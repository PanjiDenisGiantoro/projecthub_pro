<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\NotificationService;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;

class TaskWebController extends Controller
{
    public function __construct(private NotificationService $notifier, private TeamNotifier $teamNotifier) {}

    public function index(Request $request, Project $project)
    {
        $user  = auth()->user();
        $query = $project->tasks()->with(['assignee', 'milestone'])
            ->when($request->status, fn($q) => $q->where('status', $request->status));

        if ($user->hasRole('developer')) {
            $query->where('assigned_to', $user->id);
        }

        $tasks      = $query->latest()->paginate(20);
        $milestones = $project->milestones()->get();
        $developers = User::role('developer')->where('is_active', true)->get();
        return view('tasks.index', compact('project', 'tasks', 'milestones', 'developers'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title'           => 'required|string|max:255',
            'assigned_to'     => 'nullable|exists:users,id',
            'milestone_id'    => 'nullable|exists:milestones,id',
            'start_date'      => 'nullable|date',
            'due_date'        => 'nullable|date',
            'priority'        => 'in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
        ]);

        $task = $project->tasks()->create([
            ...$request->only('title', 'description', 'assigned_to', 'milestone_id', 'priority', 'start_date', 'due_date', 'estimated_hours'),
            'created_by' => auth()->id(),
        ]);

        if ($task->assigned_to) {
            $this->notifier->send($task->assigned_to, 'task_assigned', 'Task Baru', "Task \"{$task->title}\" ditugaskan ke Anda.", ['task_id' => $task->id]);
        }

        if ($project->manager_id && $project->manager_id !== auth()->id() && $project->manager_id !== $task->assigned_to) {
            $this->notifier->send(
                $project->manager_id,
                'new_task',
                'Task Baru di Proyek',
                auth()->user()->name . " menambahkan task \"{$task->title}\" di proyek \"{$project->name}\".",
                ['task_id' => $task->id, 'project_id' => $project->id]
            );
        }

        $this->teamNotifier->notify($project, '🆕 Task Baru', "\"{$task->title}\" ditambahkan oleh " . auth()->user()->name . '.');

        return back()->with('success', 'Task berhasil dibuat.');
    }

    public function show(Project $project, Task $task)
    {
        $task->load(['assignee', 'milestone', 'creator', 'timeLogs.user']);
        $runningLog = $task->timeLogs()->where('user_id', auth()->id())->where('is_running', true)->first();
        return view('tasks.show', compact('project', 'task', 'runningLog'));
    }

    public function update(Request $request, Project $project, Task $task)
    {
        $request->validate([
            'completion_notes' => 'required_with:status|nullable|string|max:2000',
        ]);

        $old = $task->status;
        $task->update($request->only('title', 'description', 'completion_notes', 'assigned_to', 'milestone_id', 'status', 'priority', 'start_date', 'due_date', 'estimated_hours'));

        if ($old !== $task->status) {
            $notify = $task->creator_id ?? $project->manager_id;
            if ($notify) {
                $this->notifier->send($notify, 'task_status_changed', 'Status Task Berubah', "Task \"{$task->title}\" berubah dari {$old} ke {$task->status}.", ['task_id' => $task->id]);
            }
            if ($task->status === 'done') {
                $this->teamNotifier->notify($project, '✅ Task Selesai', "\"{$task->title}\" ditandai selesai oleh " . auth()->user()->name . '.');
            }
        }

        return back()->with('success', 'Task diperbarui.');
    }

    public function destroy(Project $project, Task $task)
    {
        $task->delete();
        return redirect()->route('tasks.index', $project)->with('success', 'Task dihapus.');
    }

    public function moveStatus(Request $request, Project $project, Task $task)
    {
        $request->validate([
            'status' => 'required|in:todo,in_progress,review,done',
            'notes'  => 'nullable|string|max:2000',
        ]);
        $old = $task->status;
        $task->update([
            'status'           => $request->status,
            'completion_notes' => $request->notes ?: null,
        ]);

        if ($old !== $task->status) {
            $notify = $task->created_by ?? $project->manager_id;
            if ($notify) {
                $this->notifier->send($notify, 'task_status_changed', 'Status Task Berubah',
                    "Task \"{$task->title}\" berubah dari {$old} ke {$task->status}.", ['task_id' => $task->id]);
            }
            if ($task->status === 'done') {
                $this->teamNotifier->notify($project, '✅ Task Selesai', "\"{$task->title}\" ditandai selesai oleh " . auth()->user()->name . '.');
            }
        }

        return response()->json(['ok' => true, 'status' => $task->status]);
    }

    public function storeTimeLog(Request $request, Task $task)
    {
        $this->authorize('view', $task->project);

        $request->validate(['action' => 'required|in:start,stop,manual', 'minutes' => 'required_if:action,manual|nullable|integer|min:1']);

        $user = auth()->user();

        if ($request->action === 'start') {
            TimeLog::where('user_id', $user->id)->where('is_running', true)->get()->each->stop();
            $task->timeLogs()->create(['user_id' => $user->id, 'started_at' => now(), 'is_running' => true, 'notes' => $request->notes]);
        } elseif ($request->action === 'stop') {
            $log = $task->timeLogs()->where('user_id', $user->id)->where('is_running', true)->first();
            $log?->stop();
        } else {
            $task->timeLogs()->create(['user_id' => $user->id, 'started_at' => now(), 'ended_at' => now(), 'minutes' => $request->minutes, 'notes' => $request->notes, 'is_running' => false]);
        }

        return back()->with('success', 'Waktu dicatat.');
    }
}
