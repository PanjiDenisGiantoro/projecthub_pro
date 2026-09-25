<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;

class SprintWebController extends Controller
{
    use HasPerPage;

    public function index(Project $project, Request $request)
    {
        $sprints = $project->sprints()->with(['tasks.assignee', 'milestone'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->orderByDesc('start_date')
            ->paginate($this->perPage($request), ['*'], 'sprints_page')->withQueryString();
        $backlog = $project->tasks()->whereNull('sprint_id')->with('assignee', 'milestone')->orderBy('sort_order')
            ->paginate($this->perPage($request, 10, 'backlog_per_page'), ['*'], 'backlog_page')->withQueryString();
        $activeSprint = $project->sprints()->where('status', 'active')->first();
        $milestones = $project->milestones()->get();

        return view('sprints.index', compact('project', 'sprints', 'backlog', 'activeSprint', 'milestones'));
    }

    public function allSprints(Request $request)
    {
        $authUser = auth()->user();
        $companyId = $authUser->company_id;

        $companyScope = function ($q) use ($authUser, $companyId) {
            if (! $authUser->is_super_admin && $companyId) {
                $q->whereHas('project', fn ($p) => $p->where('company_id', $companyId));
            }
        };

        $query = Sprint::with(['project', 'milestone'])
            ->tap($companyScope)
            ->when($request->status, fn ($q) => $q->where('status', $request->status));

        if ($authUser->hasRole('client')) {
            $query->whereHas('project', fn ($p) => $p->where('client_id', $authUser->id));
        }

        $sprints = $query->orderByDesc('start_date')->paginate($this->perPage($request))->withQueryString();

        return view('sprints.all', compact('sprints'));
    }

    public function store(Request $request, Project $project, GoogleCalendarService $calendar)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'goal' => 'nullable|string',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:normal,low,high,urgent',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:not_started,planning,planned,active,in_progress,completed,at_risk',
        ]);

        $data['project_id'] = $project->id;
        $data['created_by'] = auth()->id();
        
        $statusMap = [
            'planning'    => 'planned',
            'planned'     => 'planned',
            'not_started' => 'planned',
            'active'      => 'active',
            'in_progress' => 'active',
            'at_risk'     => 'active',
            'completed'   => 'completed',
        ];
        $rawStatus = $data['status'] ?? 'planned';
        $data['status'] = $statusMap[$rawStatus] ?? 'planned';
        if (empty($data['priority'])) $data['priority'] = 'normal';

        $sprint = Sprint::create($data);

        if ($project->meeting_auto_create) {
            try {
                $calendar->createMeetingForSprint($sprint, auth()->user());
            } catch (\Throwable $e) {
                // silent — user tetap bisa klik "Buat Meeting" manual nanti
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sprint berhasil dibuat.',
                'sprint' => $sprint->load(['tasks', 'milestone', 'assignee']),
            ], 201);
        }

        return back()->with('success', 'Sprint berhasil dibuat.');
    }

    public function show(Project $project, Sprint $sprint)
    {
        $sprint->load([
            'tasks' => fn ($q) => $q->with([
                'assignee',
                'members',
                'labels',
                'checklists.items',
                'attachments',
                'milestone',
                'boardColumn',
            ])->withCount(['comments', 'attachments'])->orderBy('sort_order')
        ]);

        $columns = $project->boardColumns()->orderBy('sort_order')->get();

        // Seed default labels if none exist
        if ($project->labels()->count() === 0) {
            $defaultLabels = [
                ['name' => 'Feature', 'color' => 'blue'],
                ['name' => 'Bug', 'color' => 'red'],
                ['name' => 'Urgent', 'color' => 'orange'],
                ['name' => 'Design', 'color' => 'purple'],
                ['name' => 'Backend', 'color' => 'teal'],
            ];
            foreach ($defaultLabels as $l) {
                $project->labels()->create($l);
            }
        }

        $projectLabels = $project->labels()->orderBy('name')->get();
        $assignableUsers = $project->members()->with('user')->get()->pluck('user')->push($project->manager)->filter()->unique('id')->values();
        $milestones = $project->milestones()->get();

        return view('sprints.show', compact('project', 'sprint', 'columns', 'projectLabels', 'assignableUsers', 'milestones'));
    }

    public function update(Request $request, Project $project, Sprint $sprint, GoogleCalendarService $calendar)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50',
            'goal' => 'nullable|string',
            'milestone_id' => 'nullable|exists:milestones,id',
            'assigned_to' => 'nullable|exists:users,id',
            'priority' => 'nullable|in:normal,low,high,urgent',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:not_started,planning,planned,active,in_progress,completed,at_risk',
        ]);

        $statusMap = [
            'planning'    => 'planned',
            'planned'     => 'planned',
            'not_started' => 'planned',
            'active'      => 'active',
            'in_progress' => 'active',
            'at_risk'     => 'active',
            'completed'   => 'completed',
        ];
        $data['status'] = $statusMap[$data['status'] ?? 'planned'] ?? 'planned';

        // Only one sprint can be active at a time
        if (in_array($data['status'], ['active', 'in_progress'])) {
            $project->sprints()->where('id', '!=', $sprint->id)->whereIn('status', ['active', 'in_progress'])->update(['status' => 'completed']);
        }

        $sprint->update($data);

        if ($sprint->wasChanged('start_date') && $sprint->google_event_id) {
            try {
                $calendar->syncMeetingTime($sprint, auth()->user());
            } catch (\Throwable $e) {
                // silent — jadwal Google Calendar tetap yang lama, tidak blokir update sprint
            }
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sprint berhasil diperbarui.',
                'sprint' => $sprint->load(['tasks', 'milestone', 'assignee']),
            ]);
        }

        return back()->with('success', 'Sprint berhasil diperbarui.');
    }

    public function destroy(Project $project, Sprint $sprint)
    {
        // Move tasks back to backlog
        $sprint->tasks()->update(['sprint_id' => null]);
        $sprint->delete();

        if (request()->wantsJson() || request()->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Sprint berhasil dihapus.',
            ]);
        }

        return back()->with('success', 'Sprint berhasil dihapus.');
    }

    public function assignTasks(Request $request, Project $project, Sprint $sprint)
    {
        $request->validate([
            'task_ids' => 'nullable|array',
            'task_ids.*' => 'exists:tasks,id',
        ]);

        // Reset all tasks in this sprint first
        Task::where('sprint_id', $sprint->id)->update(['sprint_id' => null]);

        // Re-assign selected tasks
        if ($request->task_ids) {
            $updateData = ['sprint_id' => $sprint->id];
            if ($sprint->milestone_id) {
                $updateData['milestone_id'] = $sprint->milestone_id;
            }
            Task::whereIn('id', $request->task_ids)
                ->where('project_id', $project->id)
                ->update($updateData);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Task assignments berhasil diperbarui.',
            ]);
        }

        return back()->with('success', 'Task assignments berhasil diperbarui.');
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

    public function createMeeting(Project $project, Sprint $sprint, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForSprint($sprint, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
    }

    public function createStandup(Project $project, Sprint $sprint, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createRecurringMeetingForSprint($sprint, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat standup harian. Silakan coba lagi.');
        }

        return back()->with('success', 'Standup harian berhasil dijadwalkan.');
    }
}
