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
        $request->validate(['title' => 'required|string|max:255', 'due_date' => 'nullable|date']);
        $project->milestones()->create($request->only('title', 'description', 'due_date', 'status'));
        return back()->with('success', 'Milestone ditambahkan.');
    }

    public function update(Request $request, Project $project, Milestone $milestone)
    {
        $milestone->update($request->only('title', 'description', 'due_date', 'status'));
        return back()->with('success', 'Milestone diperbarui.');
    }

    public function destroy(Project $project, Milestone $milestone)
    {
        $milestone->delete();
        return back()->with('success', 'Milestone dihapus.');
    }
}
