<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\Project;
use Illuminate\Http\Request;

class MilestoneWebController extends Controller
{
    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'start_date'  => 'nullable|date',
            'due_date'    => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
        ]);
        $project->milestones()->create($request->only('title', 'description', 'start_date', 'due_date', 'status', 'assigned_to'));
        return back()->with('success', 'Milestone ditambahkan.');
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $request->validate(['assigned_to' => 'nullable|exists:users,id']);
        $milestone->update($request->only('title', 'description', 'start_date', 'due_date', 'status', 'assigned_to'));
        return back()->with('success', 'Milestone diperbarui.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();
        return back()->with('success', 'Milestone dihapus.');
    }
}
