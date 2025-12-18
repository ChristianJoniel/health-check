<?php

namespace App\Providers;

use App\Jobs\CheckProjectHealth;
use App\Mail\HealthCheckBatchFailed;
use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Queue::failing(function (JobFailed $event) {
            $payload = $event->job->payload();
            $jobClass = $payload['data']['commandName'] ?? null;

            if ($jobClass !== CheckProjectHealth::class) {
                return;
            }

            $adminEmails = $this->getAdminEmails();

            if (empty($adminEmails)) {
                return;
            }

            $batchId = $payload['data']['batch_id'] ?? null;
            $batch = $batchId ? Bus::findBatch($batchId) : null;

            $job = unserialize($payload['data']['command']);
            $project = $job->project;

            Mail::to($adminEmails)->send(new HealthCheckBatchFailed(
                batchId: $batch?->id ?? 'N/A',
                batchName: $batch?->name ?? 'Health Check',
                errorMessage: $event->exception->getMessage(),
                failedJobs: $batch?->failedJobs ?? 1,
                totalJobs: $batch?->totalJobs ?? 1,
                projectName: $project->name,
                projectUrl: $project->health_check_url,
            ));
        });
    }

    /**
     * Get the admin email addresses from configuration.
     *
     * @return array<int, string>
     */
    protected function getAdminEmails(): array
    {
        $emailsString = config('health-check.admin_emails', '');

        if (empty($emailsString)) {
            return [];
        }

        return array_filter(
            array_map('trim', explode(',', $emailsString))
        );
    }
}
