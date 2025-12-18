<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Models\Project;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $projects = Project::query()
            ->withCount('notificationEmails')
            ->orderByDesc('created_at')
            ->get();

        // Return JSON for AJAX requests, Inertia for regular requests
        if (request()->expectsJson() || request()->header('X-Inertia')) {
            return response()->json([
                'props' => [
                    'projects' => ProjectResource::collection($projects),
                ],
            ]);
        }

        return inertia('Projects/Index', [
            'projects' => ProjectResource::collection($projects),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProjectRequest $request): RedirectResponse
    {
        $project = Project::create([
            'name' => $request->validated('name'),
            'health_check_url' => $request->validated('health_check_url'),
            'is_active' => $request->validated('is_active', true),
        ]);

        // Create notification emails if provided
        if ($request->has('notification_emails')) {
            foreach ($request->validated('notification_emails') as $email) {
                $project->notificationEmails()->create(['email' => $email]);
            }
        }

        return back();
    }

    /**
     * Display the specified resource.
     */
    public function show(Project $project): Response
    {
        $project->load('notificationEmails');

        return inertia('Projects/Show', [
            'project' => new ProjectResource($project),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProjectRequest $request, Project $project): RedirectResponse
    {
        $project->update($request->validated());

        return back();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return back();
    }
}
