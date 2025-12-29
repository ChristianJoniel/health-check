<?php

use App\Jobs\CheckProjectHealth;
use App\Mail\HealthCheckFailed;
use App\Mail\HealthCheckRecovered;
use App\Models\Project;
use App\Models\ProjectNotificationEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    config(['health-check.confirmation_retry_delay' => 0]);
});

test('first failure sends immediate notification', function () {
    Mail::fake();

    $project = Project::factory()->create([
        'health_status' => 'healthy',
        'consecutive_failures' => 0,
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::sequence()
        ->push('Error', 500)
        ->push('Error', 500),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->health_status)->toBe('failing');
    expect($project->consecutive_failures)->toBe(1);
    expect($project->first_failed_at)->not->toBeNull();
    expect($project->last_notification_sent_at)->not->toBeNull();

    Mail::assertQueued(HealthCheckFailed::class, function ($mail) {
        return $mail->isFirstFailure === true;
    });
});

test('recovery sends notification with downtime info', function () {
    Mail::fake();

    $project = Project::factory()->create([
        'health_status' => 'failing',
        'consecutive_failures' => 10,
        'first_failed_at' => now()->subMinutes(10),
        'last_failed_at' => now()->subMinute(),
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::response('OK', 200)]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->health_status)->toBe('healthy');
    expect($project->consecutive_failures)->toBe(0);
    expect($project->first_failed_at)->toBeNull();
    expect($project->last_recovered_at)->not->toBeNull();

    Mail::assertQueued(HealthCheckRecovered::class);
});

test('escalation interval prevents notification spam at 5 minute threshold', function () {
    Mail::fake();

    $project = Project::factory()->create([
        'health_status' => 'failing',
        'consecutive_failures' => 10, // In 6-15 range: needs 5 min interval
        'first_failed_at' => now()->subMinutes(10),
        'last_notification_sent_at' => now()->subMinutes(3), // Only 3 min ago
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::sequence()
        ->push('Error', 500)
        ->push('Error', 500),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->consecutive_failures)->toBe(11);

    // Should NOT send notification (only 3 min since last, needs 5)
    Mail::assertNotQueued(HealthCheckFailed::class);
});

test('state transition from unknown to failing is treated as first failure', function () {
    Mail::fake();

    $project = Project::factory()->create([
        'health_status' => 'unknown',
        'consecutive_failures' => 0,
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::sequence()
        ->push('Error', 500)
        ->push('Error', 500),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->health_status)->toBe('failing');
    expect($project->consecutive_failures)->toBe(1);

    Mail::assertQueued(HealthCheckFailed::class, function ($mail) {
        return $mail->isFirstFailure === true;
    });
});

test('consecutive failures counter increments correctly', function () {
    $project = Project::factory()->create([
        'health_status' => 'failing',
        'consecutive_failures' => 5,
        'first_failed_at' => now()->subMinutes(5),
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::sequence()
        ->push('Error', 500)
        ->push('Error', 500),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->consecutive_failures)->toBe(6);
    expect($project->first_failed_at)->not->toBeNull();
});

test('healthy to healthy does not send notifications', function () {
    Mail::fake();

    $project = Project::factory()->create([
        'health_status' => 'healthy',
        'consecutive_failures' => 0,
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::response('OK', 200)]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->health_status)->toBe('healthy');

    Mail::assertNothingQueued();
});

test('recovery notifications can be disabled via config', function () {
    Mail::fake();
    config(['health-check.recovery_notifications_enabled' => false]);

    $project = Project::factory()->create([
        'health_status' => 'failing',
        'consecutive_failures' => 10,
        'first_failed_at' => now()->subMinutes(10),
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake(['*' => Http::response('OK', 200)]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    $project->refresh();

    expect($project->health_status)->toBe('healthy');

    Mail::assertNotQueued(HealthCheckRecovered::class);
});
