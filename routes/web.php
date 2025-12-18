<?php

use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Laravel\Fortify\Features;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canRegister' => Features::enabled(Features::registration()),
    ]);
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('projects', App\Http\Controllers\ProjectController::class)
        ->except(['create', 'edit']);

    Route::post('projects/{project}/notification-emails', [App\Http\Controllers\ProjectNotificationEmailController::class, 'store'])
        ->name('projects.notification-emails.store');

    Route::delete('project-notification-emails/{projectNotificationEmail}', [App\Http\Controllers\ProjectNotificationEmailController::class, 'destroy'])
        ->name('project-notification-emails.destroy');
});

require __DIR__.'/settings.php';
