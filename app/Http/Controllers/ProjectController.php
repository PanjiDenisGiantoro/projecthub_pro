<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectMember;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Project::with(['client', 'manager', 'members.user'])
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        if ($user->hasRole('customer')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole('developer') || $user->hasRole('marketing')) {
            $query->whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->orWhere('manager_id', $user->id);
        }

        return response()->json($query->latest()->paginate(15));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'in:draft,active,on_hold,completed,cancelled',
        ]);

        $project = Project::create($request->only(
            'name', 'description', 'client_id', 'manager_id',
            'status', 'start_date', 'end_date', 'budget'
        ));

        return response()->json($project->load(['client', 'manager']), 201);
    }

    public function show(Project $project)
    {
        $this->authorizeProjectAccess($project);
        return response()->json($project->load(['client', 'manager', 'members.user', 'milestones']));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'sometimes|string|max:255',
            'status' => 'sometimes|in:draft,active,on_hold,completed,cancelled',
            'progress' => 'sometimes|integer|min:0|max:100',
            'end_date' => 'sometimes|nullable|date',
            'budget' => 'sometimes|nullable|numeric|min:0',
        ]);

        $project->update($request->only(
            'name', 'description', 'client_id', 'manager_id',
            'status', 'start_date', 'end_date', 'budget', 'progress'
        ));

        return response()->json($project->fresh()->load(['client', 'manager']));
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return response()->json(['message' => 'Project deleted.']);
    }

    public function addMember(Request $request, Project $project)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'required|in:developer,marketing',
            'max_hours_per_day' => 'integer|min:1|max:24',
        ]);

        $member = ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $request->user_id],
            ['role' => $request->role, 'max_hours_per_day' => $request->get('max_hours_per_day', 8)]
        );

        return response()->json($member->load('user'), 201);
    }

    public function removeMember(Project $project, $userId)
    {
        ProjectMember::where('project_id', $project->id)
            ->where('user_id', $userId)
            ->delete();

        return response()->json(['message' => 'Member removed.']);
    }

    private function authorizeProjectAccess(Project $project): void
    {
        $user = auth()->user();
        if ($user->hasRole(['admin', 'manager'])) return;

        if ($user->hasRole('customer') && $project->client_id !== $user->id) {
            abort(403, 'Access denied.');
        }

        if ($user->hasRole(['developer', 'marketing'])) {
            $isMember = $project->members()->where('user_id', $user->id)->exists();
            if (!$isMember && $project->manager_id !== $user->id) {
                abort(403, 'Access denied.');
            }
        }
    }
}
