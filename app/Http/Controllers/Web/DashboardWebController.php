<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\BugTicket;
use App\Models\Campaign;
use App\Models\CustomerRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardWebController extends Controller
{
    public function index()
    {
        $user       = auth()->user();
        $activePkg  = session('active_package', 'task_management');

        // ── HRIS Dashboard ──────────────────────────────────────────────────
        if ($activePkg === 'hris' && ($user->is_super_admin || $user->hasPackage('hris'))) {
            $companyId      = $user->company_id;
            $totalKaryawan  = User::where('company_id', $companyId)->where('is_super_admin', false)->count();
            $totalDept      = \App\Models\Department::whereHas('division.branch', fn($q) => $q->where('company_id', $companyId))->count();

            return view('dashboard.hris', compact('totalKaryawan', 'totalDept'));
        }

        if ($user->hasRole(['admin', 'manager'])) {
            $cid   = $user->company_id;
            $ckey  = $cid ?? 'superadmin';

            $stats = Cache::remember("dashboard.admin.stats.{$ckey}.v1", 60, function () use ($cid) {
                // Tasks scoped via project (Project global scope aplly otomatis)
                $totalTasks  = Task::whereHas('project')->count();
                $doneTasks   = Task::whereHas('project')->where('status', 'done')->count();
                $openTickets = BugTicket::whereHas('project')->whereIn('status', ['open', 'assigned'])->count();

                $revThis  = (float) Invoice::whereHas('project')->where('status', 'paid')->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month)->sum('total');
                $revLast  = (float) Invoice::whereHas('project')->where('status', 'paid')->whereYear('paid_at', now()->subMonth()->year)->whereMonth('paid_at', now()->subMonth()->month)->sum('total');
                $revChange = $revLast > 0 ? round(($revThis - $revLast) / $revLast * 100, 1) : null;

                $tickNow  = BugTicket::whereHas('project')->whereIn('status', ['open', 'assigned'])->where('created_at', '>=', now()->startOfWeek())->count();
                $tickPrev = BugTicket::whereHas('project')->whereIn('status', ['open', 'assigned'])->whereBetween('created_at', [now()->subWeek()->startOfWeek(), now()->subWeek()->endOfWeek()])->count();
                $tickChange = $tickPrev > 0 ? round(($tickNow - $tickPrev) / $tickPrev * 100, 1) : null;

                return [
                    'projects'         => [
                        'total'     => Project::count(),
                        'active'    => Project::where('status', 'active')->count(),
                        'completed' => Project::where('status', 'completed')->count(),
                        'new_month' => Project::whereYear('created_at', now()->year)->whereMonth('created_at', now()->month)->count(),
                    ],
                    'tasks'            => [
                        'total'           => $totalTasks,
                        'in_progress'     => Task::whereHas('project')->where('status', 'in_progress')->count(),
                        'done'            => $doneTasks,
                        'completion_rate' => $totalTasks > 0 ? round($doneTasks / $totalTasks * 100, 1) : 0,
                    ],
                    'tasks_dist'       => [
                        'done'        => $doneTasks,
                        'in_progress' => Task::whereHas('project')->where('status', 'in_progress')->count(),
                        'todo'        => Task::whereHas('project')->where('status', 'todo')->count(),
                        'review'      => Task::whereHas('project')->where('status', 'review')->count(),
                    ],
                    'tickets'          => ['open' => $openTickets, 'breached' => BugTicket::whereHas('project')->where('sla_breached', true)->count(), 'week_change' => $tickChange],
                    'pending_requests' => CustomerRequest::whereHas('project', fn($q) => $q->when($cid, fn($q) => $q->where('company_id', $cid)))->whereIn('status', ['submitted', 'under_review'])->count(),
                    'revenue'          => ['total' => Invoice::whereHas('project')->where('status', 'paid')->sum('total'), 'overdue' => Invoice::whereHas('project')->where('status', 'overdue')->count(), 'change' => $revChange],
                ];
            });

            $revenueMonthly = Cache::remember("dashboard.revenue.monthly.{$ckey}.v1", 60, function () {
                return collect(range(5, 0))->map(function ($monthsAgo) {
                    $month = now()->subMonths($monthsAgo);
                    return [
                        'month'   => $month->locale('id')->isoFormat('MMM'),
                        'revenue' => (float) Invoice::whereHas('project')->where('status', 'paid')->whereYear('paid_at', $month->year)->whereMonth('paid_at', $month->month)->sum('total'),
                        'target'  => (float) Invoice::whereHas('project')->whereNotIn('status', ['cancelled'])->whereYear('issue_date', $month->year)->whereMonth('issue_date', $month->month)->sum('total'),
                    ];
                })->values();
            });

            $topProjects = Project::with('client')
                ->whereIn('status', ['active', 'on_hold', 'draft'])
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get();

            $recentActivities = Task::with('assignee')->where('status', 'done')->orderByDesc('updated_at')->limit(5)->get()
                ->map(fn($t) => ['type' => 'task', 'user' => $t->assignee, 'message' => 'menyelesaikan task', 'subject' => $t->title, 'time' => $t->updated_at])
                ->concat(
                    BugTicket::with('reporter')->orderByDesc('created_at')->limit(5)->get()
                        ->map(fn($t) => ['type' => 'ticket', 'user' => $t->reporter, 'message' => 'membuka tiket', 'subject' => "#{$t->id} {$t->title}", 'time' => $t->created_at])
                )
                ->sortByDesc('time')->take(6)->values();

            $recentTickets = BugTicket::with(['project.client'])
                ->whereIn('status', ['open', 'assigned', 'in_progress'])
                ->orderByDesc('created_at')
                ->limit(6)
                ->get();

            $upcomingDeadlines = \App\Models\Milestone::with('project')
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
                    'pending_review'   => CustomerRequest::whereHas('project')->where('status', 'submitted')->count(),
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
                    'pending_requests' => CustomerRequest::where('customer_id', $user->id)->whereIn('status', ['submitted', 'under_review'])->count(),
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

    public function workload()
    {
        $developers = User::role('developer')
            ->where('company_id', auth()->user()->company_id)
            ->with(['assignedTasks' => fn($q) => $q->whereIn('status', ['todo', 'in_progress'])->with('project')])
            ->get();

        return view('workload', compact('developers'));
    }
}
