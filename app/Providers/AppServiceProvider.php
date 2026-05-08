<?php

namespace App\Providers;

use App\Models\Approval;
use App\Models\BugTicket;
use App\Models\TicketHistory;
use App\Services\ApprovalService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(ApprovalService::class);

        if ($this->app->environment('local') && class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)) {
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
        }
    }

    public function boot(): void
    {
        // Admin bypasses ALL permission checks
        Gate::before(fn($user) => $user->hasRole('admin') ? true : null);

        $this->registerApprovalHandlers();
    }

    private function registerApprovalHandlers(): void
    {
        $svc = $this->app->make(ApprovalService::class);
        $notifier = $this->app->make(NotificationService::class);

        // ── ticket.resolve ────────────────────────────────────────────────────
        $svc->registerHandler('ticket', 'resolve', function (Approval $approval) use ($notifier) {
            /** @var BugTicket $ticket */
            $ticket = $approval->approvable;

            $ticket->update(['status' => 'resolved', 'resolved_at' => now()]);

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $approval->requested_by,
                'field_changed' => 'status',
                'old_value'     => 'pending_review',
                'new_value'     => 'resolved',
                'description'   => 'Approved via approval #' . $approval->id,
            ]);

            $ticket->watchers()->where('user_id', '!=', $approval->requested_by)
                ->pluck('user_id')
                ->each(fn($uid) => $notifier->send(
                    $uid, 'ticket_resolved', 'Ticket Resolved',
                    "Ticket \"{$ticket->title}\" has been approved and marked as resolved.",
                    ['ticket_id' => $ticket->id]
                ));
        });

        // ── ticket.close ──────────────────────────────────────────────────────
        $svc->registerHandler('ticket', 'close', function (Approval $approval) {
            /** @var BugTicket $ticket */
            $ticket = $approval->approvable;

            $ticket->update(['status' => 'closed', 'closed_at' => now()]);

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $approval->requested_by,
                'field_changed' => 'status',
                'old_value'     => $ticket->getOriginal('status'),
                'new_value'     => 'closed',
                'description'   => 'Approved via approval #' . $approval->id,
            ]);
        });

        // ── ticket.escalate_priority ──────────────────────────────────────────
        $svc->registerHandler('ticket', 'escalate_priority', function (Approval $approval) use ($notifier) {
            /** @var BugTicket $ticket */
            $ticket = $approval->approvable;
            $meta   = $approval->metadata;

            $old = $ticket->priority;
            $ticket->update(['priority' => $meta['new_priority']]);

            // Re-apply SLA for new priority
            app(\App\Services\SlaService::class)->applyPolicy($ticket->fresh());

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $approval->requested_by,
                'field_changed' => 'priority',
                'old_value'     => $old,
                'new_value'     => $meta['new_priority'],
                'description'   => 'Priority escalation approved. Reason: ' . ($meta['reason'] ?? '-'),
            ]);

            if ($ticket->assignee_id) {
                $notifier->send(
                    $ticket->assignee_id,
                    'ticket_escalated',
                    'Ticket Priority Escalated',
                    "Ticket \"{$ticket->title}\" priority changed from {$old} to {$meta['new_priority']}.",
                    ['ticket_id' => $ticket->id]
                );
            }
        });

        // ── ticket.sla_extension ──────────────────────────────────────────────
        $svc->registerHandler('ticket', 'sla_extension', function (Approval $approval) {
            /** @var BugTicket $ticket */
            $ticket = $approval->approvable;
            $meta   = $approval->metadata;

            $old = $ticket->sla_due_at?->toDateTimeString();
            $ticket->update(['sla_due_at' => $meta['new_due_at'], 'sla_breached' => false]);

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $approval->requested_by,
                'field_changed' => 'sla_due_at',
                'old_value'     => $old,
                'new_value'     => $meta['new_due_at'],
                'description'   => 'SLA extension approved. Reason: ' . ($meta['reason'] ?? '-'),
            ]);
        });

        // ── ticket.start_enhancement ──────────────────────────────────────────
        $svc->registerHandler('ticket', 'start_enhancement', function (Approval $approval) use ($notifier) {
            /** @var BugTicket $ticket */
            $ticket = $approval->approvable;

            $ticket->update(['status' => 'in_progress']);

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $approval->requested_by,
                'field_changed' => 'status',
                'old_value'     => 'assigned',
                'new_value'     => 'in_progress',
                'description'   => 'Enhancement start approved via approval #' . $approval->id,
            ]);

            if ($ticket->assignee_id) {
                $notifier->send(
                    $ticket->assignee_id,
                    'ticket_start_approved',
                    'Enhancement Approved to Start',
                    "Enhancement ticket \"{$ticket->title}\" has been approved. You can now start working on it.",
                    ['ticket_id' => $ticket->id]
                );
            }
        });

        // ── ticket.security_disclose ──────────────────────────────────────────
        $svc->registerHandler('ticket', 'security_disclose', function (Approval $approval) use ($notifier) {
            /** @var BugTicket $ticket */
            $ticket = $approval->approvable;

            TicketHistory::create([
                'ticket_id'     => $ticket->id,
                'actor_id'      => $approval->requested_by,
                'field_changed' => 'security_disclosed',
                'old_value'     => 'false',
                'new_value'     => 'true',
                'description'   => 'Security disclosure approved via approval #' . $approval->id,
            ]);

            // Notify all project members
            $notifier->notifyManagers(
                'security_disclosed',
                'Security Ticket Disclosed',
                "Security ticket \"{$ticket->title}\" has been approved for disclosure.",
                ['ticket_id' => $ticket->id, 'project_id' => $ticket->project_id]
            );
        });
    }
}
