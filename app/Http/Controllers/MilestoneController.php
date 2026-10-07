<?php

namespace App\Http\Controllers;

use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneController extends Controller
{
    public function index(Project $project)
    {
        return response()->json($project->milestones()->with('tasks')->get());
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'status' => 'in:pending,in_progress,completed',
        ]);

        $milestone = $project->milestones()->create($request->only('title', 'description', 'start_date', 'due_date', 'assigned_to', 'status'));

        return response()->json($milestone, 201);
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $request->validate([
            'start_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        $milestone->update($request->only('title', 'description', 'start_date', 'due_date', 'assigned_to', 'status'));
        return response()->json($milestone);
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();
        return response()->json(['message' => 'Milestone deleted.']);
    }
}
