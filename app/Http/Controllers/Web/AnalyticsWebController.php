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
        $period = (int) $request->input('period', 30);
        $since  = now()->subDays($period)->startOfDay();

        // ── KPI ──────────────────────────────────────────────────────────────
        $totalTasks    = Task::whereNull('deleted_at')->count();
        $doneTasks     = Task::whereNull('deleted_at')->where('status', 'done')->count();
        $overdueCount  = Task::whereNull('deleted_at')
            ->whereNotNull('due_date')->where('due_date', '<', now())
            ->whereNotIn('status', ['done'])->count();
        $totalProjects = Project::whereNull('deleted_at')->count();
        $activeProjects = Project::whereNull('deleted_at')->where('status', 'active')->count();
        $openTickets   = BugTicket::whereNotIn('status', ['resolved', 'closed'])->count();
        $slaBreached   = BugTicket::where('sla_breached', true)
            ->whereNotIn('status', ['resolved', 'closed'])->count();
        $newRequests   = CustomerRequest::where('status', 'pending')
            ->where('created_at', '>=', $since)->count();

        // ── Task status breakdown ─────────────────────────────────────────────
        $tasksByStatus = Task::selectRaw('status, count(*) as count')
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->pluck('count', 'status');

        // ── Weekly created vs completed (8 weeks) ────────────────────────────
        $weeks = collect();
        for ($i = 7; $i >= 0; $i--) {
            $wStart = now()->subWeeks($i)->startOfWeek();
            $wEnd   = now()->subWeeks($i)->endOfWeek();
            $weeks->push([
                'label'     => $wStart->format('d M'),
                'created'   => Task::whereBetween('created_at', [$wStart, $wEnd])->whereNull('deleted_at')->count(),
                'completed' => Task::whereBetween('updated_at', [$wStart, $wEnd])->where('status', 'done')->whereNull('deleted_at')->count(),
            ]);
        }

        // ── Daily completions (period) ────────────────────────────────────────
        $completions = Task::selectRaw('DATE(updated_at) as date, count(*) as count')
            ->where('status', 'done')->where('updated_at', '>=', $since)
            ->whereNull('deleted_at')->groupBy('date')->orderBy('date')
            ->pluck('count', 'date');

        // ── Projects by status ────────────────────────────────────────────────
        $projectsByStatus = Project::selectRaw('status, count(*) as count')
            ->whereNull('deleted_at')->groupBy('status')->pluck('count', 'status');

        // ── Ticket stats ──────────────────────────────────────────────────────
        $ticketsByStatus = BugTicket::selectRaw('status, count(*) as count')
            ->groupBy('status')->pluck('count', 'status');

        $ticketsByPriority = BugTicket::selectRaw('priority, count(*) as count')
            ->whereNotIn('status', ['resolved', 'closed'])
            ->groupBy('priority')->pluck('count', 'priority');

        // ── Top developers by hours logged ────────────────────────────────────
        $topDevs = TimeLog::selectRaw('user_id, sum(minutes) as total_minutes')
            ->where('started_at', '>=', $since)
            ->groupBy('user_id')->orderByDesc('total_minutes')->limit(8)
            ->with('user')->get();

        // ── Task completion rate per project (top 6 active) ───────────────────
        $projectProgress = Project::withCount([
            'tasks as total_tasks'  => fn($q) => $q->whereNull('deleted_at'),
            'tasks as done_tasks'   => fn($q) => $q->whereNull('deleted_at')->where('status', 'done'),
        ])->where('status', 'active')->whereNull('deleted_at')
          ->orderByDesc('done_tasks')->limit(6)->get()
          ->map(fn($p) => [
              'name'    => $p->name,
              'total'   => $p->total_tasks,
              'done'    => $p->done_tasks,
              'percent' => $p->total_tasks > 0 ? round($p->done_tasks / $p->total_tasks * 100) : 0,
          ]);

        // ── Milestone completion ──────────────────────────────────────────────
        $totalMilestones    = Milestone::count();
        $completedMilestones = Milestone::where('status', 'completed')->count();

        // ── New tasks trend (last 14 days) ────────────────────────────────────
        $dailyCreated = collect();
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $dailyCreated->push([
                'label' => $day->format('d/m'),
                'count' => Task::whereDate('created_at', $day)->whereNull('deleted_at')->count(),
            ]);
        }

        return view('analytics.index', compact(
            'period', 'totalTasks', 'doneTasks', 'overdueCount',
            'totalProjects', 'activeProjects', 'openTickets', 'slaBreached', 'newRequests',
            'tasksByStatus', 'projectsByStatus', 'ticketsByStatus', 'ticketsByPriority',
            'weeks', 'completions', 'topDevs', 'projectProgress',
            'totalMilestones', 'completedMilestones', 'dailyCreated'
        ));
    }
}
