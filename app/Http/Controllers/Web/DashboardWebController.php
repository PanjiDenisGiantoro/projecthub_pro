<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\Campaign;
use App\Models\Company;
use App\Models\CustomerRequest;
use App\Models\Invoice;
use App\Models\Milestone;
use App\Models\Project;
use App\Models\Sprint;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardWebController extends Controller
{
    public function index(Request $request)
    {
        $user       = auth()->user();
        $activePkg  = session('active_package', 'task_management');

        // ── HRIS Dashboard ──────────────────────────────────────────────────
        if ($activePkg === 'hris' && !$user->hasRole('customer') && ($user->is_super_admin || $user->hasPackage('hris'))) {
            $companyId      = $user->company_id;
            $totalKaryawan  = User::where('company_id', $companyId)->where('is_super_admin', false)->count();
            $totalDept      = \App\Models\OrganizationUnit::where('company_id', $companyId)->count();
            $hadirHariIni   = \App\Models\Attendance::where('company_id', $companyId)->whereDate('date', now()->toDateString())->where('status', 'hadir')->count();
            $cutiPending    = \App\Models\LeaveRequest::where('company_id', $companyId)->where('status', 'pending')->count();

            return view('dashboard.hris', compact('totalKaryawan', 'totalDept', 'hadirHariIni', 'cutiPending'));
        }

        if ($user->hasRole(['admin', 'manager'])) {
            $companies = collect();
            $cid       = $user->company_id;

            if ($user->is_super_admin) {
                $companies = Company::orderBy('name')->get(['id', 'name']);

                if ($request->has('company_id')) {
                    session(['dashboard_company_id' => $request->integer('company_id') ?: null]);
                }

                // Default sebelum dropdown pernah disentuh: admin "hybrid" (is_super_admin=true
                // tapi tetap terikat 1 company, mis. buat akses /superadmin) langsung ke company
                // miliknya sendiri — bukan data gabungan semua company. Super admin murni (tanpa
                // company_id) tetap default ke "Semua Company". Setelah dropdown dipilih manual
                // (termasuk pilih "Semua Company"), pilihan itu yang dipakai.
                $cid = session()->has('dashboard_company_id')
                    ? session('dashboard_company_id')
                    : $user->company_id;
            }

            $ckey = $cid ?? 'all';

            // Filter tambahan untuk relasi ->project ketika super admin memilih 1 company
            // (global scope company di model Project di-bypass untuk super admin)
            $projectFilter = fn($q) => $cid ? $q->where('company_id', $cid) : $q;

            $stats = Cache::remember("dashboard.admin.stats.{$ckey}.v1", 60, function () use ($cid, $projectFilter) {
                $totalTasks  = Task::whereHas('project', $projectFilter)->count();
                $doneTasks   = Task::whereHas('project', $projectFilter)->where('status', 'done')->count();
                $openTickets = BugTicket::whereHas('project', $projectFilter)->whereIn('status', ['open', 'assigned'])->count();

                $revThis  = (float) Invoice::whereHas('project', $projectFilter)->where('status', 'paid')->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('total');
                $revLast  = (float) Invoice::whereHas('project', $projectFilter)->where('status', 'paid')->whereYear('paid_at', now()->subMonth()->year)->whereMonth('paid_at', now()->subMonth()->month)->sum('total');
                $revChange = $revLast > 0 ? round(($revThis - $revLast) / $revLast * 100, 1) : null;

                $tickNow  = BugTicket::whereHas('project', $projectFilter)->whereIn('status', ['open', 'assigned'])->where('created_at', '>=', now()->startOfWeek())->count();
                $tickPrev = BugTicket::whereHas('project', $projectFilter)->whereIn('status', ['open', 'assigned'])->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
                $tickChange = $tickPrev > 0 ? round(($tickNow - $tickPrev) / $tickPrev * 100, 1) : null;

                $projectsQuery = Project::query()->when($cid, fn($q) => $q->where('company_id', $cid));

                return [
                    'projects'         => [
                        'total'     => (clone $projectsQuery)->count(),
                        'active'    => (clone $projectsQuery)->where('status', 'active')->count(),
                        'completed' => (clone $projectsQuery)->where('status', 'completed')->count(),
                        'new_month' => (clone $projectsQuery)->whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                    ],
                    'tasks'            => [
                        'total'           => $totalTasks,
                        'in_progress'     => Task::whereHas('project', $projectFilter)->where('status', 'in_progress')->count(),
                        'done'            => $doneTasks,
                        'completion_rate' => $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 1) : 0,
                    ],
                    'tasks_dist'       => [
                        'done'        => $doneTasks,
                        'in_progress' => Task::whereHas('project', $projectFilter)->where('status', 'in_progress')->count(),
                        'todo'        => Task::whereHas('project', $projectFilter)->where('status', 'todo')->count(),
                        'review'      => Task::whereHas('project', $projectFilter)->where('status', 'review')->count(),
                    ],
                    'tickets'          => ['open' => $openTickets, 'breached' => BugTicket::whereHas('project', $projectFilter)->where('sla_breached', true)->count(), 'week_change' => $tickChange],
                    'pending_requests' => CustomerRequest::whereHas('project', $projectFilter)->where('status', 'waiting_approval')->count(),
                    'revenue'          => ['total' => Invoice::whereHas('project', $projectFilter)->where('status', 'paid')->sum('total'), 'overdue' => Invoice::whereHas('project', $projectFilter)->where('status', 'overdue')->count(), 'change' => $revChange],
                ];
            });

            $revenueMonthly = Cache::remember("dashboard.revenue.monthly.{$ckey}.v1", 60, function () use ($projectFilter) {
                return collect(range(5, 0))->map(function ($monthsAgo) use ($projectFilter) {
                    $month = now()->subMonths($monthsAgo);
                    return [
                        'month'   => $month->locale('id')->isoFormat('MMM'),
                        'revenue' => (float) Invoice::whereHas('project', $projectFilter)->where('status', 'paid')->whereYear('paid_at', $month->year)->whereMonth('paid_at', $month->month)->sum('total'),
                        'target'  => (float) Invoice::whereHas('project', $projectFilter)->whereNotIn('status', ['cancelled'])->whereYear('issue_date', $month->year)->whereMonth('issue_date', $month->month)->sum('total'),
                    ];
                })->values();
            });

            $topProjects = Project::with('client')
                ->when($cid, fn($q) => $q->where('company_id', $cid))
                ->whereIn('status', ['active', 'on_hold', 'draft'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();

            $recentActivities = Task::with('assignee')->whereHas('project', $projectFilter)->where('status', 'done')->orderByDesc('updated_at')->limit(5)->get()
                ->map(fn($t) => ['type' => 'task', 'user' => $t->assignee, 'message' => 'menyelesaikan task', 'subject' => $t->title, 'time' => $t->updated_at])
                ->concat(
                    BugTicket::with('reporter')->whereHas('project', $projectFilter)->orderByDesc('created_at')->limit(5)->get()
                        ->map(fn($t) => ['type' => 'ticket', 'user' => $t->reporter, 'message' => 'membuka tiket', 'subject' => "#{$t->id} {$t->title}", 'time' => $t->created_at])
                )
                ->sortByDesc('time')->take(6)->values();

            $recentTickets = BugTicket::with(['project.client'])
                ->whereHas('project', $projectFilter)
                ->whereIn('status', ['open', 'assigned', 'in_progress'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();

            $upcomingDeadlines = \App\Models\Milestone::with('project')
                ->whereHas('project', $projectFilter)
                ->where('due_date', '>=', now()->toDateString())
                ->where('status', '!=', 'completed')
                ->orderBy('due_date')
                ->limit(5)
                ->get();

            return view('dashboard.manager', [
                'stats'               => $stats,
                'revenue_monthly'     => $revenueMonthly,
                'top_projects'        => $topProjects,
                'recent_activities'   => $recentActivities,
                'recent_tickets'      => $recentTickets,
                'upcoming_deadlines'  => $upcomingDeadlines,
                'companies'           => $companies,
                'selected_company_id' => $cid,
            ]);
        }

        if ($user->hasRole('developer')) {
            $stats = Cache::remember("dashboard.developer.{$user->id}.stats", 60, function () use ($user) {
                return [
                    'todo'        => Task::where('assigned_to', $user->id)->where('status', 'todo')->count(),
                    'in_progress' => Task::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
                    'done_week'   => Task::where('assigned_to', $user->id)->where('status', 'done')->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'hours_week'  => round(TimeLog::where('user_id', $user->id)->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('minutes') / 60, 1),
                ];
            });

            $data = [
                'my_tasks' => Task::where('assigned_to', $user->id)->whereIn('status', ['todo', 'in_progress'])->with(['project', 'milestone'])->orderBy('due_date')->limit(10)->get(),
                'stats'    => $stats,
            ];
            return view('dashboard.developer', $data);
        }

        if ($user->hasRole('marketing')) {
            $ckey = $user->company_id ?? 'superadmin';
            $stats = Cache::remember("dashboard.marketing.stats.{$ckey}.v1", 60, function () {
                return [
                    'active_campaigns' => Campaign::whereHas('project')->where('status', 'active')->count(),
                    'pending_review'   => CustomerRequest::whereHas('project')->where('status', 'waiting_approval')->count(),
                ];
            });

            $data = [
                'campaigns' => Campaign::with('project')->whereHas('project')->latest()->limit(5)->get(),
                'stats'     => $stats,
            ];
            return view('dashboard.marketing', $data);
        }

        if ($user->hasRole('customer')) {
            $stats = Cache::remember("dashboard.customer.{$user->id}.stats", 60, function () use ($user) {
                return [
                    'pending_requests' => CustomerRequest::where('customer_id', $user->id)->where('status', 'waiting_approval')->count(),
                    'open_tickets'     => BugTicket::where('reporter_id', $user->id)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                    'unpaid_invoices'  => Invoice::where('client_id', $user->id)->whereIn('status', ['sent', 'overdue'])->count(),
                ];
            });

            $data = [
                'projects'        => Project::where('client_id', $user->id)->with('milestones')->get(),
                'stats'           => $stats,
                'recent_requests' => CustomerRequest::where('customer_id', $user->id)->latest()->limit(5)->get(),
            ];
            return view('dashboard.customer', $data);
        }

        return view('dashboard.manager', []);
    }

    /**
     * Dashboard v2 (/dashboard2) — preview desain baru bergaya "TaskMeet":
     * hero jadwal, metrik performa, jadwal harian, ringkasan task, dan heatmap
     * aktivitas. Dibangun dari data nyata (task, meeting, jam kerja) yang sudah
     * ada, di-scope dengan pola yang sama seperti dashboard admin/manager biasa.
     */
    public function v2(Request $request)
    {
        $user      = auth()->user();
        $isManager = $user->hasRole(['admin', 'manager']);
        $companies = collect();
        $cid       = null;

        if ($isManager) {
            if ($user->is_super_admin) {
                $companies = Company::orderBy('name')->get(['id', 'name']);
                if ($request->has('company_id')) {
                    session(['dashboard_company_id' => $request->integer('company_id') ?: null]);
                }
                $cid = session()->has('dashboard_company_id')
                    ? session('dashboard_company_id')
                    : $user->company_id;
            } else {
                $cid = $user->company_id;
            }
        }

        $projectFilter = fn($q) => $cid ? $q->where('company_id', $cid) : $q;

        $tasksBase = fn() => Task::query()->when(
            $isManager,
            fn($q) => $q->whereHas('project', $projectFilter),
            fn($q) => $q->where('assigned_to', $user->id)
        );

        $totalTasks      = $tasksBase()->count();
        $doneTasks       = $tasksBase()->where('status', 'done')->count();
        $inProgressTasks = $tasksBase()->where('status', 'in_progress')->count();
        $reviewTasks     = $tasksBase()->where('status', 'review')->count();
        $todoTasks       = $tasksBase()->where('status', 'todo')->count();
        $completionRate  = $totalTasks > 0 ? (int) round($doneTasks / $totalTasks * 100) : 0;

        $doneThisWeek   = $tasksBase()->where('status', 'done')->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count();
        $activeLast7d   = $tasksBase()->where('updated_at', '>=', now()->subDays(7))->count();
        $engagementRate = $totalTasks > 0 ? (int) round($activeLast7d / $totalTasks * 100) : 0;

        $teamCollaborations = $isManager
            ? $tasksBase()->whereNotNull('assigned_to')->distinct('assigned_to')->count('assigned_to')
            : $tasksBase()->distinct('project_id')->count('project_id');

        $stats = [
            'total_tasks'      => $totalTasks,
            'done_tasks'       => $doneTasks,
            'in_progress'      => $inProgressTasks,
            'review'           => $reviewTasks,
            'todo'             => $todoTasks,
            'completion_rate'  => $completionRate,
            'done_this_week'   => $doneThisWeek,
            'engagement_rate'  => $engagementRate,
            'collaborations'   => $teamCollaborations,
        ];

        // ── Task summary (kolom kanan, mirip panel "Task Summary") ──────────
        $taskSummary = [
            'upcoming' => $tasksBase()->with(['project', 'assignee'])->where('status', 'todo')->orderBy('due_date')->limit(4)->get(),
            'ongoing'  => $tasksBase()->with(['project', 'assignee'])->whereIn('status', ['in_progress', 'review'])->orderBy('due_date')->limit(4)->get(),
            'complete' => $tasksBase()->with(['project', 'assignee'])->where('status', 'done')->orderByDesc('updated_at')->limit(4)->get(),
        ];

        // ── Meeting (project/sprint/milestone/task/tiket dengan jadwal) ─────
        $meetings = $this->collectMeetingsV2($projectFilter, $user->hasRole('customer'), $user->id);
        $todaySchedule    = $meetings->filter(fn($m) => $m['startsAt'] && $m['startsAt']->isToday())->values();
        $upcomingMeetings = $meetings->filter(fn($m) => !$m['startsAt'] || $m['startsAt']->isFuture())->take(6)->values();

        $meetingsThisMonth = $meetings->filter(fn($m) => $m['startsAt'] && $m['startsAt']->isCurrentMonth());
        $meetingStats = [
            'total'    => $meetingsThisMonth->count(),
            'past'     => $meetingsThisMonth->filter(fn($m) => $m['startsAt']->isPast())->count(),
            'upcoming' => $meetingsThisMonth->filter(fn($m) => $m['startsAt']->isFuture())->count(),
        ];

        // ── Jam kerja 7 hari terakhir (pengganti "AI Summary" — data nyata dari time log) ──
        $timeLogQuery = fn() => TimeLog::query()->when(
            $isManager,
            fn($q) => $q->whereHas('task.project', $projectFilter),
            fn($q) => $q->where('user_id', $user->id)
        );
        $weeklyHours = collect(range(6, 0))->map(function ($daysAgo) use ($timeLogQuery) {
            $day = now()->subDays($daysAgo);
            $minutes = (clone $timeLogQuery())->whereDate('started_at', $day->toDateString())->sum('minutes');
            return ['label' => $day->locale('id')->isoFormat('dd'), 'hours' => round($minutes / 60, 1)];
        })->values();
        $totalHoursWeek = round($weeklyHours->sum('hours'), 1);
        $avgHoursDay    = round($totalHoursWeek / 7, 1);

        // ── Heatmap aktivitas: task selesai per hari, 4 minggu terakhir ──────
        $doneDates = $tasksBase()->where('status', 'done')
            ->where('updated_at', '>=', now()->subWeeks(4)->startOfWeek())
            ->get(['updated_at'])
            ->groupBy(fn($t) => $t->updated_at->toDateString())
            ->map->count();

        $activityGrid = collect(range(3, 0))->map(function ($weeksAgo) use ($doneDates) {
            $weekStart = now()->subWeeks($weeksAgo)->startOfWeek();
            return collect(range(0, 6))->map(function ($d) use ($weekStart, $doneDates) {
                $date = $weekStart->copy()->addDays($d);
                return ['date' => $date, 'count' => $doneDates->get($date->toDateString(), 0)];
            });
        });

        return view('dashboard.v2', [
            'stats'              => $stats,
            'taskSummary'        => $taskSummary,
            'todaySchedule'      => $todaySchedule,
            'upcomingMeetings'   => $upcomingMeetings,
            'meetingStats'       => $meetingStats,
            'weeklyHours'        => $weeklyHours,
            'totalHoursWeek'     => $totalHoursWeek,
            'avgHoursDay'        => $avgHoursDay,
            'activityGrid'       => $activityGrid,
            'companies'          => $companies,
            'selectedCompanyId'  => $cid,
            'isManager'          => $isManager,
        ]);
    }

    /**
     * Kumpulkan meeting lintas entitas (project/sprint/milestone/task/tiket)
     * — versi ringkas dari MeetingWebController::index() untuk kebutuhan dashboard.
     */
    private function collectMeetingsV2($projectFilter, bool $isCustomer, int $userId)
    {
        $meetings = collect();
        $scopeToClient = fn($q) => $isCustomer ? $q->where('client_id', $userId) : $q;

        Project::query()->tap($projectFilter)->tap($scopeToClient)
            ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'))
            ->get()->each(fn($p) => $meetings->push([
                'type' => 'project', 'title' => $p->name, 'project' => $p->name,
                'startsAt' => $p->meeting_starts_at, 'meetLink' => $p->google_meet_link,
                'organizer' => null, 'url' => route('projects.show', $p->id),
            ]));

        Sprint::with(['project', 'meetingOrganizer'])
            ->whereHas('project', fn($q) => $q->tap($projectFilter)->when($isCustomer, $scopeToClient))
            ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'))
            ->get()->each(fn($s) => $meetings->push([
                'type' => 'sprint', 'title' => $s->name, 'project' => $s->project?->name,
                'startsAt' => $s->meeting_starts_at, 'meetLink' => $s->google_meet_link,
                'organizer' => $s->meetingOrganizer?->name, 'url' => route('sprints.show', [$s->project_id, $s->id]),
            ]));

        Milestone::with(['project', 'meetingOrganizer'])
            ->whereHas('project', fn($q) => $q->tap($projectFilter)->when($isCustomer, $scopeToClient))
            ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'))
            ->get()->each(fn($m) => $meetings->push([
                'type' => 'milestone', 'title' => $m->title, 'project' => $m->project?->name,
                'startsAt' => $m->meeting_starts_at, 'meetLink' => $m->google_meet_link,
                'organizer' => $m->meetingOrganizer?->name, 'url' => route('projects.show', $m->project_id),
            ]));

        Task::with(['project', 'assignee', 'meetingOrganizer'])->whereNull('deleted_at')
            ->whereHas('project', fn($q) => $q->tap($projectFilter)->when($isCustomer, $scopeToClient))
            ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'))
            ->get()->each(fn($t) => $meetings->push([
                'type' => 'task', 'title' => $t->title, 'project' => $t->project?->name,
                'startsAt' => $t->meeting_starts_at, 'meetLink' => $t->google_meet_link,
                'organizer' => $t->meetingOrganizer?->name, 'assignee' => $t->assignee,
                'url' => route('tasks.show', [$t->project_id, $t->id]),
            ]));

        BugTicket::with(['project'])
            ->whereHas('project', fn($q) => $q->tap($projectFilter)->when($isCustomer, $scopeToClient))
            ->where(fn($q) => $q->whereNotNull('meeting_starts_at')->orWhereNotNull('google_meet_link'))
            ->get()->each(fn($t) => $meetings->push([
                'type' => 'ticket', 'title' => $t->title, 'project' => $t->project?->name,
                'startsAt' => $t->meeting_starts_at, 'meetLink' => $t->google_meet_link,
                'organizer' => null, 'url' => route('tickets.show', $t->id),
            ]));

        return $meetings->sortBy(fn($m) => $m['startsAt'] ?? now()->addCentury())->values();
    }

    public function workload()
    {
        $developers = User::role('developer')
            ->where('company_id', auth()->user()->company_id)
            ->with(['assignedTasks' => fn($q) => $q->whereIn('status', ['todo', 'in_progress'])->with('project')])
            ->get();

        return view('workload', compact('developers'));
    }
}
