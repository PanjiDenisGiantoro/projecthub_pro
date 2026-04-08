<?php

namespace App\Http\Controllers;

use App\Models\BugTicket;
use App\Models\Campaign;
use App\Models\CustomerRequest;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->hasRole(['admin', 'manager'])) {
            return $this->managerDashboard();
        }

        if ($user->hasRole('developer')) {
            return $this->developerDashboard($user);
        }

        if ($user->hasRole('marketing')) {
            return $this->marketingDashboard($user);
        }

        if ($user->hasRole('customer')) {
            return $this->customerDashboard($user);
        }

        return response()->json(['message' => 'No dashboard available for your role.'], 403);
    }

    private function managerDashboard(): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'projects' => [
                'total' => Project::count(),
                'active' => Project::where('status', 'active')->count(),
                'completed' => Project::where('status', 'completed')->count(),
                'on_hold' => Project::where('status', 'on_hold')->count(),
            ],
            'tasks' => [
                'total' => Task::count(),
                'todo' => Task::where('status', 'todo')->count(),
                'in_progress' => Task::where('status', 'in_progress')->count(),
                'done' => Task::where('status', 'done')->count(),
            ],
            'tickets' => [
                'total' => BugTicket::count(),
                'open' => BugTicket::whereIn('status', ['open', 'assigned'])->count(),
                'breached' => BugTicket::where('sla_breached', true)->count(),
                'resolved_today' => BugTicket::whereDate('resolved_at', today())->count(),
            ],
            'requests' => [
                'pending_approval' => CustomerRequest::whereIn('status', ['submitted', 'under_review'])->count(),
                'approved_today' => CustomerRequest::whereDate('approved_at', today())->count(),
            ],
            'revenue' => [
                'total_invoiced' => Invoice::where('status', '!=', 'cancelled')->sum('total'),
                'paid' => Invoice::where('status', 'paid')->sum('total'),
                'overdue' => Invoice::where('status', 'overdue')->count(),
            ],
            'team' => [
                'total_users' => User::where('is_active', true)->count(),
                'developers' => User::role('developer')->count(),
            ],
            'recent_projects' => Project::with(['client', 'manager'])
                ->latest()->limit(5)->get(),
            'recent_tickets' => BugTicket::with(['project', 'reporter'])
                ->whereIn('status', ['open', 'assigned'])->latest()->limit(5)->get(),
        ]);
    }

    private function developerDashboard(User $user): \Illuminate\Http\JsonResponse
    {
        $myTasks = Task::where('assigned_to', $user->id);

        return response()->json([
            'tasks' => [
                'total' => $myTasks->count(),
                'todo' => (clone $myTasks)->where('status', 'todo')->count(),
                'in_progress' => (clone $myTasks)->where('status', 'in_progress')->count(),
                'done_this_week' => (clone $myTasks)->where('status', 'done')
                    ->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            ],
            'hours_this_week' => round(
                TimeLog::where('user_id', $user->id)
                    ->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])
                    ->sum('minutes') / 60,
                2
            ),
            'my_tasks' => Task::where('assigned_to', $user->id)
                ->whereIn('status', ['todo', 'in_progress'])
                ->with(['project', 'milestone'])
                ->orderBy('due_date')
                ->limit(10)
                ->get(),
        ]);
    }

    private function marketingDashboard(User $user): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'campaigns' => [
                'total' => Campaign::count(),
                'active' => Campaign::where('status', 'active')->count(),
            ],
            'requests_pending_review' => CustomerRequest::where('status', 'submitted')->count(),
            'recent_campaigns' => Campaign::with('project')->latest()->limit(5)->get(),
        ]);
    }

    private function customerDashboard(User $user): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'projects' => Project::where('client_id', $user->id)
                ->with('milestones')
                ->get(['id', 'name', 'status', 'progress', 'end_date']),
            'requests' => [
                'total' => CustomerRequest::where('customer_id', $user->id)->count(),
                'pending' => CustomerRequest::where('customer_id', $user->id)
                    ->whereIn('status', ['submitted', 'under_review'])->count(),
                'approved' => CustomerRequest::where('customer_id', $user->id)
                    ->where('status', 'approved')->count(),
            ],
            'tickets' => [
                'open' => BugTicket::where('reporter_id', $user->id)
                    ->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            ],
            'invoices' => [
                'unpaid' => Invoice::where('client_id', $user->id)
                    ->whereIn('status', ['sent', 'overdue'])->count(),
                'total_due' => Invoice::where('client_id', $user->id)
                    ->whereIn('status', ['sent', 'overdue'])->sum('total'),
            ],
        ]);
    }

    public function workload(Request $request)
    {
        $developers = User::role('developer')
            ->with(['assignedTasks' => function ($q) {
                $q->whereIn('status', ['todo', 'in_progress'])->with('project');
            }])
            ->get()
            ->map(function ($dev) {
                $taskCount = $dev->assignedTasks->count();
                $estimatedHours = $dev->assignedTasks->sum('estimated_hours');
                return [
                    'user' => $dev->only(['id', 'name', 'email', 'avatar']),
                    'active_tasks' => $taskCount,
                    'estimated_hours' => $estimatedHours,
                    'tasks' => $dev->assignedTasks,
                ];
            });

        return response()->json($developers);
    }
}
