<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\CustomerRequest;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AnalyticsWebController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $isAdmin = $user->is_super_admin || $user->hasRole('admin');

        // ── Range tanggal (default 30 hari terakhir) ─────────────────────────
        $parse = function ($v) {
            try { return $v ? Carbon::parse($v) : null; } catch (\Throwable) { return null; }
        };
        $until = ($parse($request->input('end_date')) ?? now())->endOfDay();
        $since = ($parse($request->input('start_date')) ?? $until->copy()->subDays(29))->startOfDay();
        if ($since->gt($until)) {
            [$since, $until] = [$until->copy()->startOfDay(), $since->copy()->endOfDay()];
        }
        if ($since->diffInDays($until) > 366) {
            $since = $until->copy()->subDays(365)->startOfDay();
        }
        $rangeDays = (int) $since->diffInDays($until) + 1;

        // ── Filter per user (khusus admin; daftar user dibatasi company) ─────
        $companyUsers = collect();
        $selectedUser = null;
        if ($isAdmin) {
            $companyUsers = User::query()
                ->when(! $user->is_super_admin, fn ($q) => $q->where('company_id', $user->company_id))
                ->with('company:id,name')
                ->orderBy('name')
                ->get(['id', 'name', 'email', 'company_id']);
            if ($uid = (int) $request->input('user_id')) {
                $selectedUser = $companyUsers->firstWhere('id', $uid);
            }
        }
        $userId = $selectedUser?->id;

        // Task/BugTicket/Milestone/TimeLog tidak punya global scope company → filter lewat project
        // (Project & CustomerRequest sudah ter-scope otomatis). Super admin melihat semua perusahaan.
        $companyId = $user->is_super_admin ? null : (int) $user->company_id;
        $inCompany = fn (string $relation) => fn ($q) => $user->is_super_admin
            ? $q
            : $q->whereHas($relation, fn ($p) => $p->where('projects.company_id', $companyId));

        $userProjects = fn ($q) => $q->where(fn ($w) => $w
            ->where('projects.manager_id', $userId)
            ->orWhereHas('members', fn ($m) => $m->where('user_id', $userId)));

        $projects = fn () => Project::query()->when($userId, $userProjects);
        $tasks = fn () => Task::query()->tap($inCompany('project'))
            ->when($userId, fn ($q) => $q->where(fn ($w) => $w
                ->where('assigned_to', $userId)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $userId))));
        $tickets = fn () => BugTicket::query()->tap($inCompany('project'))
            ->when($userId, fn ($q) => $q->where(fn ($w) => $w
                ->where('assignee_id', $userId)
                ->orWhereHas('assignees', fn ($m) => $m->where('users.id', $userId))));
        $milestones = fn () => Milestone::query()->tap($inCompany('project'))
            ->when($userId, fn ($q) => $q->whereHas('project', $userProjects));
        $timeLogs = fn () => TimeLog::query()->tap($inCompany('task.project'))
            ->when($userId, fn ($q) => $q->where('user_id', $userId));

        // ── KPI ──────────────────────────────────────────────────────────────
        $totalTasks    = $tasks()->count();
        $doneTasks     = $tasks()->where('status', 'done')->count();
        $overdueCount  = $tasks()->whereNotNull('due_date')->where('due_date', '<', now())
            ->whereNotIn('status', ['done'])->count();
        $totalProjects  = $projects()->count();
        $activeProjects = $projects()->where('status', 'active')->count();
        $openTickets   = $tickets()->whereNotIn('status', ['resolved', 'closed'])->count();
        $slaBreached   = $tickets()->where('sla_breached', true)
            ->whereNotIn('status', ['resolved', 'closed'])->count();
        $newRequests   = CustomerRequest::where('status', 'waiting_approval')
            ->when($userId, fn ($q) => $q->whereHas('project', $userProjects))
            ->whereBetween('created_at', [$since, $until])->count();

        // ── Task status breakdown ─────────────────────────────────────────────
        $tasksByStatus = $tasks()->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        // ── Created vs completed dalam range (bucket harian/mingguan/bulanan) ─
        $doneAt = 'DATE(COALESCE(completed_at, updated_at))';
        $createdPerDay = $tasks()->selectRaw('DATE(created_at) as d, count(*) as c')
            ->whereBetween('created_at', [$since, $until])
            ->groupBy('d')->pluck('c', 'd');
        $completedPerDay = $tasks()->selectRaw("$doneAt as d, count(*) as c")
            ->where('status', 'done')
            ->whereRaw("COALESCE(completed_at, updated_at) BETWEEN ? AND ?", [$since, $until])
            ->groupBy('d')->pluck('c', 'd');

        $bucketUnit = $rangeDays <= 31 ? 'day' : ($rangeDays <= 180 ? 'week' : 'month');
        $bucketLabel = ['day' => 'Harian', 'week' => 'Mingguan', 'month' => 'Bulanan'][$bucketUnit];
        $buckets = [];
        for ($d = $since->copy(); $d->lte($until); $d->addDay()) {
            $key = match ($bucketUnit) {
                'day'   => $d->format('Y-m-d'),
                'week'  => $d->copy()->startOfWeek()->format('Y-m-d'),
                'month' => $d->format('Y-m'),
            };
            $buckets[$key] ??= [
                'label' => match ($bucketUnit) {
                    'day'   => $d->format('d/m'),
                    'week'  => $d->copy()->startOfWeek()->max($since)->format('d M'),
                    'month' => $d->translatedFormat('M Y'),
                },
                'created' => 0, 'completed' => 0,
            ];
            $buckets[$key]['created']   += (int) ($createdPerDay[$d->format('Y-m-d')] ?? 0);
            $buckets[$key]['completed'] += (int) ($completedPerDay[$d->format('Y-m-d')] ?? 0);
        }
        $weeks = collect(array_values($buckets));
        $dailyCreated = $weeks->map(fn ($b) => ['label' => $b['label'], 'count' => $b['created']]);
        $completions = $completedPerDay;

        // ── Projects by status ────────────────────────────────────────────────
        $projectsByStatus = $projects()->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        // ── Ticket stats ──────────────────────────────────────────────────────
        $ticketsByStatus = $tickets()->selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        $ticketsByPriority = $tickets()->selectRaw('priority, count(*) as count')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->groupBy('priority')->pluck('count', 'priority');

        // ── Top developers by hours logged (dalam range) ──────────────────────
        $topDevs = $timeLogs()->selectRaw('user_id, sum(minutes) as total_minutes')
            ->whereBetween('started_at', [$since, $until])
            ->groupBy('user_id')->orderByDesc('total_minutes')->limit(8)
            ->with('user')->get();

        // ── Task completion rate per project (top 6 active) ───────────────────
        $taskScope = fn ($q) => $q->when($userId, fn ($t) => $t->where(fn ($w) => $w
            ->where('assigned_to', $userId)
            ->orWhereHas('members', fn ($m) => $m->where('users.id', $userId))));
        $projectProgress = $projects()->withCount([
            'tasks as total_tasks' => $taskScope,
            'tasks as done_tasks'  => fn ($q) => $taskScope($q)->where('status', 'done'),
        ])->where('status', 'active')
          ->orderByDesc('done_tasks')->limit(6)->get()
          ->map(fn ($p) => [
              'name'    => $p->name,
              'total'   => $p->total_tasks,
              'done'    => $p->done_tasks,
              'percent' => $p->total_tasks > 0 ? round($p->done_tasks / $p->total_tasks * 100) : 0,
          ]);

        // ── Milestone completion ──────────────────────────────────────────────
        $totalMilestones     = $milestones()->count();
        $completedMilestones = $milestones()->where('status', 'completed')->count();

        $startDate = $since->toDateString();
        $endDate   = $until->toDateString();

        return view('analytics.index', compact(
            'startDate', 'endDate', 'rangeDays', 'bucketLabel',
            'isAdmin', 'companyUsers', 'selectedUser',
            'totalTasks', 'doneTasks', 'overdueCount',
            'totalProjects', 'activeProjects', 'openTickets', 'slaBreached', 'newRequests',
            'tasksByStatus', 'projectsByStatus', 'ticketsByStatus', 'ticketsByPriority',
            'weeks', 'completions', 'topDevs', 'projectProgress',
            'totalMilestones', 'completedMilestones', 'dailyCreated'
        ));
    }
}
