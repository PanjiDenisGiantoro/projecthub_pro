<?php

namespace App\Services;

use App\Models\BugTicket;
use App\Models\SlaLog;
use App\Models\SlaPolicy;
use App\Models\User;

class SlaService
{
    // Default SLA policies (minutes) if no project-specific policy exists
    private array $defaults = [
        'critical' => ['response' => 30,  'resolution' => 240,   'escalation' => 75],
        'high'     => ['response' => 120, 'resolution' => 1440,  'escalation' => 75],
        'medium'   => ['response' => 240, 'resolution' => 4320,  'escalation' => 75],
        'low'      => ['response' => 1440,'resolution' => 10080, 'escalation' => 75],
    ];

    public function applyPolicy(BugTicket $ticket): void
    {
        $policy = SlaPolicy::where('project_id', $ticket->project_id)
            ->where('priority', $ticket->priority)
            ->first();

        if (!$policy) {
            $policy = SlaPolicy::whereNull('project_id')
                ->where('priority', $ticket->priority)
                ->first();
        }

        $resolutionMinutes = $policy?->resolution_minutes
            ?? $this->defaults[$ticket->priority]['resolution'];

        $ticket->update([
            'sla_policy_id' => $policy?->id,
            'sla_due_at' => now()->addMinutes($resolutionMinutes),
        ]);
    }

    public function checkBreaches(): void
    {
        BugTicket::whereNotNull('sla_due_at')
            ->where('sla_breached', false)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->where('sla_due_at', '<', now())
            ->each(function (BugTicket $ticket) {
                $ticket->update(['sla_breached' => true, 'escalated_at' => now()]);

                SlaLog::create([
                    'ticket_id' => $ticket->id,
                    'event_type' => 'breached',
                ]);

                app(NotificationService::class)->notifyManagers(
                    'sla_breached',
                    'SLA Breach Alert',
                    "Ticket \"{$ticket->title}\" has breached its SLA!",
                    ['ticket_id' => $ticket->id, 'project_id' => $ticket->project_id]
                );

                if ($ticket->assignee_id) {
                    app(NotificationService::class)->send(
                        $ticket->assignee_id,
                        'sla_breached',
                        'SLA Breach on Your Ticket',
                        "Ticket \"{$ticket->title}\" SLA has been breached.",
                        ['ticket_id' => $ticket->id]
                    );
                }
            });
    }

    public function sendWarnings(): void
    {
        BugTicket::whereNotNull('sla_due_at')
            ->where('sla_breached', false)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with('slaPolicy')
            ->get()
            ->each(function (BugTicket $ticket) {
                $totalMinutes = $ticket->slaPolicy?->resolution_minutes
                    ?? $this->defaults[$ticket->priority]['resolution'];

                $percent = $ticket->slaPolicy?->escalation_at_percent
                    ?? $this->defaults[$ticket->priority]['escalation'];

                $warningAt = now()->addMinutes($totalMinutes - ($totalMinutes * $percent / 100));

                if (now()->gte($warningAt)) {
                    $alreadyWarned = $ticket->slaLogs()
                        ->where('event_type', 'warning')
                        ->exists();

                    if (!$alreadyWarned) {
                        SlaLog::create([
                            'ticket_id' => $ticket->id,
                            'event_type' => 'warning',
                        ]);

                        app(NotificationService::class)->notifyManagers(
                            'sla_warning',
                            'SLA Warning',
                            "Ticket \"{$ticket->title}\" is at {$percent}% of SLA time.",
                            ['ticket_id' => $ticket->id]
                        );
                    }
                }
            });
    }
}
