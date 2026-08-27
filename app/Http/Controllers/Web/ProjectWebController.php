<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\BoardColumnTemplate;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\StructuralLevel;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use App\Services\SlaService;
use Illuminate\Http\Request;

class ProjectWebController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Project::with(['client', 'manager'])
            ->when($request->status, fn ($q) => $q->where('status', $request->status))
            ->when($request->search, fn ($q) => $q->where('name', 'like', "%{$request->search}%"));

        if ($user->hasRole('client')) {
            $query->where('client_id', $user->id);
        }

        $projects = $query->latest()->paginate($this->perPage($request))->withQueryString();

        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        $clients = User::role('client')->where('is_active', true)->where('company_id', $companyId)->get();
        $managers = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))
            ->where('is_active', true)->where('company_id', $companyId)->get();

        return view('projects.create', compact('clients', 'managers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,on_hold,completed,cancelled',
        ]);

        $project = Project::create([
            ...$request->only('name', 'description', 'client_id', 'manager_id', 'start_date', 'end_date', 'budget'),
            'status' => $request->input('status', 'active'),
        ]);

        BoardColumnTemplate::default()?->applyTo($project);

        $this->notifier->notifyManagers(
            'new_project',
            'Proyek Baru Dibuat',
            "Proyek \"{$project->name}\" baru saja dibuat oleh ".auth()->user()->name.'.',
            ['project_id' => $project->id],
            push: true,
            companyId: $project->company_id
        );

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil dibuat.');
    }

    public function show(Project $project, Request $request)
    {
        $project->load([
            'client', 'manager',
            'members.user',
            'milestones' => fn ($q) => $q->with(['assignee', 'tasks'])->orderByDesc('created_at'),
            'tasks' => fn ($q) => $q->with('assignee')->orderByDesc('created_at'),
        ]);
        $slaPolicies = app(SlaService::class);
        $developers = User::role('member')->where('is_active', true)->where('company_id', $project->company_id)->get();
        $companyUsers = User::where('is_active', true)->where('company_id', $project->company_id)
            ->whereNotIn('id', $project->members()->pluck('user_id'))
            ->orderBy('name')->get();
        $structuralLevels = StructuralLevel::active()->where('company_id', $project->company_id)->get();
        $recentTickets = $project->tickets()->with('reporter')->latest()->limit(5)->get();

        // Task & hour stats per member
        $memberTaskCounts = $project->tasks()
            ->selectRaw('assigned_to, count(*) as total, sum(case when status="done" then 1 else 0 end) as done')
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $memberHours = TimeLog::whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->selectRaw('user_id, round(sum(minutes)/60, 1) as total_hours')
            ->groupBy('user_id')
            ->pluck('total_hours', 'user_id');

        // KB articles for project tab (root only)
        $kbArticles = $project->kbArticles()
            ->with(['author', 'children'])
            ->whereNull('parent_id')
            ->latest()
            ->get();

        $chatMembers = User::whereIn('id',
            $project->members()->pluck('user_id')
                ->push($project->manager_id)
                ->filter()
                ->unique()
        )->select('id', 'name')->get();

        $sprintList = $project->sprints()->with(['tasks.assignee'])->orderByDesc('start_date')
            ->paginate($this->perPage($request), ['*'], 'sprints_page')->withQueryString();
        $backlog = $project->tasks()->whereNull('sprint_id')->with('assignee', 'milestone')->orderBy('sort_order')
            ->paginate($this->perPage($request, 10, 'backlog_per_page'), ['*'], 'backlog_page')->withQueryString();

        $recurringDefinitions = $project->recurringTasks()->with('assignee', 'milestone')->withCount('tasks')->orderByDesc('id')
            ->paginate($this->perPage($request, 10, 'recurring_per_page'), ['*'], 'recurring_page')->withQueryString();
        $recurringMilestones = $project->milestones()->orderBy('title')->get(['id', 'title']);
        $recurringUsers = User::where('company_id', $project->company_id)->orderBy('name')->get(['id', 'name']);

        // Preview file terbaru aja buat tab ringkas — browsing folder lengkap ada di
        // halaman File Manager penuh (route project.files.index), biar tidak perlu
        // narik semua file project ke satu request cuma buat tab yang mungkin nggak dibuka.
        $recentFiles = $project->files()->with('uploader')->latest()->limit(12)->get();
        $recentFilesTotal = $project->files()->count();

        $timesheetData = $this->buildTimesheetData($project, $request);

        return view('projects.show', array_merge(
            compact('project', 'developers', 'companyUsers', 'structuralLevels', 'recentTickets', 'memberTaskCounts', 'memberHours', 'kbArticles', 'chatMembers', 'backlog', 'sprintList', 'recurringDefinitions', 'recurringMilestones', 'recurringUsers', 'recentFiles', 'recentFilesTotal'),
            $timesheetData
        ));
    }

    public function edit(Project $project)
    {
        $clients = User::role('client')->where('is_active', true)->get();
        $managers = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))
            ->where('is_active', true)->get();

        return view('projects.edit', compact('project', 'clients', 'managers'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:draft,active,on_hold,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
            'meeting_default_time' => 'nullable|date_format:H:i',
            'meeting_default_duration_minutes' => 'nullable|integer|min:15|max:480',
        ]);

        $project->update([
            ...$request->only('name', 'description', 'client_id', 'manager_id', 'status', 'start_date', 'end_date', 'budget', 'progress'),
            'google_meet_enabled' => $request->boolean('google_meet_enabled'),
            'meeting_auto_create' => $request->boolean('meeting_auto_create'),
            'meeting_default_time' => $request->meeting_default_time,
            'meeting_default_duration_minutes' => $request->meeting_default_duration_minutes ?? 60,
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Proyek diperbarui.');
    }

    public function createMeeting(Project $project, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForProject($project, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyek dihapus.');
    }

    public function addMember(Request $request, Project $project)
    {
        $request->validate([
            'user_id' => 'required|array|min:1',
            'user_id.*' => 'exists:users,id',
        ]);

        foreach ($request->user_id as $userId) {
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $userId]
            );

            if ((int) $userId !== auth()->id()) {
                $this->notifier->send(
                    $userId,
                    'project_member_added',
                    'Ditambahkan ke Tim Proyek',
                    auth()->user()->name." menambahkan Anda ke tim proyek \"{$project->name}\".",
                    ['project_id' => $project->id]
                );
            }
        }

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    public function removeMember(Project $project, User $user)
    {
        ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->delete();

        return back()->with('success', 'Anggota dihapus.');
    }

    public function timesheet(Request $request, Project $project)
    {
        $logs = $this->timeLogsQuery($project, $request)
            ->orderByDesc('started_at')
            ->paginate($this->perPage($request), ['*'], 'logs_page')->withQueryString();

        return view('projects.timesheet', array_merge(
            compact('project', 'logs'),
            $this->buildTimesheetData($project, $request)
        ));
    }

    /** Base query for a project's time logs, dengan filter tanggal opsional dari request. */
    private function timeLogsQuery(Project $project, Request $request)
    {
        return TimeLog::with(['user', 'task'])
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->when($request->from, fn ($q) => $q->whereDate('started_at', '>=', $request->from))
            ->when($request->to, fn ($q) => $q->whereDate('started_at', '<=', $request->to));
    }

    private function buildTimesheetData(Project $project, Request $request): array
    {
        // Agregasi langsung di DB (bukan load semua row time_logs ke PHP) supaya ringan
        // walau time log-nya sudah ribuan baris — cuma butuh total per user, bukan detailnya.
        $summary = $this->timeLogsQuery($project, $request)
            ->selectRaw('user_id, ROUND(SUM(minutes) / 60, 2) as total_hours, COUNT(*) as logs_count')
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'user' => $row->user,
                'total_hours' => (float) $row->total_hours,
                'logs_count' => $row->logs_count,
            ])
            ->values();

        // Preview 15 log terbaru buat tab ringkas di halaman project — detail lengkap +
        // pagination ada di halaman Timesheet penuh (route projects.timesheet).
        $recentLogs = $this->timeLogsQuery($project, $request)->orderByDesc('started_at')->limit(15)->get();
        $recentLogsTotal = $this->timeLogsQuery($project, $request)->count();

        $sprints = $project->sprints()->with(['tasks.assignee'])->orderBy('start_date')->get();

        // Gantt: tasks with start/due dates, or belonging to a sprint (uses sprint's dates as fallback), + their time logs
        $ganttTasks = $project->tasks()
            ->with(['milestone', 'assignee', 'sprint', 'timeLogs' => fn ($q) => $q->where('is_running', false)->orderBy('started_at')])
            ->where(fn ($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date')->orWhereNotNull('sprint_id'))
            ->orderBy('milestone_id')
            ->orderBy('start_date')
            ->get();

        // Effective start/end per task: own dates, else its sprint's dates
        $effStart = fn ($t) => $t->start_date ?? $t->due_date ?? $t->sprint?->start_date;
        $effEnd = fn ($t) => $t->due_date ?? $t->start_date ?? $t->sprint?->end_date;

        // Determine Gantt date range (also cover sprint date ranges, even sprints with no tasks yet)
        // collect()->map() dipakai (bukan langsung $ganttTasks->map()) karena map() pada Eloquent Collection
        // mewarisi tipe Eloquent Collection walau isinya sudah bukan model (Carbon), sehingga merge() berikutnya
        // salah asumsi item punya getKey() dan meledak.
        $allStarts = collect($ganttTasks->all())->map($effStart)->filter()->merge($sprints->pluck('start_date')->filter());
        $allEnds = collect($ganttTasks->all())->map($effEnd)->filter()->merge($sprints->pluck('end_date')->filter());
        $ganttStart = $allStarts->min() ?? now()->startOfWeek();
        $ganttEnd = $allEnds->max() ?? now()->addDays(30);
        // Always show at least today + 7 days
        if ($ganttEnd->lt(now()->addDays(7))) {
            $ganttEnd = now()->addDays(7);
        }
        $ganttDays = max(1, (int) $ganttStart->diffInDays($ganttEnd) + 1);

        return compact('summary', 'recentLogs', 'recentLogsTotal', 'sprints', 'ganttTasks', 'ganttStart', 'ganttEnd', 'ganttDays');
    }
}
