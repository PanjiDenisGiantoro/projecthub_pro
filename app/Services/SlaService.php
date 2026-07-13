<?php

namespace App\Services;

use App\Models\BugTicket;
use App\Models\SlaPause;
use App\Models\SlaLog;
use App\Models\SlaPolicy;

class SlaService
{
    private array $defaults = [
        'critical' => ['response' => 30,   'resolution' => 240,   'escalation' => 75],
        'high'     => ['response' => 120,  'resolution' => 1440,  'escalation' => 75],
        'medium'   => ['response' => 240,  'resolution' => 4320,  'escalation' => 75],
        'low'      => ['response' => 1440, 'resolution' => 10080, 'escalation' => 75],
    ];

    public function applyPolicy(BugTicket $ticket): void
    {
        $policy = SlaPolicy::where('project_id', $ticket->project_id)
            ->where('priority', $ticket->priority)
            ->first()
            ?? SlaPolicy::whereNull('project_id')->where('priority', $ticket->priority)->first();

        $resolutionMinutes = $policy?->resolution_minutes
            ?? $this->defaults[$ticket->priority]['resolution'];

        $ticket->update([
            'sla_policy_id' => $policy?->id,
            'sla_due_at'    => now()->addMinutes($resolutionMinutes),
        ]);
    }

    public function pauseSla(BugTicket $ticket, int $userId, string $reason): SlaPause
    {
        $pause = SlaPause::create([
            'ticket_id' => $ticket->id,
            'paused_by' => $userId,
            'reason'    => $reason,
            'paused_at' => now(),
        ]);

        $ticket->update(['sla_paused' => true, 'sla_paused_at' => now()]);

        SlaLog::create(['ticket_id' => $ticket->id, 'event_type' => 'paused']);

        return $pause;
    }

    public function resumeSla(BugTicket $ticket): void
    {
        $activePause = SlaPause::where('ticket_id', $ticket->id)
            ->whereNull('resumed_at')
            ->first();

        if (!$activePause) return;

        $pausedMinutes = (int) $activePause->paused_at->diffInMinutes(now());
        $activePause->update(['resumed_at' => now()]);

        $newDueAt = $ticket->sla_due_at?->addMinutes($pausedMinutes);

        $ticket->update([
            'sla_due_at'    => $newDueAt,
            'sla_paused'    => false,
            'sla_paused_at' => null,
        ]);

        SlaLog::create(['ticket_id' => $ticket->id, 'event_type' => 'resumed']);
    }

    public function checkBreaches(): void
    {
        BugTicket::whereNotNull('sla_due_at')
            ->where('sla_breached', false)
            ->where('sla_paused', false)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->where('sla_due_at', '<', now())
            ->each(function (BugTicket $ticket) {
                $ticket->update(['sla_breached' => true, 'escalated_at' => now()]);

                SlaLog::create(['ticket_id' => $ticket->id, 'event_type' => 'breached']);

                app(NotificationService::class)->notifyManagers(
                    'sla_breached',
                    'SLA Breach Alert',
                    "Ticket \"{$ticket->title}\" has breached its SLA!",
                    ['ticket_id' => $ticket->id, 'project_id' => $ticket->project_id],
                    companyId: $ticket->project?->company_id
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
            ->where('sla_paused', false)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->with('slaPolicy')
            ->get()
            ->each(function (BugTicket $ticket) {
                $totalMinutes = $ticket->slaPolicy?->resolution_minutes
                    ?? $this->defaults[$ticket->priority]['resolution'];

                $percent = $ticket->slaPolicy?->escalation_at_percent
                    ?? $this->defaults[$ticket->priority]['escalation'];

                $warningAt = now()->addMinutes($totalMinutes - ($totalMinutes * $percent / 100));

                if (now()->gte($warningAt) && !$ticket->slaLogs()->where('event_type', 'warning')->exists()) {
                    SlaLog::create(['ticket_id' => $ticket->id, 'event_type' => 'warning']);

                    app(NotificationService::class)->notifyManagers(
                        'sla_warning',
                        'SLA Warning',
                        "Ticket \"{$ticket->title}\" is at {$percent}% of SLA time.",
                        ['ticket_id' => $ticket->id],
                        companyId: $ticket->project?->company_id
                    );
                }
            });
    }
}
