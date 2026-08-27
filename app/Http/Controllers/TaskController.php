<?php

namespace App\Http\Controllers;

use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request, Project $project)
    {
        $query = $project->tasks()->with(['assignee', 'milestone', 'creator'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->assigned_to, fn($q) => $q->where('assigned_to', $request->assigned_to));

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'milestone_id' => 'nullable|exists:milestones,id',
            'due_date' => 'nullable|date',
            'priority' => 'in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
        ]);

        $todoColumn = BoardColumn::where('project_id', $project->id)
            ->where('is_done', false)
            ->orderBy('sort_order')
            ->first();

        $task = $project->tasks()->create([
            ...$request->only('title', 'description', 'assigned_to', 'milestone_id', 'priority', 'due_date', 'estimated_hours', 'ticket_id'),
            'status' => $todoColumn->slug ?? 'todo',
            'board_column_id' => $todoColumn->id ?? null,
            'created_by' => $request->user()->id,
        ]);

        if ($task->assigned_to) {
            $this->notifier->send(
                $task->assigned_to,
                'task_assigned',
                'New Task Assigned',
                "You have been assigned task: {$task->title}",
                ['task_id' => $task->id, 'project_id' => $project->id]
            );
        }

        return response()->json($task->load(['assignee', 'milestone']), 201);
    }

    public function show(Project $project, Task $task)
    {
        return response()->json($task->load(['assignee', 'milestone', 'creator', 'timeLogs.user']));
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $oldStatus = $task->status;

        $task->update($request->only(
            'title', 'description', 'assigned_to', 'milestone_id',
            'status', 'priority', 'due_date', 'estimated_hours'
        ));

        if ($oldStatus !== $task->status) {
            $this->notifier->send(
                $task->creator->id ?? $project->manager_id,
                'task_status_changed',
                'Task Status Updated',
                "Task \"{$task->title}\" changed from {$oldStatus} to {$task->status}",
                ['task_id' => $task->id]
            );
        }

        return response()->json($task->fresh()->load(['assignee', 'milestone']));
    }

    public function destroy(Project $project, Task $task)
    {
        $task->delete();
        return response()->json(['message' => 'Task deleted.']);
    }
}
