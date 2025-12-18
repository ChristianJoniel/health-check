<?php

use App\Models\Project;
use App\Models\ProjectNotificationEmail;
use App\Models\User;

test('can add notification email to project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->post(
        route('projects.notification-emails.store', $project),
        ['email' => 'test@example.com']
    );

    $response->assertRedirect();

    $this->assertDatabaseHas('project_notification_emails', [
        'project_id' => $project->id,
        'email' => 'test@example.com',
    ]);
});

test('email validation on adding notification email', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $response = $this->actingAs($user)->post(
        route('projects.notification-emails.store', $project),
        ['email' => 'not-a-valid-email']
    );

    $response->assertSessionHasErrors('email');
});

test('email must be unique per project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'existing@example.com',
    ]);

    $response = $this->actingAs($user)->post(
        route('projects.notification-emails.store', $project),
        ['email' => 'existing@example.com']
    );

    $response->assertSessionHasErrors('email');
});

test('same email can exist on different projects', function () {
    $user = User::factory()->create();
    $project1 = Project::factory()->create();
    $project2 = Project::factory()->create();

    ProjectNotificationEmail::factory()->create([
        'project_id' => $project1->id,
        'email' => 'shared@example.com',
    ]);

    $response = $this->actingAs($user)->post(
        route('projects.notification-emails.store', $project2),
        ['email' => 'shared@example.com']
    );

    $response->assertRedirect();
    $response->assertSessionHasNoErrors();

    $this->assertCount(2, ProjectNotificationEmail::where('email', 'shared@example.com')->get());
});

test('can remove notification email from project', function () {
    $user = User::factory()->create();
    $project = Project::factory()->create();

    $email = ProjectNotificationEmail::factory()->create([
        'project_id' => $project->id,
        'email' => 'remove@example.com',
    ]);

    $response = $this->actingAs($user)->delete(
        route('project-notification-emails.destroy', $email)
    );

    $response->assertRedirect();

    $this->assertDatabaseMissing('project_notification_emails', [
        'id' => $email->id,
    ]);
});

test('unauthenticated users cannot manage notification emails', function () {
    $project = Project::factory()->create();
    $email = ProjectNotificationEmail::factory()->create();

    $this->post(route('projects.notification-emails.store', $project))
        ->assertRedirect(route('login'));

    $this->delete(route('project-notification-emails.destroy', $email))
        ->assertRedirect(route('login'));
});
