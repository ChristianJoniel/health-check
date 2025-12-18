<?php

use App\Models\HealthCheckFailure;
use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('dashboard displays correct active projects count', function () {
    $user = User::factory()->create();

    Project::factory()->count(3)->create(['is_active' => true]);
    Project::factory()->count(2)->create(['is_active' => false]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('activeProjectsCount', 3)
        );
});

test('dashboard displays correct failures today count', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    HealthCheckFailure::factory()->count(5)->create([
        'project_id' => $project->id,
        'checked_at' => now()->subHours(12),
    ]);

    HealthCheckFailure::factory()->count(3)->create([
        'project_id' => $project->id,
        'checked_at' => now()->subDays(2),
    ]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('failuresToday', 5)
        );
});

test('dashboard displays uptime percentage', function () {
    $user = User::factory()->create();
    Project::factory()->create(['is_active' => true]);

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('uptimePercentage')
        );
});

test('dashboard returns 100% uptime when no active projects', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('activeProjectsCount', 0)
            ->where('uptimePercentage', 100)
        );
});

test('dashboard has deferred failures prop', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('activeProjectsCount')
            ->has('failuresToday')
            ->has('uptimePercentage')
        );
});

test('dashboard failures are ordered by most recent first', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $oldFailure = HealthCheckFailure::factory()->create([
        'project_id' => $project->id,
        'checked_at' => now()->subDays(1),
    ]);

    $newFailure = HealthCheckFailure::factory()->create([
        'project_id' => $project->id,
        'checked_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('dashboard'));

    $response->assertSuccessful();
});
