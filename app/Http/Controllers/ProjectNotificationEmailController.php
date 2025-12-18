<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectNotificationEmailRequest;
use App\Models\Project;
use App\Models\ProjectNotificationEmail;
use Illuminate\Http\RedirectResponse;

class ProjectNotificationEmailController extends Controller
{
    /**
     * Store a newly created notification email for a project.
     */
    public function store(StoreProjectNotificationEmailRequest $request, Project $project): RedirectResponse
    {
        $project->notificationEmails()->create([
            'email' => $request->validated('email'),
        ]);

        return back();
    }

    /**
     * Remove the specified notification email from storage.
     */
    public function destroy(ProjectNotificationEmail $projectNotificationEmail): RedirectResponse
    {
        $projectNotificationEmail->delete();

        return back();
    }
}
