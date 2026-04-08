<?php

namespace App\Http\Controllers;

use App\Models\BugTicket;
use App\Models\Project;
use App\Models\SlaPolicy;
use App\Models\TicketHistory;
use App\Services\NotificationService;
use App\Services\SlaService;
use Illuminate\Http\Request;

class BugTicketController extends Controller
{
    public function __construct(
        private SlaService $slaService,
        private NotificationService $notifier
    ) {}

    public function index(Request $request, Project $project)
    {
        $query = $project->tickets()->with(['reporter', 'assignee', 'slaPolicy'])
            ->when($request->status, fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->type, fn($q) => $q->where('type', $request->type));

        if ($request->user()->hasRole('customer')) {
            $query->where('reporter_id', $request->user()->id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'type' => 'in:bug,issue,enhancement,security,performance',
            'priority' => 'in:critical,high,medium,low',
        ]);

        $ticket = $project->tickets()->create([
            ...$request->only('title', 'description', 'type', 'priority'),
            'reporter_id' => $request->user()->id,
            'status' => 'open',
        ]);

        $this->slaService->applyPolicy($ticket);

        $this->notifier->notifyManagers(
            'new_ticket',
            'New Bug Ticket',
            "New {$ticket->priority} ticket: {$ticket->title}",
            ['ticket_id' => $ticket->id, 'project_id' => $project->id]
        );

        return response()->json($ticket->load(['reporter', 'slaPolicy']), 201);
    }

    public function show(BugTicket $ticket)
    {
        return response()->json($ticket->load([
            'reporter', 'assignee', 'slaPolicy', 'comments.user',
            'histories.actor', 'tasks',
        ])->append(['sla_remaining_minutes', 'sla_percent_used']));
    }

    public function assign(Request $request, BugTicket $ticket)
    {
        $request->validate(['assignee_id' => 'required|exists:users,id']);

        $old = $ticket->assignee_id;
        $ticket->update(['assignee_id' => $request->assignee_id, 'status' => 'assigned']);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'actor_id' => $request->user()->id,
            'field_changed' => 'assignee_id',
            'old_value' => $old,
            'new_value' => $request->assignee_id,
        ]);

        $this->notifier->send(
            $request->assignee_id,
            'ticket_assigned',
            'Ticket Assigned to You',
            "Ticket \"{$ticket->title}\" has been assigned to you.",
            ['ticket_id' => $ticket->id]
        );

        return response()->json($ticket->fresh()->load(['assignee']));
    }

    public function updateStatus(Request $request, BugTicket $ticket)
    {
        $request->validate(['status' => 'required|in:open,assigned,in_progress,pending_review,resolved,closed,reopened']);

        $old = $ticket->status;
        $updates = ['status' => $request->status];

        if ($request->status === 'resolved') $updates['resolved_at'] = now();
        if ($request->status === 'closed') $updates['closed_at'] = now();

        $ticket->update($updates);

        TicketHistory::create([
            'ticket_id' => $ticket->id,
            'actor_id' => $request->user()->id,
            'field_changed' => 'status',
            'old_value' => $old,
            'new_value' => $request->status,
        ]);

        return response()->json($ticket->fresh());
    }

    public function addComment(Request $request, BugTicket $ticket)
    {
        $request->validate(['body' => 'required|string']);

        $comment = $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => $request->body,
            'attachment_path' => $request->attachment_path,
        ]);

        return response()->json($comment->load('user'), 201);
    }

    public function history(BugTicket $ticket)
    {
        return response()->json($ticket->histories()->with('actor')->latest()->get());
    }

    public function reopen(Request $request, BugTicket $ticket)
    {
        if ($ticket->status !== 'closed') {
            return response()->json(['message' => 'Only closed tickets can be reopened.'], 422);
        }

        if ($ticket->closed_at && $ticket->closed_at->diffInDays(now()) > 7) {
            return response()->json(['message' => 'Reopen window (7 days) has expired.'], 422);
        }

        $request->validate(['reason' => 'required|string']);

        $ticket->update(['status' => 'reopened', 'closed_at' => null]);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body' => 'Reopened: ' . $request->reason,
        ]);

        return response()->json($ticket->fresh());
    }

    public function slaReport(Project $project)
    {
        $tickets = $project->tickets()->with('slaPolicy')->get();

        return response()->json([
            'total' => $tickets->count(),
            'breached' => $tickets->where('sla_breached', true)->count(),
            'resolved_in_time' => $tickets->where('sla_breached', false)->whereNotNull('resolved_at')->count(),
            'open' => $tickets->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'compliance_rate' => $tickets->count() > 0
                ? round((1 - $tickets->where('sla_breached', true)->count() / $tickets->count()) * 100, 2)
                : 100,
        ]);
    }

    public function breached(Request $request)
    {
        $tickets = BugTicket::with(['project', 'assignee', 'reporter'])
            ->where('sla_breached', true)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->latest()
            ->paginate(20);

        return response()->json($tickets);
    }
}
