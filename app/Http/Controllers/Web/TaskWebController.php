<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\BoardColumn;
use App\Models\Label;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\RecurringTaskDefinition;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TaskChecklist;
use App\Models\TaskChecklistItem;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use App\Services\TeamNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class TaskWebController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier, private TeamNotifier $teamNotifier) {}

    public function index(Request $request, Project $project)
    {
        $query = $project->tasks()->with([
            'assignee',
            'members',
            'labels',
            'checklists.items',
            'attachments',
            'milestone',
            'boardColumn',
        ])->withCount(['comments', 'attachments'])
          ->when($request->status, fn ($q) => $q->where('status', $request->status));

        $tasks = $query->orderBy('sort_order')->paginate($this->perPage($request))->withQueryString();
        $milestones = $project->milestones()->get();
        $developers = User::role('member')->where('is_active', true)->where('company_id', $project->company_id)->get();
        $columns = $project->boardColumns()->orderBy('sort_order')->get();
        $projectLabels = $project->labels()->orderBy('name')->get();
        $assignableUsers = $project->members()->with('user')->get()->pluck('user')->push($project->manager)->filter()->unique('id')->values();

        return view('tasks.index', compact('project', 'tasks', 'milestones', 'developers', 'columns', 'projectLabels', 'assignableUsers'));
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

        $query = Task::with(['project', 'assignee', 'sprint', 'milestone'])
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
            'sprint_id' => 'nullable|exists:sprints,id',
            'board_column_id' => 'nullable|exists:board_columns,id',
            'start_date' => 'nullable|date',
            'due_date' => 'nullable|date',
            'priority' => 'nullable|in:low,medium,high,urgent,critical',
            'estimated_hours' => 'nullable|integer|min:0',
        ]);

        $column = null;
        if ($request->filled('board_column_id')) {
            $column = BoardColumn::where('project_id', $project->id)->find($request->board_column_id);
        }
        if (!$column) {
            $column = BoardColumn::where('project_id', $project->id)
                ->where('is_done', false)
                ->orderBy('sort_order')
                ->first();
        }

        $nextSort = (int) $project->tasks()->where('board_column_id', $column?->id)->max('sort_order') + 1;

        $assignedTo = $request->assigned_to;
        $memberIds = $request->input('member_ids', $request->input('members', []));
        if (is_array($memberIds)) {
            $memberIds = array_values(array_filter(array_map('intval', $memberIds)));
            if (!$assignedTo && count($memberIds) > 0) {
                $assignedTo = $memberIds[0];
            }
        }

        $startDate = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->format('Y-m-d') : null;
        $dueDate = $request->filled('due_date') ? \Carbon\Carbon::parse($request->due_date)->format('Y-m-d') : null;

        $task = $project->tasks()->create([
            ...$request->only('title', 'description', 'milestone_id', 'sprint_id', 'estimated_hours', 'cover_image_path'),
            'start_date' => $startDate,
            'due_date' => $dueDate,
            'assigned_to' => $assignedTo,
            'priority' => $request->priority ?: 'medium',
            'status' => $column?->slug ?? 'todo',
            'board_column_id' => $column?->id ?? null,
            'sort_order' => $nextSort,
            'created_by' => auth()->id(),
        ]);

        // 1. Assignee & Members sync
        if (is_array($memberIds)) {
            $this->syncTaskMembers($task, $memberIds, $assignedTo);
        }

        // 2. Labels sync
        if ($request->has('label_ids') || $request->has('labels')) {
            $this->syncTaskLabels($task, $request->input('label_ids', $request->input('labels', [])));
        }

        // 3. Cover image handling
        $this->handleTaskCover($task, $request);

        // 4. Repeat (recurring definition)
        $this->syncTaskRecurring($task, $project, $request);

        // 5. Checklists / DoD
        if ($request->has('checklists')) {
            $this->syncTaskChecklists($task, $request->input('checklists', []));
        } elseif ($request->has('dods') || $request->has('dod_items')) {
            $dodItems = $request->input('dods', $request->input('dod_items', []));
            $this->syncTaskChecklists($task, [
                [
                    'title' => 'Definition of Done',
                    'items' => array_map(fn($t) => ['title' => is_array($t) ? ($t['title'] ?? '') : (string) $t, 'is_done' => false], $dodItems)
                ]
            ]);
        }

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

        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            $task->load(['assignee', 'members', 'labels', 'checklists.items', 'attachments', 'recurringDefinition', 'boardColumn']);
            $cardHtml = view('sprints._kanban_card', ['task' => $task, 'col' => $task->boardColumn])->render();
            return response()->json([
                'ok' => true,
                'message' => 'Task berhasil dibuat.',
                'task' => $task,
                'card_html' => $cardHtml,
            ], 201);
        }

        return back()->with('success', 'Task berhasil dibuat.');
    }

    public function show(Project $project, Task $task)
    {
        $task->load(['assignee', 'milestone', 'creator', 'timeLogs.user', 'comments.user', 'comments.attachments']);
        $runningLog = $task->timeLogs()->where('user_id', auth()->id())->where('is_running', true)->first();
        // Cuma hitung jumlah di sini (buat badge) — daftar log lengkap (dengan relasi
        // causer) baru di-load lewat logs() saat panel riwayat dibuka user (lazy load),
        // biar halaman detail task tidak ikut berat setiap kali dibuka.
        $logsCount = $task->activitiesAsSubject()->count();
        $developers = User::role('member')->where('is_active', true)->where('company_id', $project->company_id)->get();

        return view('tasks.show', compact('project', 'task', 'runningLog', 'logsCount', 'developers'));
    }

    public function addComment(Request $request, Project $project, Task $task)
    {
        $request->validate([
            'body'            => 'required|string|max:2000',
            'attachments'     => 'nullable|array|max:5',
            'attachments.*'   => 'file|max:10240',
        ]);

        $comment = $task->comments()->create(['user_id' => auth()->id(), 'body' => $request->body]);

        foreach ($request->file('attachments', []) as $file) {
            $path = $file->store("task-comment-attachments/{$comment->id}", 'public');
            $comment->attachments()->create([
                'file_name' => $file->getClientOriginalName(),
                'file_path' => $path,
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
            ]);
        }

        $notify = collect([$task->assigned_to, $task->created_by])
            ->filter()
            ->unique()
            ->reject(fn ($id) => $id === auth()->id());

        foreach ($notify as $userId) {
            $this->notifier->send($userId, 'task_comment_added', 'Komentar Baru',
                auth()->user()->name . " berkomentar di task \"{$task->title}\".", ['task_id' => $task->id]);
        }

        return back()->with('success', 'Komentar ditambahkan.');
    }

    public function logs(Project $project, Task $task)
    {
        $logs = $task->activitiesAsSubject()->with('causer')->latest()->limit(50)->get();

        return view('tasks._logs', compact('logs'));
    }

    public function update(Request $request, Project $project, Task $task, GoogleCalendarService $calendar)
    {
        $request->validate([
            'completion_notes' => 'nullable|string|max:2000',
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where('company_id', $project->company_id)],
            'milestone_id' => 'nullable|exists:milestones,id',
            'sprint_id' => 'nullable|exists:sprints,id',
            'priority' => 'nullable|in:low,medium,high,urgent,critical',
            'estimated_hours' => 'nullable|integer|min:0',
        ]);

        $old = $task->status;
        $data = $request->only(
            'title',
            'description',
            'completion_notes',
            'assigned_to',
            'milestone_id',
            'sprint_id',
            'status',
            'priority',
            'start_date',
            'due_date',
            'estimated_hours',
            'board_column_id',
            'cover_image_path',
            'recurring_definition_id'
        );

        if ($request->has('start_date')) {
            $data['start_date'] = $request->filled('start_date') ? \Carbon\Carbon::parse($request->start_date)->format('Y-m-d') : null;
        }
        if ($request->has('due_date')) {
            $data['due_date'] = $request->filled('due_date') ? \Carbon\Carbon::parse($request->due_date)->format('Y-m-d') : null;
        }

        if ($request->filled('board_column_id') && !$request->filled('status')) {
            $col = BoardColumn::where('project_id', $project->id)->find($request->board_column_id);
            if ($col) {
                $data['status'] = $col->slug;
            }
        } elseif ($request->filled('status') && $request->status !== $old && !$request->filled('board_column_id')) {
            $column = BoardColumn::where('project_id', $project->id)->where('slug', $request->status)->first();
            $data['board_column_id'] = $column->id ?? $task->board_column_id;
        }

        $task->update($data);

        // 1. Assignee & Members sync
        if ($request->has('member_ids') || $request->has('members') || $request->has('assigned_to')) {
            $memberIds = $request->input('member_ids', $request->input('members', []));
            $assignedTo = $request->has('assigned_to') ? $request->input('assigned_to') : null;
            $this->syncTaskMembers($task, (array) $memberIds, $assignedTo);
        }

        // 2. Labels sync
        if ($request->has('label_ids') || $request->has('labels')) {
            $this->syncTaskLabels($task, $request->input('label_ids', $request->input('labels', [])));
        }

        // 3. Cover image handling
        $this->handleTaskCover($task, $request);

        // 4. Repeat (recurring definition)
        $this->syncTaskRecurring($task, $project, $request);

        // 5. Checklists / DoD
        if ($request->has('checklists')) {
            $this->syncTaskChecklists($task, $request->input('checklists', []));
        }

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

        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            $freshTask = $task->fresh(['assignee', 'members', 'labels', 'checklists.items', 'attachments', 'recurringDefinition', 'boardColumn', 'milestone', 'creator']);
            $cardHtml = view('sprints._kanban_card', ['task' => $freshTask, 'col' => $freshTask->boardColumn])->render();
            return response()->json([
                'ok' => true,
                'message' => 'Task diperbarui.',
                'task' => $freshTask,
                'card_html' => $cardHtml,
            ]);
        }

        return back()->with('success', 'Task diperbarui.');
    }

    public function uploadCover(Request $request, Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        if ($request->hasFile('cover') || $request->hasFile('cover_image')) {
            $request->validate(['cover' => 'nullable|image|max:5120', 'cover_image' => 'nullable|image|max:5120']);

            if ($task->cover_image_path) {
                Storage::disk('public')->delete($task->cover_image_path);
            }

            $file = $request->file('cover') ?: $request->file('cover_image');
            $path = $file->store("task-covers/{$task->id}", 'public');
            $task->update(['cover_image_path' => $path]);

            if (function_exists('activity')) {
                activity('task')
                    ->performedOn($task)
                    ->causedBy(auth()->user())
                    ->withProperties(['cover_path' => $path])
                    ->log('cover_updated');
            }

            return response()->json([
                'ok' => true,
                'cover_image_path' => $path,
                'url' => Storage::url($path),
            ]);
        }

        // Remove cover
        if ($task->cover_image_path) {
            Storage::disk('public')->delete($task->cover_image_path);
        }
        $task->update(['cover_image_path' => null]);

        if (function_exists('activity')) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->log('cover_removed');
        }

        return response()->json(['ok' => true, 'cover_image_path' => null, 'url' => null]);
    }

    /**
     * Synchronize assignee & members for a task.
     */
    protected function syncTaskMembers(Task $task, array $memberIds, ?int $assignedTo = null): void
    {
        $oldMemberIds = $task->members()->pluck('users.id')->sort()->values()->all();
        $memberIds = array_values(array_filter(array_map('intval', $memberIds)));

        if ($assignedTo && !in_array($assignedTo, $memberIds)) {
            $memberIds[] = $assignedTo;
        }

        $task->members()->sync($memberIds);
        $newMemberIds = collect($memberIds)->sort()->values()->all();

        if ($oldMemberIds !== $newMemberIds) {
            $memberNames = User::whereIn('id', $newMemberIds)->pluck('name')->implode(', ');
            if (function_exists('activity')) {
                activity('task')
                    ->performedOn($task)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'member_names' => $memberNames,
                        'count' => count($newMemberIds),
                    ])
                    ->log('members_updated');
            }
        }

        if ($assignedTo) {
            $task->update(['assigned_to' => $assignedTo]);
        } elseif (!empty($memberIds)) {
            if (!$task->assigned_to || !in_array($task->assigned_to, $memberIds)) {
                $task->update(['assigned_to' => $memberIds[0]]);
            }
        } else {
            $task->update(['assigned_to' => null]);
        }
    }

    /**
     * Synchronize labels attached to a task.
     */
    protected function syncTaskLabels(Task $task, $labels): void
    {
        if (!is_array($labels)) {
            return;
        }

        $oldLabelIds = $task->labels()->pluck('labels.id')->sort()->values()->all();
        $labelIds = collect($labels)->map(function ($item) {
            if (is_numeric($item)) return (int) $item;
            if (is_array($item) && isset($item['id'])) return (int) $item['id'];
            if (is_object($item) && isset($item->id)) return (int) $item->id;
            return null;
        })->filter()->unique()->sort()->values()->all();

        $task->labels()->sync($labelIds);

        if ($oldLabelIds !== $labelIds) {
            $labelNames = Label::whereIn('id', $labelIds)->pluck('name')->implode(', ');
            if (function_exists('activity')) {
                activity('task')
                    ->performedOn($task)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'label_names' => $labelNames,
                        'count' => count($labelIds),
                    ])
                    ->log('labels_updated');
            }
        }
    }

    /**
     * Synchronize recurring definition rule for a task.
     */
    protected function syncTaskRecurring(Task $task, Project $project, Request $request): void
    {
        $frequency = $request->input('recurring_frequency');
        $recurringDays = $request->input('recurring_days', []);
        $recurringDefId = $request->input('recurring_definition_id');

        // Explicitly deactivated / none
        if ($frequency === 'none' || ($request->has('recurring_definition_id') && empty($recurringDefId) && empty($frequency))) {
            if ($task->recurring_definition_id) {
                RecurringTaskDefinition::where('id', $task->recurring_definition_id)->update(['is_active' => false]);
                $task->update(['recurring_definition_id' => null]);

                if (function_exists('activity')) {
                    activity('task')
                        ->performedOn($task)
                        ->causedBy(auth()->user())
                        ->withProperties(['recurring' => 'none'])
                        ->log('recurring_removed');
                }
            }
            return;
        }

        if ($frequency && in_array($frequency, ['daily', 'weekly', 'biweekly', 'monthly', 'custom'])) {
            $dayMap = [
                'min' => 0, 'sun' => 0, '0' => 0,
                'sen' => 1, 'mon' => 1, '1' => 1,
                'sel' => 2, 'tue' => 2, '2' => 2,
                'rab' => 3, 'wed' => 3, '3' => 3,
                'kam' => 4, 'thu' => 4, '4' => 4,
                'jum' => 5, 'fri' => 5, '5' => 5,
                'sab' => 6, 'sat' => 6, '6' => 6,
            ];

            $dayOfWeek = 1;
            if (is_array($recurringDays) && count($recurringDays) > 0) {
                $firstKey = strtolower(trim((string) $recurringDays[0]));
                if (isset($dayMap[$firstKey])) {
                    $dayOfWeek = $dayMap[$firstKey];
                }
            } elseif (is_string($recurringDays) && isset($dayMap[strtolower(trim($recurringDays))])) {
                $dayOfWeek = $dayMap[strtolower(trim($recurringDays))];
            }

            $validFreq = in_array($frequency, ['daily', 'weekly', 'biweekly', 'monthly']) ? $frequency : 'weekly';
            $priority = in_array($task->priority, ['low', 'medium', 'high', 'critical', 'urgent'])
                ? ($task->priority === 'urgent' ? 'critical' : $task->priority)
                : 'medium';

            if ($task->recurring_definition_id && ($existing = RecurringTaskDefinition::find($task->recurring_definition_id))) {
                $existing->update([
                    'title' => $task->title,
                    'description' => $task->description,
                    'assigned_to' => $task->assigned_to,
                    'milestone_id' => $task->milestone_id,
                    'frequency' => $validFreq,
                    'day_of_week' => $dayOfWeek,
                    'priority' => $priority,
                    'estimated_hours' => $task->estimated_hours ?: 0,
                    'is_active' => true,
                ]);
            } else {
                $def = RecurringTaskDefinition::create([
                    'project_id' => $project->id,
                    'milestone_id' => $task->milestone_id,
                    'title' => $task->title,
                    'description' => $task->description,
                    'assigned_to' => $task->assigned_to,
                    'frequency' => $validFreq,
                    'day_of_week' => $dayOfWeek,
                    'day_of_month' => 1,
                    'priority' => $priority,
                    'estimated_hours' => $task->estimated_hours ?: 0,
                    'due_offset_days' => 1,
                    'is_active' => true,
                    'created_by' => auth()->id(),
                ]);
                $task->update(['recurring_definition_id' => $def->id]);
            }

            if (function_exists('activity')) {
                activity('task')
                    ->performedOn($task)
                    ->causedBy(auth()->user())
                    ->withProperties([
                        'frequency' => $validFreq,
                        'days' => $recurringDays,
                    ])
                    ->log('recurring_updated');
            }
        } elseif ($recurringDefId && RecurringTaskDefinition::where('project_id', $project->id)->where('id', $recurringDefId)->exists()) {
            $task->update(['recurring_definition_id' => $recurringDefId]);
        }
    }

    /**
     * Synchronize DoD / Checklist groups and items for a task.
     */
    protected function syncTaskChecklists(Task $task, $checklists): void
    {
        if (!is_array($checklists)) {
            return;
        }

        $changed = false;
        foreach ($checklists as $cIndex => $clData) {
            if (!is_array($clData)) continue;

            $groupId = $clData['id'] ?? null;
            $title = trim($clData['title'] ?? 'Definition of Done') ?: 'Definition of Done';

            if ($groupId && is_numeric($groupId)) {
                $group = $task->checklists()->find($groupId);
                if ($group) {
                    $group->update(['title' => $title, 'sort_order' => $cIndex + 1]);
                } else {
                    $group = $task->checklists()->create(['title' => $title, 'sort_order' => $cIndex + 1]);
                    $changed = true;
                }
            } else {
                $group = $task->checklists()->create(['title' => $title, 'sort_order' => $cIndex + 1]);
                $changed = true;
            }

            if (isset($clData['items']) && is_array($clData['items'])) {
                foreach ($clData['items'] as $iIndex => $itemData) {
                    if (!is_array($itemData)) continue;
                    $itemId = $itemData['id'] ?? null;
                    $itemTitle = trim($itemData['title'] ?? '');
                    if (!$itemTitle) continue;
                    $isDone = !empty($itemData['is_done']);

                    if ($itemId && is_numeric($itemId)) {
                        $item = $group->items()->find($itemId);
                        if ($item) {
                            $wasDone = (bool) $item->is_done;
                            $wasTitle = $item->title;
                            if ($wasDone !== $isDone || $wasTitle !== $itemTitle) {
                                $changed = true;
                            }
                            $item->update([
                                'title' => $itemTitle,
                                'is_done' => $isDone,
                                'sort_order' => $iIndex + 1,
                                'completed_at' => ($isDone && !$wasDone) ? now() : ($isDone ? $item->completed_at : null),
                                'completed_by' => ($isDone && !$wasDone) ? auth()->id() : ($isDone ? $item->completed_by : null),
                            ]);
                            continue;
                        }
                    }

                    $group->items()->create([
                        'title' => $itemTitle,
                        'is_done' => $isDone,
                        'sort_order' => $iIndex + 1,
                        'completed_at' => $isDone ? now() : null,
                        'completed_by' => $isDone ? auth()->id() : null,
                    ]);
                    $changed = true;
                }
            }
        }

        if ($changed && function_exists('activity')) {
            activity('task')
                ->performedOn($task)
                ->causedBy(auth()->user())
                ->log('checklists_updated');
        }
    }

    /**
     * Handle task cover file upload or path update.
     */
    protected function handleTaskCover(Task $task, Request $request): void
    {
        if ($request->hasFile('cover') || $request->hasFile('cover_image')) {
            $file = $request->file('cover') ?: $request->file('cover_image');
            if ($file && $file->isValid()) {
                if ($task->cover_image_path) {
                    Storage::disk('public')->delete($task->cover_image_path);
                }
                $path = $file->store("task-covers/{$task->id}", 'public');
                $task->update(['cover_image_path' => $path]);

                if (function_exists('activity')) {
                    activity('task')
                        ->performedOn($task)
                        ->causedBy(auth()->user())
                        ->withProperties(['cover_path' => $path])
                        ->log('cover_updated');
                }
            }
        } elseif ($request->has('cover_image_path')) {
            $coverPath = $request->input('cover_image_path');
            if (empty($coverPath)) {
                if ($task->cover_image_path) {
                    Storage::disk('public')->delete($task->cover_image_path);
                }
                $task->update(['cover_image_path' => null]);

                if (function_exists('activity')) {
                    activity('task')
                        ->performedOn($task)
                        ->causedBy(auth()->user())
                        ->log('cover_removed');
                }
            } else {
                $task->update(['cover_image_path' => $coverPath]);
            }
        }
    }


    public function destroy(Request $request, Project $project, Task $task)
    {
        $task->delete();

        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json(['ok' => true, 'message' => 'Task berhasil dihapus.']);
        }

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

    /**
     * Reorder cards within a bucket (persist sort_order).
     * Accepts: { order: [taskId1, taskId2, ...], board_column_id: X }
     */
    public function reorderCards(Request $request, Project $project)
    {
        $this->authorize('view', $project);

        $request->validate([
            'order'          => 'required|array',
            'order.*'        => 'integer',
            'board_column_id'=> 'required|integer|exists:board_columns,id',
        ]);

        foreach ($request->order as $index => $taskId) {
            $project->tasks()
                ->where('id', $taskId)
                ->where('board_column_id', $request->board_column_id)
                ->update(['sort_order' => $index]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Return full task data as JSON for the modal.
     */
    public function detail(Project $project, Task $task)
    {
        $this->authorize('view', $project);
        abort_if($task->project_id !== $project->id, 404);

        $task->load([
            'assignee',
            'milestone',
            'creator',
            'boardColumn',
            'members',
            'labels',
            'checklists.items',
            'attachments.creator',
            'comments.user',
            'comments.attachments',
            'recurringDefinition',
            'sprint',
        ]);

        if ($task->members->isEmpty() && $task->assignee) {
            $task->setRelation('members', collect([$task->assignee]));
        }

        $logsCount = $task->activitiesAsSubject()->count();

        // Load rich activities for Activity & Comments tab
        $rawLogs = $task->activitiesAsSubject()
            ->with('causer')
            ->latest()
            ->limit(50)
            ->get();

        // Collect foreign keys to resolve
        $userIds = collect();
        $columnIds = collect();
        $milestoneIds = collect();
        $sprintIds = collect();
        foreach ($rawLogs as $log) {
            $attrs = $log->attribute_changes['attributes'] ?? $log->properties['attributes'] ?? [];
            if (isset($attrs['assigned_to'])) $userIds->push($attrs['assigned_to']);
            if (isset($attrs['board_column_id'])) $columnIds->push($attrs['board_column_id']);
            if (isset($attrs['milestone_id'])) $milestoneIds->push($attrs['milestone_id']);
            if (isset($attrs['sprint_id'])) $sprintIds->push($attrs['sprint_id']);
        }
        $userNames = \App\Models\User::whereIn('id', $userIds->filter()->unique())->pluck('name', 'id');
        $columnNames = \App\Models\BoardColumn::whereIn('id', $columnIds->filter()->unique())->pluck('name', 'id');
        $milestoneNames = \App\Models\Milestone::whereIn('id', $milestoneIds->filter()->unique())->pluck('title', 'id');
        $sprintNames = \App\Models\Sprint::whereIn('id', $sprintIds->filter()->unique())->pluck('name', 'id');

        $priorityLabels = [
            'urgent' => 'Urgent',
            'high' => 'Important',
            'medium' => 'Medium',
            'low' => 'Low',
        ];
        $statusLabels = [
            'todo' => 'To Do',
            'in_progress' => 'In Progress',
            'review' => 'Review',
            'done' => 'Completed',
        ];

        $activities = $rawLogs->map(function ($log) use ($userNames, $columnNames, $milestoneNames, $sprintNames, $priorityLabels, $statusLabels) {
            $causer = $log->causer?->name ?? 'System';
            $old = $log->attribute_changes['old'] ?? [];
            $rawAttrs = $log->attribute_changes['attributes'] ?? $log->properties['attributes'] ?? [];
            $props = $log->properties ?? [];
            $desc = $log->description;
            $text = '';

            // Filter to actual changed attributes only
            $attrs = [];
            if (!empty($old) && is_array($old)) {
                foreach ($rawAttrs as $k => $v) {
                    if (in_array($k, ['updated_at', 'created_at', 'id', 'project_id', 'sort_order'])) continue;
                    if (!array_key_exists($k, $old) || $old[$k] !== $v) {
                        $attrs[$k] = $v;
                    }
                }
            } else {
                foreach ($rawAttrs as $k => $v) {
                    if (!in_array($k, ['updated_at', 'created_at', 'id', 'project_id', 'sort_order'])) {
                        $attrs[$k] = $v;
                    }
                }
            }

            // 1. Explicit Custom Log Events
            if ($desc === 'created') {
                $text = "membuat task ini.";
            } elseif ($desc === 'deleted') {
                $text = "menghapus task.";
            } elseif ($desc === 'recurring_updated') {
                $freq = $props['frequency'] ?? 'weekly';
                $freqMap = ['daily' => 'Harian', 'weekly' => 'Mingguan', 'biweekly' => '2 Mingguan', 'monthly' => 'Bulanan', 'custom' => 'Kustom'];
                $freqLabel = $freqMap[$freq] ?? ucfirst($freq);
                $days = !empty($props['days']) ? ' (' . (is_array($props['days']) ? implode(', ', $props['days']) : $props['days']) . ')' : '';
                $text = "mengatur jadwal berulang (Repeat): <span class=\"font-semibold text-indigo-600 dark:text-indigo-400\">{$freqLabel}{$days}</span>.";
            } elseif ($desc === 'recurring_removed') {
                $text = "menonaktifkan jadwal berulang (Repeat).";
            } elseif ($desc === 'checklist_group_created') {
                $gTitle = e($props['group_title'] ?? 'Definition of Done');
                $text = "menambahkan grup checklist <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$gTitle}\"</span>.";
            } elseif ($desc === 'checklist_group_updated') {
                $gTitle = e($props['group_title'] ?? 'Definition of Done');
                $text = "mengubah nama grup checklist menjadi <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$gTitle}\"</span>.";
            } elseif ($desc === 'checklist_group_deleted') {
                $gTitle = e($props['group_title'] ?? 'Definition of Done');
                $text = "menghapus grup checklist <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$gTitle}\"</span>.";
            } elseif ($desc === 'checklist_item_created') {
                $iTitle = e($props['item_title'] ?? 'Item');
                $text = "menambahkan item checklist <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$iTitle}\"</span>.";
            } elseif ($desc === 'checklist_item_completed') {
                $iTitle = e($props['item_title'] ?? 'Item');
                $text = "menyelesaikan item Definition of Done <span class=\"font-semibold text-emerald-600 dark:text-emerald-400\">\"{$iTitle}\"</span>.";
            } elseif ($desc === 'checklist_item_uncompleted') {
                $iTitle = e($props['item_title'] ?? 'Item');
                $text = "membatalkan penyelesaian item Definition of Done <span class=\"font-semibold text-slate-700 dark:text-slate-300\">\"{$iTitle}\"</span>.";
            } elseif ($desc === 'checklist_item_updated') {
                $iTitle = e($props['item_title'] ?? 'Item');
                $text = "memperbarui item Definition of Done <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$iTitle}\"</span>.";
            } elseif ($desc === 'checklist_item_deleted') {
                $iTitle = e($props['item_title'] ?? 'Item');
                $text = "menghapus item Definition of Done <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$iTitle}\"</span>.";
            } elseif ($desc === 'checklists_updated') {
                $text = "memperbarui daftar Definition of Done.";
            } elseif ($desc === 'cover_updated') {
                $text = "mengunggah / memperbarui gambar cover task.";
            } elseif ($desc === 'cover_removed') {
                $text = "menghapus gambar cover task.";
            } elseif ($desc === 'labels_updated') {
                $lNames = e($props['label_names'] ?? '');
                if ($lNames) {
                    $text = "memperbarui label task: <span class=\"font-semibold text-indigo-600 dark:text-indigo-400\">{$lNames}</span>.";
                } else {
                    $text = "menghapus semua label task.";
                }
            } elseif ($desc === 'member_added') {
                $uName = e($props['user_name'] ?? 'Anggota');
                $text = "menambahkan <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$uName}</span> ke anggota task.";
            } elseif ($desc === 'member_removed') {
                $uName = e($props['user_name'] ?? 'Anggota');
                $text = "menghapus <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$uName}</span> dari anggota task.";
            } elseif ($desc === 'members_updated') {
                $mNames = e($props['member_names'] ?? '');
                if ($mNames) {
                    $text = "memperbarui anggota task: <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$mNames}</span>.";
                } else {
                    $text = "menghapus semua anggota task.";
                }
            } elseif ($desc === 'attachment_added') {
                $fName = e($props['file_name'] ?? 'Lampiran');
                $type = ($props['type'] ?? 'file') === 'link' ? 'tautan' : 'lampiran';
                $text = "menambahkan {$type} <span class=\"font-semibold text-blue-600 dark:text-blue-400\">\"{$fName}\"</span>.";
            } elseif ($desc === 'attachment_deleted') {
                $fName = e($props['file_name'] ?? 'Lampiran');
                $text = "menghapus lampiran <span class=\"font-semibold text-slate-800 dark:text-slate-200\">\"{$fName}\"</span>.";
            }
            // 2. Automatic Spatie Model Column Changes
            else {
                $changes = [];

                if (array_key_exists('status', $attrs)) {
                    $sLabel = $statusLabels[$attrs['status']] ?? ucfirst(str_replace('_', ' ', (string)$attrs['status']));
                    $changes[] = "mengubah status ke <span class=\"font-semibold text-blue-600 dark:text-blue-400\">{$sLabel}</span>";
                }
                if (array_key_exists('board_column_id', $attrs) && !array_key_exists('status', $attrs)) {
                    $colName = $columnNames[$attrs['board_column_id']] ?? "Kolom #{$attrs['board_column_id']}";
                    $changes[] = "menetapkan bucket ke <span class=\"font-semibold text-blue-600 dark:text-blue-400\">{$colName}</span>";
                }
                if (array_key_exists('priority', $attrs)) {
                    $pLabel = $priorityLabels[$attrs['priority']] ?? ucfirst((string)$attrs['priority']);
                    $color = $attrs['priority'] === 'urgent' ? 'text-red-600 font-bold' : ($attrs['priority'] === 'high' ? 'text-amber-600 font-bold' : 'text-blue-600 font-medium');
                    $changes[] = "mengubah prioritas menjadi <span class=\"{$color}\">! {$pLabel}</span>";
                }
                if (array_key_exists('assigned_to', $attrs)) {
                    if (empty($attrs['assigned_to'])) {
                        $changes[] = "menghapus penugasan task (Unassigned)";
                    } else {
                        $uName = $userNames[$attrs['assigned_to']] ?? "User #{$attrs['assigned_to']}";
                        $changes[] = "menugaskan task kepada <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$uName}</span>";
                    }
                }
                if (array_key_exists('cover_image_path', $attrs)) {
                    if (empty($attrs['cover_image_path'])) {
                        $changes[] = "menghapus gambar cover task";
                    } else {
                        $changes[] = "mengunggah gambar cover task";
                    }
                }
                if (array_key_exists('recurring_definition_id', $attrs)) {
                    if (empty($attrs['recurring_definition_id'])) {
                        $changes[] = "menonaktifkan jadwal berulang (Repeat)";
                    } else {
                        $changes[] = "mengaktifkan jadwal berulang (Repeat)";
                    }
                }
                if (array_key_exists('due_date', $attrs) && array_key_exists('start_date', $attrs)) {
                    try { $dDue = \Carbon\Carbon::parse($attrs['due_date'])->translatedFormat('d M Y'); } catch (\Throwable) { $dDue = (string)$attrs['due_date']; }
                    try { $dStart = \Carbon\Carbon::parse($attrs['start_date'])->translatedFormat('d M Y'); } catch (\Throwable) { $dStart = (string)$attrs['start_date']; }
                    $changes[] = "memperbarui rentang waktu (<span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$dStart} - {$dDue}</span>)";
                } elseif (array_key_exists('due_date', $attrs)) {
                    if (empty($attrs['due_date'])) {
                        $changes[] = "menghapus tenggat waktu task";
                    } else {
                        try { $dDue = \Carbon\Carbon::parse($attrs['due_date'])->translatedFormat('d M Y'); } catch (\Throwable) { $dDue = (string)$attrs['due_date']; }
                        $changes[] = "memperbarui tenggat waktu menjadi <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$dDue}</span>";
                    }
                } elseif (array_key_exists('start_date', $attrs)) {
                    if (empty($attrs['start_date'])) {
                        $changes[] = "menghapus tanggal mulai task";
                    } else {
                        try { $dStart = \Carbon\Carbon::parse($attrs['start_date'])->translatedFormat('d M Y'); } catch (\Throwable) { $dStart = (string)$attrs['start_date']; }
                        $changes[] = "memperbarui tanggal mulai menjadi <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$dStart}</span>";
                    }
                }
                if (array_key_exists('milestone_id', $attrs)) {
                    if (empty($attrs['milestone_id'])) {
                        $changes[] = "menghapus milestone task";
                    } else {
                        $mTitle = $milestoneNames[$attrs['milestone_id']] ?? "Milestone #{$attrs['milestone_id']}";
                        $changes[] = "mengubah milestone ke <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$mTitle}</span>";
                    }
                }
                if (array_key_exists('sprint_id', $attrs)) {
                    if (empty($attrs['sprint_id'])) {
                        $changes[] = "menghapus sprint task";
                    } else {
                        $sTitle = $sprintNames[$attrs['sprint_id']] ?? "Sprint #{$attrs['sprint_id']}";
                        $changes[] = "mengubah sprint ke <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$sTitle}</span>";
                    }
                }
                if (array_key_exists('estimated_hours', $attrs)) {
                    $hours = (int) $attrs['estimated_hours'];
                    $changes[] = "mengubah estimasi waktu menjadi <span class=\"font-semibold text-slate-800 dark:text-slate-200\">{$hours} jam</span>";
                }
                if (array_key_exists('title', $attrs)) {
                    $changes[] = "mengubah judul task";
                }
                if (array_key_exists('description', $attrs)) {
                    $changes[] = "memperbarui deskripsi task";
                }
                if (array_key_exists('completion_notes', $attrs)) {
                    $changes[] = "menambahkan catatan penyelesaian";
                }

                if (!empty($changes)) {
                    $text = implode(', ', $changes) . '.';
                } else {
                    $text = $desc ?: "memperbarui task ini.";
                }
            }

            return [
                'id' => $log->id,
                'user_name' => $causer,
                'formatted_text' => $text,
                'created_at' => $log->created_at->toISOString(),
            ];
        });

        // Column task counts for Bucket Kanban Selector
        $columnCounts = Task::where('project_id', $project->id)
            ->whereNotNull('board_column_id')
            ->groupBy('board_column_id')
            ->selectRaw('board_column_id, count(*) as count')
            ->pluck('count', 'board_column_id');

        $taskArray = $task->toArray();
        $taskArray['start_date'] = $task->start_date ? $task->start_date->format('Y-m-d') : null;
        $taskArray['due_date'] = $task->due_date ? $task->due_date->format('Y-m-d') : null;

        return response()->json([
            'task'         => $taskArray,
            'logsCount'    => $logsCount,
            'activities'   => $activities,
            'columnCounts' => $columnCounts,
        ]);
    }
}
