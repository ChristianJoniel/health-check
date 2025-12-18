<?php

use App\Models\Project;
use App\Models\ProjectNotificationEmail;
use App\Models\User;

test('unauthenticated users cannot access project routes', function () {
    $this->get(route('projects.index'))->assertRedirect(route('login'));
    $this->post(route('projects.store'))->assertRedirect(route('login'));
});

test('authenticated users can view projects index', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson(route('projects.index'));

    $response->assertSuccessful();
    $response->assertJsonStructure(['props' => ['projects']]);
});

test('authenticated users can create a project with valid data', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Test Project',
        'health_check_url' => 'https://example.com/health',
        'is_active' => true,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'name' => 'Test Project',
        'health_check_url' => 'https://example.com/health',
        'is_active' => true,
    ]);
});

test('project creation requires name', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'health_check_url' => 'https://example.com/health',
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('name');
});

test('project creation requires valid URL', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Test Project',
        'health_check_url' => 'not-a-valid-url',
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('health_check_url');
});

test('health check URL must be unique', function () {
    $user = User::factory()->create();

    Project::factory()->create([
        'health_check_url' => 'https://example.com/health',
    ]);

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Another Project',
        'health_check_url' => 'https://example.com/health',
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('health_check_url');
});

test('project can be created with notification emails', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('projects.store'), [
        'name' => 'Test Project',
        'health_check_url' => 'https://example.com/health',
        'is_active' => true,
        'notification_emails' => [
            'admin@example.com',
            'alerts@example.com',
        ],
    ]);

    $response->assertRedirect();

    $project = Project::where('name', 'Test Project')->first();

    $this->assertCount(2, $project->notificationEmails);
    $this->assertDatabaseHas('project_notification_emails', [
        'project_id' => $project->id,
        'email' => 'admin@example.com',
    ]);
    $this->assertDatabaseHas('project_notification_emails', [
        'project_id' => $project->id,
        'email' => 'alerts@example.com',
    ]);
});

test('authenticated users can update project details', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create([
        'name' => 'Original Name',
        'health_check_url' => 'https://example.com/original',
        'is_active' => true,
    ]);

    $response = $this->actingAs($user)->patch(route('projects.update', $project), [
        'name' => 'Updated Name',
        'health_check_url' => 'https://example.com/updated',
        'is_active' => false,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated Name',
        'health_check_url' => 'https://example.com/updated',
        'is_active' => false,
    ]);
});

test('health check URL must be unique when updating except for current project', function () {
    $user = User::factory()->create();

    $project1 = Project::factory()->create([
        'health_check_url' => 'https://example.com/health1',
    ]);

    $project2 = Project::factory()->create([
        'health_check_url' => 'https://example.com/health2',
    ]);

    // Should fail: trying to use project1's URL
    $response = $this->actingAs($user)->patch(route('projects.update', $project2), [
        'name' => $project2->name,
        'health_check_url' => 'https://example.com/health1',
        'is_active' => true,
    ]);

    $response->assertSessionHasErrors('health_check_url');

    // Should succeed: updating to the same URL it already has
    $response = $this->actingAs($user)->patch(route('projects.update', $project2), [
        'name' => $project2->name,
        'health_check_url' => 'https://example.com/health2',
        'is_active' => true,
    ]);

    $response->assertSessionHasNoErrors();
});

test('authenticated users can delete a project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->delete(route('projects.destroy', $project));

    $response->assertRedirect();

    $this->assertDatabaseMissing('projects', [
        'id' => $project->id,
    ]);
});

test('deleting project cascades to notification emails', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $email = ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'test@example.com',
    ]);

    $this->actingAs($user)->delete(route('projects.destroy', $project));

    $this->assertDatabaseMissing('project_notification_emails', [
        'id' => $email->id,
    ]);
});
