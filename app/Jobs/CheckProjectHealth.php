<?php

namespace App\Jobs;

use App\Mail\HealthCheckFailed;
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
                Log::info("Health check passed for {$this->project->name}", [
                    'project_id' => $this->project->id,
                    'response_time_ms' => $responseTime,
                ]);

                return;
            }

            $this->recordFailure(
                'HTTP '.$response->status().': '.$response->reason(),
                $response->status(),
                $responseTime
            );
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $this->recordFailure(
                'Connection failed: '.$e->getMessage(),
                0,
                $responseTime
            );
        } catch (\Exception $e) {
            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            $this->recordFailure(
                'Error: '.$e->getMessage(),
                0,
                $responseTime
            );
        }
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

        $this->sendNotifications($failure);
    }

    protected function sendNotifications(HealthCheckFailure $failure): void
    {
        if (! config('health-check.notifications_enabled', true)) {
            return;
        }

        $emails = $this->project->notificationEmails->pluck('email')->toArray();

        if (empty($emails)) {
            return;
        }

        Mail::to($emails)->send(new HealthCheckFailed($failure));
    }
}
