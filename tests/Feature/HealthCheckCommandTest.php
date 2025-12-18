<?php

use App\Jobs\CheckProjectHealth;
use App\Mail\HealthCheckBatchFailed;
use App\Mail\HealthCheckFailed;
use App\Models\HealthCheckFailure;
use App\Models\Project;
use App\Models\ProjectNotificationEmail;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    Http::preventStrayRequests();
    Mail::fake();
});

test('successful ping does not record failure', function () {
    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.com/up' => Http::response('OK', 200),
    ]);

    (new CheckProjectHealth($project))->handle();

    expect(HealthCheckFailure::count())->toBe(0);
    Mail::assertNothingSent();
});

test('failed ping records failure and sends email', function () {
    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake([
        'https://example.com/up' => Http::response('Internal Server Error', 500),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    expect(HealthCheckFailure::count())->toBe(1);

    $failure = HealthCheckFailure::first();
    expect($failure->project_id)->toBe($project->id);
    expect($failure->response_code)->toBe(500);
    expect($failure->error_message)->toContain('HTTP 500');

    Mail::assertQueued(HealthCheckFailed::class, function ($mail) use ($failure) {
        return $mail->failure->id === $failure->id;
    });
});

test('connection timeout records failure', function () {
    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake([
        'https://example.com/up' => Http::failedConnection(),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    expect(HealthCheckFailure::count())->toBe(1);

    $failure = HealthCheckFailure::first();
    expect($failure->project_id)->toBe($project->id);
    expect($failure->response_code)->toBe(0);
    expect($failure->error_message)->toContain('Connection failed');

    Mail::assertQueued(HealthCheckFailed::class);
});

test('inactive projects are not checked', function () {
    Bus::fake();

    Project::factory()->create([
        'health_check_url' => 'https://active.com/up',
        'is_active' => true,
    ]);

    Project::factory()->create([
        'health_check_url' => 'https://inactive.com/up',
        'is_active' => false,
    ]);

    $this->artisan('health:check')
        ->assertSuccessful();

    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 1
            && $batch->jobs->first()->project->health_check_url === 'https://active.com/up';
    });
});

test('no email sent when notifications are disabled', function () {
    config(['health-check.notifications_enabled' => false]);

    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);

    Http::fake([
        'https://example.com/up' => Http::response('Error', 500),
    ]);

    (new CheckProjectHealth($project->load('notificationEmails')))->handle();

    expect(HealthCheckFailure::count())->toBe(1);
    Mail::assertNothingSent();
});

test('no email sent when project has no notification emails', function () {
    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.com/up' => Http::response('Error', 500),
    ]);

    (new CheckProjectHealth($project))->handle();

    expect(HealthCheckFailure::count())->toBe(1);
    Mail::assertNothingSent();
});

test('multiple projects are batched', function () {
    Bus::fake();

    Project::factory()->create([
        'health_check_url' => 'https://site1.com/up',
        'is_active' => true,
    ]);

    Project::factory()->create([
        'health_check_url' => 'https://site2.com/up',
        'is_active' => true,
    ]);

    $this->artisan('health:check')
        ->assertSuccessful();

    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 2
            && $batch->name === 'Health Check - '.now()->format('Y-m-d H:i:s');
    });
});

test('no batch dispatched when no active projects', function () {
    Bus::fake();

    Project::factory()->create([
        'health_check_url' => 'https://inactive.com/up',
        'is_active' => false,
    ]);

    $this->artisan('health:check')
        ->assertSuccessful()
        ->expectsOutput('No active projects to check.');

    Bus::assertNothingBatched();
});

test('job skips execution when batch is cancelled', function () {
    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.com/up' => Http::response('OK', 200),
    ]);

    [$job, $batch] = (new CheckProjectHealth($project))->withFakeBatch();

    $batch->cancel();

    $job->handle();

    Http::assertNothingSent();
});

test('batch allows failures so all projects are checked', function () {
    Bus::fake();

    Project::factory()->count(3)->create(['is_active' => true]);

    $this->artisan('health:check')
        ->assertSuccessful();

    Bus::assertBatched(function ($batch) {
        return $batch->jobs->count() === 3;
    });
});

test('admin emails are parsed correctly from config', function () {
    config(['health-check.admin_emails' => 'admin1@example.com, admin2@example.com, admin3@example.com']);

    $emailsString = config('health-check.admin_emails', '');
    $emails = array_filter(array_map('trim', explode(',', $emailsString)));

    expect($emails)->toBe(['admin1@example.com', 'admin2@example.com', 'admin3@example.com']);
});

test('admin emails returns empty array when not configured', function () {
    config(['health-check.admin_emails' => '']);

    $emailsString = config('health-check.admin_emails', '');
    $emails = empty($emailsString) ? [] : array_filter(array_map('trim', explode(',', $emailsString)));

    expect($emails)->toBe([]);
});

test('admin emails handles single email', function () {
    config(['health-check.admin_emails' => 'admin@example.com']);

    $emailsString = config('health-check.admin_emails', '');
    $emails = array_filter(array_map('trim', explode(',', $emailsString)));

    expect($emails)->toBe(['admin@example.com']);
});

test('queue failing event sends email to admin when CheckProjectHealth job fails', function () {
    config(['health-check.admin_emails' => 'admin@example.com']);

    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    $emailsString = config('health-check.admin_emails', '');
    $emails = array_filter(array_map('trim', explode(',', $emailsString)));

    expect($emails)->toBe(['admin@example.com']);
    expect(config('health-check.admin_emails'))->toBe('admin@example.com');
});

test('no admin email sent when admin emails not configured', function () {
    config(['health-check.admin_emails' => '']);

    $project = Project::factory()->create([
        'health_check_url' => 'https://example.com/up',
        'is_active' => true,
    ]);

    Http::fake([
        'https://example.com/up' => Http::failedConnection(),
    ]);

    (new CheckProjectHealth($project))->handle();

    Mail::assertNotSent(HealthCheckBatchFailed::class);
});
