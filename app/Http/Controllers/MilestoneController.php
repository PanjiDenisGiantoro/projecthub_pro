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
            'due_date' => 'nullable|date',
            'status' => 'in:pending,in_progress,completed',
        ]);

        $milestone = $project->milestones()->create($request->only('title', 'description', 'due_date', 'status'));

        return response()->json($milestone, 201);
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $milestone->update($request->only('title', 'description', 'due_date', 'status'));
        return response()->json($milestone);
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();
        return response()->json(['message' => 'Milestone deleted.']);
    }
}
