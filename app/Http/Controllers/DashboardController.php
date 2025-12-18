<?php

namespace App\Http\Controllers;

use App\Http\Resources\HealthCheckFailureResource;
use App\Models\HealthCheckFailure;
use App\Models\Project;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
        $activeProjectsCount = Project::query()
            ->where('is_active', true)
            ->count();

        $failuresToday = HealthCheckFailure::query()
            ->where('checked_at', '>=', now()->subDay())
            ->count();

        $uptimePercentage = $this->calculateUptimePercentage();

        return Inertia::render('Dashboard', [
            'activeProjectsCount' => $activeProjectsCount,
            'failuresToday' => $failuresToday,
            'uptimePercentage' => $uptimePercentage,
            'failures' => Inertia::defer(fn () => HealthCheckFailureResource::collection(
                HealthCheckFailure::query()
                    ->with('project')
                    ->orderByDesc('checked_at')
                    ->paginate(15)
            )),
        ]);
    }

    protected function calculateUptimePercentage(): float
    {
        $activeProjects = Project::query()
            ->where('is_active', true)
            ->count();

        if ($activeProjects === 0) {
            return 100.0;
        }

        $checksPerDay = 60 * 24;
        $expectedChecks = $activeProjects * $checksPerDay;

        $failuresInLast24h = HealthCheckFailure::query()
            ->where('checked_at', '>=', now()->subDay())
            ->count();

        if ($expectedChecks === 0) {
            return 100.0;
        }

        $successfulChecks = max(0, $expectedChecks - $failuresInLast24h);
        $uptime = ($successfulChecks / $expectedChecks) * 100;

        return round($uptime, 2);
    }
}
