<?php

namespace App\Jobs;

use App\Mail\HealthCheckFailed;
use App\Mail\HealthCheckRecovered;
use App\Models\HealthCheckFailure;
use App\Models\Project;
use Illuminate\Bus\Batchable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class CheckProjectHealth implements ShouldQueue
{
    use Batchable, Queueable;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 10;

    /**
     * Create a new job instance.
     */
    public function __construct(public Project $project) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        if ($this->batch()?->cancelled()) {
            return;
        }

        $result = $this->performHealthCheck();

        if ($result['success']) {
            $this->handleSuccessfulCheck($result['response_time']);

            return;
        }

        if (config('health-check.confirmation_retry_enabled', true)) {
            $delay = config('health-check.confirmation_retry_delay', 30);

            Log::info("Health check failed for {$this->project->name}, retrying in {$delay} seconds", [
                'project_id' => $this->project->id,
                'initial_error' => $result['error'],
            ]);

            sleep($delay);

            $retryResult = $this->performHealthCheck();

            if ($retryResult['success']) {
                Log::info("Health check recovered after retry for {$this->project->name}", [
                    'project_id' => $this->project->id,
                    'response_time_ms' => $retryResult['response_time'],
                ]);

                $this->handleSuccessfulCheck($retryResult['response_time']);

                return;
            }

            $result = $retryResult;
        }

        $this->recordFailure($result['error'], $result['response_code'], $result['response_time']);
    }

    /**
     * Perform a single health check request.
     *
     * @return array{success: bool, error: string|null, response_code: int, response_time: int}
     */
    protected function performHealthCheck(): array
    {
        $timeout = config('health-check.timeout', 10);
        $startTime = microtime(true);

        try {
            $request = Http::timeout($timeout);

            if (! config('health-check.verify_ssl', true)) {
                $request = $request->withoutVerifying();
            }

            $response = $request->get($this->project->health_check_url);
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'error' => null,
                    'response_code' => $response->status(),
                    'response_time' => $responseTime,
                ];
            }

            return [
                'success' => false,
                'error' => 'HTTP '.$response->status().': '.$response->reason(),
                'response_code' => $response->status(),
                'response_time' => $responseTime,
            ];
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'success' => false,
                'error' => 'Connection failed: '.$e->getMessage(),
                'response_code' => 0,
                'response_time' => $responseTime,
            ];
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'success' => false,
                'error' => 'Error: '.$e->getMessage(),
                'response_code' => 0,
                'response_time' => $responseTime,
            ];
        }
    }

    /**
     * Handle a successful health check.
     */
    protected function handleSuccessfulCheck(int $responseTime): void
    {
        $wasFailingBefore = $this->project->health_status === 'failing';

        $this->project->update([
            'health_status' => 'healthy',
            'consecutive_failures' => 0,
            'first_failed_at' => null,
            'last_failed_at' => null,
            'last_recovered_at' => $wasFailingBefore ? now() : $this->project->last_recovered_at,
        ]);

        if ($wasFailingBefore) {
            $this->sendRecoveryNotification();
        }

        Log::info("Health check passed for {$this->project->name}", [
            'project_id' => $this->project->id,
            'response_time_ms' => $responseTime,
        ]);
    }

    protected function recordFailure(string $errorMessage, int $responseCode, int $responseTime): void
    {
        $failure = HealthCheckFailure::create([
            'project_id' => $this->project->id,
            'error_message' => $errorMessage,
            'response_code' => $responseCode,
            'response_time_ms' => $responseTime,
            'checked_at' => now(),
        ]);

        Log::warning("Health check failed for {$this->project->name}", [
            'project_id' => $this->project->id,
            'url' => $this->project->health_check_url,
            'error' => $errorMessage,
            'response_code' => $responseCode,
        ]);

        $isFirstFailure = $this->project->health_status !== 'failing';

        $this->project->update([
            'health_status' => 'failing',
            'consecutive_failures' => $this->project->consecutive_failures + 1,
            'first_failed_at' => $this->project->first_failed_at ?? now(),
            'last_failed_at' => now(),
        ]);

        $this->project->refresh();

        if ($this->shouldSendNotification($isFirstFailure)) {
            $this->sendFailureNotification($failure, $isFirstFailure);

            $this->project->update([
                'last_notification_sent_at' => now(),
            ]);
        }
    }

    protected function sendFailureNotification(HealthCheckFailure $failure, bool $isFirstFailure): void
    {
        if (! config('health-check.notifications_enabled', true)) {
            return;
        }

        $emails = $this->project->notificationEmails->pluck('email')->toArray();

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(new HealthCheckFailed($failure, $isFirstFailure));
    }

    protected function shouldSendNotification(bool $isFirstFailure): bool
    {
        if (! config('health-check.notifications_enabled', true)) {
            return false;
        }

        // Always send on first failure (state transition)
        if ($isFirstFailure) {
            return true;
        }

        // Get the escalation interval for current failure count
        $intervalMinutes = $this->getEscalationInterval($this->project->consecutive_failures);

        // If no previous notification, send one
        if (! $this->project->last_notification_sent_at) {
            return true;
        }

        // Check if enough time has passed since last notification
        $minutesSinceLastNotification = now()->diffInMinutes($this->project->last_notification_sent_at);

        return $minutesSinceLastNotification >= $intervalMinutes;
    }

    protected function getEscalationInterval(int $consecutiveFailures): int
    {
        $escalation = config('health-check.escalation', []);

        foreach ($escalation as $range) {
            if ($consecutiveFailures >= $range['min'] && $consecutiveFailures <= $range['max']) {
                return $range['interval'];
            }
        }

        // Default: hourly if not in any range
        return 60;
    }

    protected function sendRecoveryNotification(): void
    {
        if (! config('health-check.notifications_enabled', true) ||
            ! config('health-check.recovery_notifications_enabled', true)) {
            return;
        }

        $emails = $this->project->notificationEmails->pluck('email')->toArray();

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(new HealthCheckRecovered($this->project));
    }
}
