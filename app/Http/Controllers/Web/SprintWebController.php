<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use Illuminate\Http\Request;

class SprintWebController extends Controller
{
    public function index(Project $project)
    {
        $sprints = $project->sprints()->with(['tasks.assignee'])->orderByDesc('start_date')->get();
        $backlog = $project->tasks()->whereNull('sprint_id')->with('assignee', 'milestone')->orderBy('sort_order')->get();
        return view('sprints.index', compact('project', 'sprints', 'backlog'));
    }

    public function store(Request $request, Project $project)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();

        Sprint::create($data);

        return back()->with('success', 'Sprint dibuat.');
    }

    public function show(Project $project, Sprint $sprint)
    {
        $sprint->load(['tasks' => fn($q) => $q->with('assignee', 'milestone')->orderBy('sort_order')]);
        $statuses = ['todo' => 'To Do', 'in_progress' => 'In Progress', 'review' => 'Review', 'done' => 'Done'];
        return view('sprints.show', compact('project', 'sprint', 'statuses'));
    }

    public function update(Request $request, Project $project, Sprint $sprint)
    {
        $data = $request->validate([
            'name'       => 'required|string|max:255',
            'goal'       => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'status'     => 'required|in:planned,active,completed',
        ]);

        // Only one sprint can be active at a time
        if ($data['status'] === 'active') {
            $project->sprints()->where('id', '!=', $sprint->id)->where('status', 'active')->update(['status' => 'completed']);
        }

        $sprint->update($data);

        return back()->with('success', 'Sprint diperbarui.');
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        // Move tasks back to backlog
        $sprint->tasks()->update(['sprint_id' => null]);
        $sprint->delete();
        return back()->with('success', 'Sprint dihapus.');
    }

    public function addTask(Request $request, Project $project, Sprint $sprint)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id']);
        Task::where('id', $request->task_id)->where('project_id', $project->id)->update(['sprint_id' => $sprint->id]);
        return back()->with('success', 'Task ditambahkan ke sprint.');
    }

    public function removeTask(Request $request, Project $project, Sprint $sprint)
    {
        $request->validate(['task_id' => 'required|exists:tasks,id']);
        Task::where('id', $request->task_id)->where('sprint_id', $sprint->id)->update(['sprint_id' => null]);
        return back()->with('success', 'Task dipindahkan ke backlog.');
    }
}
