<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\StructuralLevel;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\SlaService;
use Illuminate\Http\Request;

class ProjectWebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Project::with(['client', 'manager'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->search, fn($q) => $q->where('name', 'like', "%{$request->search}%"));

        if ($user->hasRole('customer')) {
            $query->where('client_id', $user->id);
        } elseif ($user->hasRole(['developer', 'marketing'])) {
            $query->whereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->orWhere('manager_id', $user->id);
        }

        $projects = $query->latest()->paginate(12);
        return view('projects.index', compact('projects'));
    }

    public function create()
    {
        $companyId = auth()->user()->company_id;
        $clients   = User::role('customer')->where('is_active', true)->where('company_id', $companyId)->get();
        $managers  = User::role('manager')->where('is_active', true)->where('company_id', $companyId)->get();
        return view('projects.create', compact('clients', 'managers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'client_id'  => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
            'budget'     => 'nullable|numeric|min:0',
        ]);

        $project = Project::create([
            ...$request->only('name', 'description', 'client_id', 'manager_id', 'start_date', 'end_date', 'budget'),
            'status' => 'draft',
        ]);

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil dibuat.');
    }

    public function show(Project $project)
    {
        $project->load([
            'client', 'manager',
            'members.user',
            'milestones.assignee', 'milestones.tasks',
            'tasks' => fn($q) => $q->with('assignee')->limit(10),
        ]);
        $slaPolicies      = app(SlaService::class);
        $developers       = User::role(['developer', 'marketing'])->where('is_active', true)->where('company_id', $project->company_id)->get();
        $structuralLevels = StructuralLevel::active()->where('company_id', $project->company_id)->get();
        $recentTickets = $project->tickets()->with('reporter')->latest()->limit(5)->get();

        // Task & hour stats per member
        $memberTaskCounts = $project->tasks()
            ->selectRaw('assigned_to, count(*) as total, sum(case when status="done" then 1 else 0 end) as done')
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->pluck('total', 'assigned_to');

        $memberHours = TimeLog::whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->selectRaw('user_id, round(sum(minutes)/60, 1) as total_hours')
            ->groupBy('user_id')
            ->pluck('total_hours', 'user_id');

        // KB articles for project tab (root only)
        $kbArticles = $project->kbArticles()
            ->with(['author', 'children'])
            ->whereNull('parent_id')
            ->latest()
            ->get();

        $chatMembers = \App\Models\User::whereIn('id',
            $project->members()->pluck('user_id')
                ->push($project->manager_id)
                ->filter()
                ->unique()
        )->select('id', 'name')->get();

        return view('projects.show', compact('project', 'developers', 'structuralLevels', 'recentTickets', 'memberTaskCounts', 'memberHours', 'kbArticles', 'chatMembers'));
    }

    public function edit(Project $project)
    {
        $clients  = User::role('customer')->where('is_active', true)->get();
        $managers = User::role('manager')->where('is_active', true)->get();
        return view('projects.edit', compact('project', 'clients', 'managers'));
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'status'   => 'in:draft,active,on_hold,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
        ]);

        $project->update($request->only('name', 'description', 'client_id', 'manager_id', 'status', 'start_date', 'end_date', 'budget', 'progress'));

        return redirect()->route('projects.show', $project)->with('success', 'Proyek diperbarui.');
    }

    public function destroy(Project $project)
    {
        $project->delete();
        return redirect()->route('projects.index')->with('success', 'Proyek dihapus.');
    }

    public function addMember(Request $request, Project $project)
    {
        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'role'              => 'nullable|string|max:100',
            'max_hours_per_day' => 'nullable|integer|min:1|max:24',
        ]);

        ProjectMember::updateOrCreate(
            ['project_id' => $project->id, 'user_id' => $request->user_id],
            ['role' => $request->role ?? 'developer', 'max_hours_per_day' => $request->max_hours_per_day ?? 8]
        );

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    public function removeMember(Project $project, User $user)
    {
        ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->delete();
        return back()->with('success', 'Anggota dihapus.');
    }

    public function timesheet(Request $request, Project $project)
    {
        $logs = TimeLog::with(['user', 'task'])
            ->whereHas('task', fn($q) => $q->where('project_id', $project->id))
            ->when($request->from, fn($q) => $q->whereDate('started_at', '>=', $request->from))
            ->when($request->to, fn($q) => $q->whereDate('started_at', '<=', $request->to))
            ->orderBy('started_at')
            ->get();

        $summary = $logs->groupBy('user_id')->map(fn($ul) => [
            'user'        => $ul->first()->user,
            'total_hours' => round($ul->sum('minutes') / 60, 2),
            'logs_count'  => $ul->count(),
        ])->values();

        // Gantt: tasks with start/due dates and their time logs
        $ganttTasks = $project->tasks()
            ->with(['milestone', 'assignee', 'timeLogs' => fn($q) => $q->where('is_running', false)->orderBy('started_at')])
            ->where(fn($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date'))
            ->orderBy('milestone_id')
            ->orderBy('start_date')
            ->get();

        // Determine Gantt date range
        $allStarts = $ganttTasks->filter(fn($t) => $t->start_date)->pluck('start_date');
        $allEnds   = $ganttTasks->filter(fn($t) => $t->due_date)->pluck('due_date');
        $ganttStart = $allStarts->min() ?? now()->startOfWeek();
        $ganttEnd   = $allEnds->max()   ?? now()->addDays(30);
        // Always show at least today + 7 days
        if ($ganttEnd->lt(now()->addDays(7))) $ganttEnd = now()->addDays(7);
        $ganttDays = max(1, (int) $ganttStart->diffInDays($ganttEnd) + 1);

        return view('projects.timesheet', compact('project', 'logs', 'summary', 'ganttTasks', 'ganttStart', 'ganttEnd', 'ganttDays'));
    }
}
