<?php

namespace App\Console\Commands;

use App\Jobs\CheckProjectHealth;
use App\Models\Project;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class HealthCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'health:check';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check the health of monitored endpoints';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $projects = Project::query()
            ->where('is_active', true)
            ->with('notificationEmails')
            ->get();

        if ($projects->isEmpty()) {
            $this->info('No active projects to check.');

            return self::SUCCESS;
        }

        $jobs = $projects->map(fn (Project $project) => new CheckProjectHealth($project))->all();

        $batch = Bus::batch($jobs)
            ->name('Health Check - '.now()->format('Y-m-d H:i:s'))
            ->before(function (Batch $batch) {
                info("Starting health check batch [{$batch->id}] with {$batch->totalJobs} jobs");
            })
            ->progress(function (Batch $batch) {
                info("Health check batch [{$batch->id}] progress: {$batch->progress()}%");
            })
            ->then(function (Batch $batch) {
                info("Health check batch [{$batch->id}] completed successfully");
            })
            ->catch(function (Batch $batch, Throwable $e) {
                info("Health check batch [{$batch->id}] encountered an error: {$e->getMessage()}");
            })
            ->finally(function (Batch $batch) {
                info("Health check batch [{$batch->id}] finished. Processed: {$batch->processedJobs()}, Failed: {$batch->failedJobs}");
            })
            ->allowFailures()
            ->dispatch();

        $this->info("Dispatched health check batch [{$batch->id}] with {$batch->totalJobs} project(s)");

        return self::SUCCESS;
    }
}
