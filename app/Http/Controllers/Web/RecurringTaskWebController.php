<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\RecurringTaskDefinition;
use App\Models\User;
use Illuminate\Http\Request;

class RecurringTaskWebController extends Controller
{
    public function index(Project $project)
    {
        $definitions = $project->recurringTasks()->with('assignee', 'milestone')->orderByDesc('id')->get();
        $milestones = $project->milestones()->orderBy('title')->get(['id', 'title']);
        $users = User::orderBy('name')->get(['id', 'name']);
        return view('recurring.index', compact('project', 'definitions', 'milestones', 'users'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'assigned_to'     => 'nullable|exists:users,id',
            'milestone_id'    => 'nullable|exists:milestones,id',
            'frequency'       => 'required|in:daily,weekly,biweekly,monthly',
            'day_of_week'     => 'nullable|integer|min:0|max:6',
            'day_of_month'    => 'nullable|integer|min:1|max:28',
            'priority'        => 'required|in:low,medium,high,critical',
            'estimated_hours' => 'nullable|integer|min:0',
            'due_offset_days' => 'required|integer|min:0',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        RecurringTaskDefinition::create($data);

        return back()->with('success', 'Recurring task ditambahkan.');
    }

    public function update(Request $request, Project $project, RecurringTaskDefinition $recurringTask)
    {
        $data = $request->validate([
            'title'           => 'required|string|max:255',
            'description'     => 'nullable|string',
            'assigned_to'     => 'nullable|exists:users,id',
            'frequency'       => 'required|in:daily,weekly,biweekly,monthly',
            'day_of_week'     => 'nullable|integer|min:0|max:6',
            'day_of_month'    => 'nullable|integer|min:1|max:28',
            'priority'        => 'required|in:low,medium,high,critical',
            'estimated_hours' => 'nullable|integer|min:0',
            'due_offset_days' => 'required|integer|min:0',
            'is_active'       => 'boolean',
        ]);

        $recurringTask->update($data);

        return back()->with('success', 'Recurring task diperbarui.');
    }

    public function destroy(Project $project, RecurringTaskDefinition $recurringTask)
    {
        $recurringTask->update(['is_active' => false]);
        $recurringTask->delete();
        return back()->with('success', 'Recurring task dihapus.');
    }
}
