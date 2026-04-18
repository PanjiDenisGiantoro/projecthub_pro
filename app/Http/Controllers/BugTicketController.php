<?php

namespace App\Http\Controllers;

use App\Exports\TicketsExport;
use App\Models\BugTicket;
use App\Models\Project;
use App\Models\SlaPolicy;
use App\Models\TicketHistory;
use App\Models\TicketLink;
use App\Models\TicketWatcher;
use App\Models\User;
use App\Services\ApprovalService;
use App\Services\NotificationService;
use App\Services\SlaService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class BugTicketController extends Controller
{
    public function __construct(
        private SlaService $slaService,
        private NotificationService $notifier,
        private ApprovalService $approvalService,
    ) {}

    // ─── List & CRUD ─────────────────────────────────────────────────────────

    public function index(Request $request, Project $project)
    {
        $query = $project->tickets()->with(['reporter', 'assignee', 'slaPolicy'])
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->when($request->type,     fn($q) => $q->where('type', $request->type))
            ->when($request->search,   fn($q) => $q->where('title', 'like', "%{$request->search}%"));

        if ($request->user()->hasRole('customer')) {
            $query->where('reporter_id', $request->user()->id);
        }

        return response()->json($query->latest()->paginate(20));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'description' => 'required|string',
            'type'        => 'in:bug,issue,enhancement,security,performance',
            'priority'    => 'in:critical,high,medium,low',
        ]);

        $ticket = $project->tickets()->create([
            ...$request->only('title', 'description', 'type', 'priority'),
            'reporter_id' => $request->user()->id,
            'status'      => 'open',
        ]);

        $this->slaService->applyPolicy($ticket);

        // Auto-watch reporter
        $ticket->watchers()->firstOrCreate(['user_id' => $request->user()->id]);

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
            'histories.actor', 'tasks', 'checklists',
            'outgoingLinks.targetTicket', 'incomingLinks.sourceTicket',
            'mergedInto:id,title,status',
            'pendingApprovals.steps.approver', 'pendingApprovals.policy', 'pendingApprovals.requester',
        ])->append(['sla_remaining_minutes', 'sla_percent_used']));
    }

    public function update(Request $request, BugTicket $ticket)
    {
        $user = $request->user();

        if (!$user->hasAnyRole(['admin', 'manager']) &&
            $ticket->reporter_id !== $user->id &&
            $ticket->assignee_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $request->validate([
            'title'       => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'type'        => 'sometimes|in:bug,issue,enhancement,security,performance',
            'priority'    => 'sometimes|in:critical,high,medium,low',
        ]);

        $trackable = ['title', 'description', 'type', 'priority'];

        foreach ($trackable as $field) {
            if ($request->has($field) && $ticket->$field !== $request->$field) {
                TicketHistory::create([
                    'ticket_id'     => $ticket->id,
                    'actor_id'      => $user->id,
                    'field_changed' => $field,
                    'old_value'     => $ticket->$field,
                    'new_value'     => $request->$field,
                ]);
            }
        }

        $ticket->update($request->only($trackable));

        return response()->json($ticket->fresh());
    }

    // ─── Assign & Status ─────────────────────────────────────────────────────

    public function assign(Request $request, BugTicket $ticket)
    {
        $request->validate(['assignee_id' => 'required|exists:users,id']);

        $old = $ticket->assignee_id;
        $ticket->update(['assignee_id' => $request->assignee_id, 'status' => 'assigned']);

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => $request->user()->id,
            'field_changed' => 'assignee_id',
            'old_value'     => $old,
            'new_value'     => $request->assignee_id,
        ]);

        // Auto-watch assignee
        $ticket->watchers()->firstOrCreate(['user_id' => $request->assignee_id]);

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
        $request->validate([
            'status'      => 'required|in:open,assigned,in_progress,pending_review,resolved,closed,reopened',
            'description' => 'required|string|max:1000',
        ]);

        // Block if there's already a pending approval on this ticket
        if ($this->approvalService->hasPendingApproval($ticket, $request->status === 'resolved' ? 'resolve' : 'close')) {
            return response()->json(['message' => 'There is already a pending approval for this action. Wait for the decision first.'], 422);
        }

        // Intercept: resolved → needs approval
        if ($request->status === 'resolved') {
            $policy = $this->approvalService->needsApproval('ticket', 'resolve');
            if ($policy) {
                $approval = $this->approvalService->createApproval(
                    $ticket, $policy, $request->user(),
                    ['description' => $request->description, 'requested_status' => 'resolved']
                );
                $ticket->update(['status' => 'pending_review']);
                return response()->json([
                    'message'  => 'Approval required. Your resolution request has been submitted.',
                    'approval' => $approval,
                ], 202);
            }
        }

        // Intercept: closed → needs approval
        if ($request->status === 'closed') {
            $policy = $this->approvalService->needsApproval('ticket', 'close');
            if ($policy) {
                $approval = $this->approvalService->createApproval(
                    $ticket, $policy, $request->user(),
                    ['description' => $request->description, 'requested_status' => 'closed']
                );
                return response()->json([
                    'message'  => 'Approval required. Your closure request has been submitted.',
                    'approval' => $approval,
                ], 202);
            }
        }

        // Intercept: in_progress for enhancement type → needs approval
        if ($request->status === 'in_progress' && $ticket->type === 'enhancement') {
            $policy = $this->approvalService->needsApproval('ticket', 'start_enhancement');
            if ($policy && !$this->approvalService->hasPendingApproval($ticket, 'start_enhancement')) {
                $approval = $this->approvalService->createApproval(
                    $ticket, $policy, $request->user(),
                    ['description' => $request->description]
                );
                return response()->json([
                    'message'  => 'Approval required to start this enhancement. Request submitted.',
                    'approval' => $approval,
                ], 202);
            }
        }

        // No approval needed — execute directly
        $old     = $ticket->status;
        $updates = ['status' => $request->status];

        if ($request->status === 'resolved') $updates['resolved_at'] = now();
        if ($request->status === 'closed')   $updates['closed_at']   = now();

        $ticket->update($updates);

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => $request->user()->id,
            'field_changed' => 'status',
            'old_value'     => $old,
            'new_value'     => $request->status,
            'description'   => $request->description,
        ]);

        $ticket->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => "[Status changed: {$old} → {$request->status}]\n{$request->description}",
        ]);

        $this->notifyWatchers(
            $ticket,
            $request->user()->id,
            'ticket_status_changed',
            'Ticket Status Updated',
            "Ticket \"{$ticket->title}\" status changed to {$request->status}."
        );

        return response()->json($ticket->fresh());
    }

    public function requestEscalation(Request $request, BugTicket $ticket)
    {
        $request->validate([
            'new_priority' => 'required|in:critical,high,medium,low',
            'reason'       => 'required|string|max:500',
        ]);

        if ($request->new_priority === $ticket->priority) {
            return response()->json(['message' => 'New priority is the same as current priority.'], 422);
        }

        if ($this->approvalService->hasPendingApproval($ticket, 'escalate_priority')) {
            return response()->json(['message' => 'There is already a pending escalation request.'], 422);
        }

        $policy = $this->approvalService->needsApproval('ticket', 'escalate_priority');

        if (!$policy) {
            // No policy — escalate directly
            $old = $ticket->priority;
            $ticket->update(['priority' => $request->new_priority]);
            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $request->user()->id,
                'field_changed' => 'priority',
                'old_value'     => $old,
                'new_value'     => $request->new_priority,
                'description'   => $request->reason,
            ]);
            return response()->json($ticket->fresh());
        }

        $approval = $this->approvalService->createApproval(
            $ticket, $policy, $request->user(),
            ['new_priority' => $request->new_priority, 'old_priority' => $ticket->priority, 'reason' => $request->reason]
        );

        return response()->json([
            'message'  => 'Priority escalation request submitted for approval.',
            'approval' => $approval,
        ], 202);
    }

    public function requestSlaExtension(Request $request, BugTicket $ticket)
    {
        $request->validate([
            'new_due_at' => 'required|date|after:now',
            'reason'     => 'required|string|max:500',
        ]);

        if ($this->approvalService->hasPendingApproval($ticket, 'sla_extension')) {
            return response()->json(['message' => 'There is already a pending SLA extension request.'], 422);
        }

        $policy = $this->approvalService->needsApproval('ticket', 'sla_extension');

        if (!$policy) {
            $ticket->update(['sla_due_at' => $request->new_due_at, 'sla_breached' => false]);
            return response()->json($ticket->fresh());
        }

        $approval = $this->approvalService->createApproval(
            $ticket, $policy, $request->user(),
            ['new_due_at' => $request->new_due_at, 'old_due_at' => $ticket->sla_due_at, 'reason' => $request->reason]
        );

        return response()->json([
            'message'  => 'SLA extension request submitted for approval.',
            'approval' => $approval,
        ], 202);
    }

    public function requestSecurityDisclose(Request $request, BugTicket $ticket)
    {
        if ($ticket->type !== 'security') {
            return response()->json(['message' => 'Only security-type tickets can be disclosed through this flow.'], 422);
        }

        if ($this->approvalService->hasPendingApproval($ticket, 'security_disclose')) {
            return response()->json(['message' => 'A disclosure request is already pending.'], 422);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $policy = $this->approvalService->needsApproval('ticket', 'security_disclose');

        if (!$policy) {
            return response()->json(['message' => 'No security disclosure policy configured.'], 422);
        }

        $approval = $this->approvalService->createApproval(
            $ticket, $policy, $request->user(),
            ['reason' => $request->reason]
        );

        return response()->json([
            'message'  => 'Security disclosure request submitted for approval.',
            'approval' => $approval,
        ], 202);
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
            'body'    => 'Reopened: ' . $request->reason,
        ]);

        return response()->json($ticket->fresh());
    }

    // ─── Bulk Actions ─────────────────────────────────────────────────────────

    public function bulkUpdate(Request $request, Project $project)
    {
        $request->validate([
            'ticket_ids'   => 'required|array|min:1',
            'ticket_ids.*' => 'exists:bug_tickets,id',
            'action'       => 'required|in:status,assign,priority',
            'value'        => 'required',
            'description'  => 'nullable|string|max:500',
        ]);

        $tickets = BugTicket::whereIn('id', $request->ticket_ids)
            ->where('project_id', $project->id)
            ->get();

        foreach ($tickets as $ticket) {
            $fieldChanged = $request->action === 'assign' ? 'assignee_id' : $request->action;
            $old          = $ticket->$fieldChanged;

            $updates = match ($request->action) {
                'status'   => array_merge(
                    ['status' => $request->value],
                    $request->value === 'resolved' ? ['resolved_at' => now()] : [],
                    $request->value === 'closed'   ? ['closed_at'   => now()] : [],
                ),
                'assign'   => ['assignee_id' => $request->value, 'status' => 'assigned'],
                'priority' => ['priority' => $request->value],
            };

            $ticket->update($updates);

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $request->user()->id,
                'field_changed' => $fieldChanged,
                'old_value'     => $old,
                'new_value'     => $request->value,
                'description'   => $request->description,
            ]);
        }

        return response()->json([
            'updated' => $tickets->count(),
            'message' => "Bulk {$request->action} applied to {$tickets->count()} tickets.",
        ]);
    }

    // ─── SLA Pause / Resume ───────────────────────────────────────────────────

    public function pauseSla(Request $request, BugTicket $ticket)
    {
        if ($ticket->sla_paused) {
            return response()->json(['message' => 'SLA is already paused.'], 422);
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $pause = $this->slaService->pauseSla($ticket, $request->user()->id, $request->reason);

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => $request->user()->id,
            'field_changed' => 'sla_paused',
            'old_value'     => 'false',
            'new_value'     => 'true',
            'description'   => $request->reason,
        ]);

        return response()->json(['message' => 'SLA paused.', 'pause' => $pause]);
    }

    public function resumeSla(Request $request, BugTicket $ticket)
    {
        if (!$ticket->sla_paused) {
            return response()->json(['message' => 'SLA is not paused.'], 422);
        }

        $this->slaService->resumeSla($ticket);

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => $request->user()->id,
            'field_changed' => 'sla_paused',
            'old_value'     => 'true',
            'new_value'     => 'false',
        ]);

        return response()->json(['message' => 'SLA resumed.', 'ticket' => $ticket->fresh()]);
    }

    // ─── Merge ────────────────────────────────────────────────────────────────

    public function mergeInto(Request $request, BugTicket $ticket)
    {
        $request->validate([
            'target_ticket_id' => 'required|exists:bug_tickets,id',
            'reason'           => 'required|string',
        ]);

        if ($request->target_ticket_id == $ticket->id) {
            return response()->json(['message' => 'Cannot merge ticket into itself.'], 422);
        }

        $target = BugTicket::findOrFail($request->target_ticket_id);

        $ticket->comments()->update(['ticket_id' => $target->id]);

        $ticket->update([
            'status'         => 'closed',
            'merged_into_id' => $target->id,
            'closed_at'      => now(),
        ]);

        $target->comments()->create([
            'user_id' => $request->user()->id,
            'body'    => "Ticket #{$ticket->id} merged into this ticket. Reason: {$request->reason}",
        ]);

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => $request->user()->id,
            'field_changed' => 'merged_into_id',
            'old_value'     => null,
            'new_value'     => $target->id,
            'description'   => $request->reason,
        ]);

        return response()->json([
            'message' => "Ticket #{$ticket->id} merged into #{$target->id}.",
            'target'  => $target,
        ]);
    }

    // ─── Comments ─────────────────────────────────────────────────────────────

    public function addComment(Request $request, BugTicket $ticket)
    {
        $request->validate(['body' => 'required|string']);

        $comment = $ticket->comments()->create([
            'user_id'         => $request->user()->id,
            'body'            => $request->body,
            'attachment_path' => $request->attachment_path,
        ]);

        // Parse @mentions by username
        preg_match_all('/@([\w.]+)/', $request->body, $matches);
        if (!empty($matches[1])) {
            User::whereIn('name', $matches[1])->get()
                ->each(function (User $mentioned) use ($request, $ticket, $comment) {
                    if ($mentioned->id === $request->user()->id) return;
                    $this->notifier->send(
                        $mentioned->id,
                        'ticket_mention',
                        'You were mentioned in a ticket',
                        "{$request->user()->name} mentioned you in \"{$ticket->title}\"",
                        ['ticket_id' => $ticket->id, 'comment_id' => $comment->id]
                    );
                });
        }

        // Notify watchers
        $this->notifyWatchers(
            $ticket,
            $request->user()->id,
            'ticket_comment',
            'New Comment on Watched Ticket',
            "{$request->user()->name} commented on \"{$ticket->title}\""
        );

        // Auto-watch commenter
        $ticket->watchers()->firstOrCreate(['user_id' => $request->user()->id]);

        return response()->json($comment->load('user'), 201);
    }

    public function history(BugTicket $ticket)
    {
        return response()->json($ticket->histories()->with('actor')->latest()->get());
    }

    // ─── Watchers ─────────────────────────────────────────────────────────────

    public function watch(Request $request, BugTicket $ticket)
    {
        $ticket->watchers()->firstOrCreate(['user_id' => $request->user()->id]);
        return response()->json(['message' => 'Now watching this ticket.']);
    }

    public function unwatch(Request $request, BugTicket $ticket)
    {
        $ticket->watchers()->where('user_id', $request->user()->id)->delete();
        return response()->json(['message' => 'Unwatched ticket.']);
    }

    public function listWatchers(BugTicket $ticket)
    {
        return response()->json($ticket->watchers()->with('user:id,name,email')->get());
    }

    // ─── Linked Tickets ───────────────────────────────────────────────────────

    public function linkTicket(Request $request, BugTicket $ticket)
    {
        $request->validate([
            'target_ticket_id' => 'required|exists:bug_tickets,id',
            'link_type'        => 'required|in:blocks,blocked_by,duplicates,duplicated_by,relates_to,caused_by,causes',
        ]);

        if ($request->target_ticket_id == $ticket->id) {
            return response()->json(['message' => 'Cannot link a ticket to itself.'], 422);
        }

        $inverseMap = [
            'blocks'       => 'blocked_by',
            'blocked_by'   => 'blocks',
            'duplicates'   => 'duplicated_by',
            'duplicated_by'=> 'duplicates',
            'caused_by'    => 'causes',
            'causes'       => 'caused_by',
            'relates_to'   => 'relates_to',
        ];

        $link = TicketLink::firstOrCreate(
            ['source_ticket_id' => $ticket->id, 'target_ticket_id' => $request->target_ticket_id, 'link_type' => $request->link_type],
            ['created_by' => $request->user()->id]
        );

        TicketLink::firstOrCreate(
            ['source_ticket_id' => $request->target_ticket_id, 'target_ticket_id' => $ticket->id, 'link_type' => $inverseMap[$request->link_type]],
            ['created_by' => $request->user()->id]
        );

        TicketHistory::create([
            'ticket_id'     => $ticket->id,
            'actor_id'      => $request->user()->id,
            'field_changed' => 'linked_ticket',
            'old_value'     => null,
            'new_value'     => $request->target_ticket_id,
            'description'   => "Linked as '{$request->link_type}' to ticket #{$request->target_ticket_id}",
        ]);

        return response()->json($link->load(['targetTicket', 'creator']), 201);
    }

    public function unlinkTicket(Request $request, BugTicket $ticket, TicketLink $link)
    {
        if ($link->source_ticket_id !== $ticket->id) {
            return response()->json(['message' => 'Link not found on this ticket.'], 404);
        }

        $inverseMap = [
            'blocks'       => 'blocked_by',
            'blocked_by'   => 'blocks',
            'duplicates'   => 'duplicated_by',
            'duplicated_by'=> 'duplicates',
            'caused_by'    => 'causes',
            'causes'       => 'caused_by',
            'relates_to'   => 'relates_to',
        ];

        TicketLink::where('source_ticket_id', $link->target_ticket_id)
            ->where('target_ticket_id', $link->source_ticket_id)
            ->where('link_type', $inverseMap[$link->link_type])
            ->delete();

        $link->delete();

        return response()->json(['message' => 'Link removed.']);
    }

    public function linkedTickets(BugTicket $ticket)
    {
        $outgoing = $ticket->outgoingLinks()->with(['targetTicket:id,title,status,priority,type', 'creator:id,name'])->get()
            ->map(fn($l) => [...$l->toArray(), 'direction' => 'outgoing']);

        $incoming = $ticket->incomingLinks()->with(['sourceTicket:id,title,status,priority,type', 'creator:id,name'])->get()
            ->map(fn($l) => [...$l->toArray(), 'direction' => 'incoming']);

        return response()->json(['outgoing' => $outgoing, 'incoming' => $incoming]);
    }

    // ─── Reports ──────────────────────────────────────────────────────────────

    public function slaReport(Project $project)
    {
        $tickets = $project->tickets()->with('slaPolicy')->get();

        return response()->json([
            'total'             => $tickets->count(),
            'breached'          => $tickets->where('sla_breached', true)->count(),
            'paused'            => $tickets->where('sla_paused', true)->count(),
            'resolved_in_time'  => $tickets->where('sla_breached', false)->whereNotNull('resolved_at')->count(),
            'open'              => $tickets->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
            'compliance_rate'   => $tickets->count() > 0
                ? round((1 - $tickets->where('sla_breached', true)->count() / $tickets->count()) * 100, 2)
                : 100,
        ]);
    }

    public function agingReport(Project $project)
    {
        $tickets = $project->tickets()
            ->with(['assignee:id,name'])
            ->whereNotIn('status', ['resolved', 'closed'])
            ->get()
            ->map(fn($t) => [
                'id'          => $t->id,
                'title'       => $t->title,
                'status'      => $t->status,
                'priority'    => $t->priority,
                'assignee'    => $t->assignee?->name ?? 'Unassigned',
                'age_days'    => $t->created_at->diffInDays(now()),
                'sla_breached'=> $t->sla_breached,
            ])
            ->sortByDesc('age_days')
            ->values();

        return response()->json([
            'over_30_days' => $tickets->where('age_days', '>=', 30)->count(),
            'over_14_days' => $tickets->where('age_days', '>=', 14)->count(),
            'over_7_days'  => $tickets->where('age_days', '>=', 7)->count(),
            'tickets'      => $tickets,
        ]);
    }

    public function workloadReport(Project $project)
    {
        $grouped = $project->tickets()
            ->with('assignee:id,name')
            ->get()
            ->groupBy('assignee_id');

        $report = $grouped->map(function ($group) {
            $first = $group->first();
            $resolvedGroup = $group->whereNotNull('resolved_at');

            return [
                'assignee_id'        => $first->assignee_id,
                'assignee'           => $first->assignee?->name ?? 'Unassigned',
                'total'              => $group->count(),
                'open'               => $group->whereIn('status', ['open', 'assigned', 'in_progress'])->count(),
                'resolved'           => $resolvedGroup->count(),
                'avg_resolution_days'=> $resolvedGroup->count()
                    ? round($resolvedGroup->avg(fn($t) => $t->created_at->diffInHours($t->resolved_at)) / 24, 1)
                    : null,
                'sla_breached'       => $group->where('sla_breached', true)->count(),
            ];
        })->values();

        return response()->json($report);
    }

    public function trendReport(Request $request, Project $project)
    {
        $weeks = min((int) $request->get('weeks', 12), 52);
        $start = now()->subWeeks($weeks)->startOfWeek();

        $opened   = $project->tickets()->where('created_at', '>=', $start)->get();
        $resolved = $project->tickets()->whereNotNull('resolved_at')->where('resolved_at', '>=', $start)->get();

        $trend = collect(range($weeks - 1, 0))->map(function ($i) use ($opened, $resolved) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd   = $weekStart->copy()->endOfWeek();

            return [
                'week'     => $weekStart->format('M d'),
                'opened'   => $opened->filter(fn($t) => $t->created_at->between($weekStart, $weekEnd))->count(),
                'resolved' => $resolved->filter(fn($t) => $t->resolved_at->between($weekStart, $weekEnd))->count(),
            ];
        })->values();

        return response()->json([
            'trend'       => $trend,
            'by_type'     => $opened->groupBy('type')->map->count(),
            'by_priority' => $opened->groupBy('priority')->map->count(),
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

    // ─── Export ───────────────────────────────────────────────────────────────

    public function export(Request $request, Project $project)
    {
        $tickets = $project->tickets()
            ->with(['reporter:id,name', 'assignee:id,name'])
            ->when($request->status,   fn($q) => $q->where('status', $request->status))
            ->when($request->priority, fn($q) => $q->where('priority', $request->priority))
            ->get();

        $filename = "tickets-project-{$project->id}-" . now()->format('Ymd') . '.xlsx';

        return Excel::download(new TicketsExport($tickets), $filename);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function notifyWatchers(BugTicket $ticket, int $actorId, string $type, string $title, string $message): void
    {
        $ticket->watchers()
            ->where('user_id', '!=', $actorId)
            ->pluck('user_id')
            ->each(fn($uid) => $this->notifier->send($uid, $type, $title, $message, ['ticket_id' => $ticket->id]));
    }
}
