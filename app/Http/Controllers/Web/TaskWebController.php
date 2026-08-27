<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\BoardColumn;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskWebController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier, private TeamNotifier $teamNotifier) {}

    public function index(Request $request, Project $project)
    {
        $query = $project->tasks()->with(['assignee', 'milestone'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        $tasks = $query->latest()->paginate($this->perPage($request))->withQueryString();
        $milestones = $project->milestones()->get();
        $developers = User::role('member')->where('is_active', true)->get();

        return view('tasks.index', compact('project', 'tasks', 'milestones', 'developers'));
    }

    public function allTasks(Request $request)
    {
        $authUser = auth()->user();
        $companyId = $authUser->company_id;

        $companyScope = function ($q) use ($authUser, $companyId) {
            if (! $authUser->is_super_admin && $companyId) {
                $q->whereHas('project', fn ($p) => $p->where('company_id', $companyId));
            }
        };

        $query = Task::with(['project', 'assignee', 'sprint'])
            ->tap($companyScope)
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->priority, fn ($q) => $q->where('priority', $request->priority));

        if ($authUser->hasRole('client')) {
            $query->whereHas('project', fn ($p) => $p->where('client_id', $authUser->id));
        }

        $kanbanLimit = 300;
        $kanbanTasks = (clone $query)->latest()->limit($kanbanLimit)->get();
        $kanbanTruncated = $kanbanTasks->count() >= $kanbanLimit;

        $tasks = $query->latest()->paginate($this->perPage($request))->withQueryString();

        return view('tasks.all', compact('tasks', 'kanbanTasks', 'kanbanTruncated'));
    }

    public function store(Request $request, Project $project, GoogleCalendarService $calendar)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'assigned_to' => 'nullable|exists:users,id',
            'milestone_id' => 'nullable|exists:milestones,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'priority' => 'in:low,medium,high,urgent',
            'estimated_hours' => 'nullable|integer|min:1',
        ]);

        $todoColumn = BoardColumn::where('project_id', $project->id)
            ->where('is_done', false)
            ->orderBy('sort_order')
            ->first();

        $task = $project->tasks()->create([
            ...$request->only('title', 'description', 'assigned_to', 'milestone_id', 'priority', 'start_date', 'due_date', 'estimated_hours'),
            'status' => $todoColumn->slug ?? 'todo',
            'board_column_id' => $todoColumn->id ?? null,
            'created_by' => auth()->id(),
        ]);

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForTask($task, auth()->user());
            } catch (\Throwable $e) {
                // silent — user tetap bisa klik "Buat Meeting" manual nanti
            }
        }

        if ($task->assigned_to) {
            $this->notifier->send($task->assigned_to, 'task_assigned', 'Task Baru', "Task \"{$task->title}\" ditugaskan ke Anda.", ['task_id' => $task->id]);
        }

        if ($project->manager_id && $project->manager_id !== auth()->id() && $project->manager_id !== $task->assigned_to) {
            $this->notifier->send(
                $project->manager_id,
                'new_task',
                'Task Baru di Proyek',
                auth()->user()->name." menambahkan task \"{$task->title}\" di proyek \"{$project->name}\".",
                ['task_id' => $task->id, 'project_id' => $project->id]
            );
        }

        $this->teamNotifier->notify($project, '🆕 Task Baru', "\"{$task->title}\" ditambahkan oleh ".auth()->user()->name.'.');

        return back()->with('success', 'Task berhasil dibuat.');
    }

    public function show(Project $project, Task $task)
    {
        $task->load(['assignee', 'milestone', 'creator', 'timeLogs.user']);
        $runningLog = $task->timeLogs()->where('user_id', auth()->id())->where('is_running', true)->first();

        return view('tasks.show', compact('project', 'task', 'runningLog'));
    }

    public function update(Request $request, Project $project, Task $task, GoogleCalendarService $calendar)
    {
        $request->validate([
            'completion_notes' => 'nullable|string|max:2000|required_if:status,done',
        ]);

        $old = $task->status;
        $data = $request->only('title', 'description', 'completion_notes', 'assigned_to', 'milestone_id', 'status', 'priority', 'start_date', 'due_date', 'estimated_hours');

        // Kalau status diubah lewat form edit (bukan drag-drop kanban), board_column_id juga
        // harus ikut disinkronkan — kalau tidak, task-nya "hilang" dari kolom board karena
        // masih nunjuk ke kolom lama sementara statusnya sudah berubah.
        if ($request->filled('status') && $request->status !== $old) {
            $column = BoardColumn::where('project_id', $project->id)->where('slug', $request->status)->first();
            $data['board_column_id'] = $column->id ?? $task->board_column_id;
        }

        $task->update($data);

        if (($task->wasChanged('due_date') || $task->wasChanged('start_date')) && $task->google_event_id) {
            try {
                $calendar->syncMeetingTime($task, auth()->user());
            } catch (\Throwable $e) {
                // silent — jadwal Google Calendar tetap yang lama, tidak blokir update task
            }
        }

        if ($old !== $task->status) {
            $notify = $task->creator_id ?? $project->manager_id;
            if ($notify) {
                $this->notifier->send($notify, 'task_status_changed', 'Status Task Berubah', "Task \"{$task->title}\" berubah dari {$old} ke {$task->status}.", ['task_id' => $task->id]);
            }
            if ($task->status === 'done') {
                $this->teamNotifier->notify($project, '✅ Task Selesai', "\"{$task->title}\" ditandai selesai oleh ".auth()->user()->name.'.');
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
            'board_column_id' => ['required', Rule::exists('board_columns', 'id')->where('project_id', $project->id)],
            'notes' => 'nullable|string|max:2000',
        ]);

        $column = BoardColumn::findOrFail($request->board_column_id);
        $old = $task->status;
        $task->update([
            'board_column_id' => $column->id,
            'status' => $column->slug,
            'completion_notes' => $request->notes ?: null,
        ]);

        if ($old !== $task->status) {
            $notify = $task->created_by ?? $project->manager_id;
            if ($notify) {
                $this->notifier->send($notify, 'task_status_changed', 'Status Task Berubah',
                    "Task \"{$task->title}\" berubah dari {$old} ke {$task->status}.", ['task_id' => $task->id]);
            }
            if ($task->isDone()) {
                $this->teamNotifier->notify($project, '✅ Task Selesai', "\"{$task->title}\" ditandai selesai oleh ".auth()->user()->name.'.');
            }
        }

        return response()->json(['ok' => true, 'status' => $task->status, 'board_column_id' => $task->board_column_id]);
    }

    public function createMeeting(Project $project, Task $task, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForTask($task, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
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
