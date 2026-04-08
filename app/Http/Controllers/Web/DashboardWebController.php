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

class DashboardWebController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if ($user->hasRole(['admin', 'manager'])) {
            $data = [
                'stats' => [
                    'projects'        => ['total' => Project::count(), 'active' => Project::where('status', 'active')->count(), 'completed' => Project::where('status', 'completed')->count()],
                    'tasks'           => ['total' => Task::count(), 'in_progress' => Task::where('status', 'in_progress')->count(), 'done' => Task::where('status', 'done')->count()],
                    'tickets'         => ['open' => BugTicket::whereIn('status', ['open', 'assigned'])->count(), 'breached' => BugTicket::where('sla_breached', true)->count()],
                    'pending_requests'=> CustomerRequest::whereIn('status', ['submitted', 'under_review'])->count(),
                    'revenue'         => ['total' => Invoice::where('status', 'paid')->sum('total'), 'overdue' => Invoice::where('status', 'overdue')->count()],
                ],
                'recent_projects' => Project::with(['client', 'manager'])->latest()->limit(5)->get(),
                'recent_tickets'  => BugTicket::with(['project', 'reporter'])->whereIn('status', ['open', 'assigned'])->latest()->limit(5)->get(),
                'recent_requests' => CustomerRequest::with(['customer', 'project'])->whereIn('status', ['submitted', 'under_review'])->latest()->limit(5)->get(),
            ];
            return view('dashboard.manager', $data);
        }

        if ($user->hasRole('developer')) {
            $data = [
                'my_tasks'     => Task::where('assigned_to', $user->id)->whereIn('status', ['todo', 'in_progress'])->with(['project', 'milestone'])->orderBy('due_date')->limit(10)->get(),
                'stats'        => [
                    'todo'        => Task::where('assigned_to', $user->id)->where('status', 'todo')->count(),
                    'in_progress' => Task::where('assigned_to', $user->id)->where('status', 'in_progress')->count(),
                    'done_week'   => Task::where('assigned_to', $user->id)->where('status', 'done')->whereBetween('updated_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                    'hours_week'  => round(TimeLog::where('user_id', $user->id)->whereBetween('started_at', [now()->startOfWeek(), now()->endOfWeek()])->sum('minutes') / 60, 1),
                ],
            ];
            return view('dashboard.developer', $data);
        }

        if ($user->hasRole('marketing')) {
            $data = [
                'campaigns'        => Campaign::with('project')->latest()->limit(5)->get(),
                'stats'            => [
                    'active_campaigns'  => Campaign::where('status', 'active')->count(),
                    'pending_review'    => CustomerRequest::where('status', 'submitted')->count(),
                ],
            ];
            return view('dashboard.marketing', $data);
        }

        if ($user->hasRole('customer')) {
            $data = [
                'projects' => Project::where('client_id', $user->id)->with('milestones')->get(),
                'stats'    => [
                    'pending_requests' => CustomerRequest::where('customer_id', $user->id)->whereIn('status', ['submitted', 'under_review'])->count(),
                    'open_tickets'     => BugTicket::where('reporter_id', $user->id)->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                    'unpaid_invoices'  => Invoice::where('client_id', $user->id)->whereIn('status', ['sent', 'overdue'])->count(),
                ],
                'recent_requests' => CustomerRequest::where('customer_id', $user->id)->latest()->limit(5)->get(),
            ];
            return view('dashboard.customer', $data);
        }

        return view('dashboard.manager', []);
    }

    public function workload()
    {
        $developers = User::role('developer')
            ->with(['assignedTasks' => fn($q) => $q->whereIn('status', ['todo', 'in_progress'])->with('project')])
            ->get();

        return view('workload', compact('developers'));
    }
}
