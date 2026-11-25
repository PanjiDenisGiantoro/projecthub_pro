<?php

namespace App\Services;

use App\Models\BugTicket;
use App\Models\Holiday;
use App\Models\Project;
use App\Models\SlaLog;
use App\Models\SlaPause;
use App\Models\SlaPolicy;
use Illuminate\Support\Carbon;

/**
 * SLA tiket bug.
 *
 * Tiket tidak punya end date manual — sla_due_at ("target selesai") dihitung
 * dari aturan SLA sesuai prioritas. Aturan diatur per proyek di tab Tickets.
 *
 * Urutan aturan yang dipakai (yang pertama ketemu):
 *   1. aturan proyek ini      (sla_policies.project_id = proyek)
 *   2. aturan default sistem  (sla_policies.project_id NULL)
 *   3. SlaPolicy::PRIORITIES  (hardcoded)
 *
 * business_hours_only = hitung hanya jam kerja (Senin–Jumat 08:00–17:00, di luar
 * hari libur company di HRIS → Holiday).
 */
class SlaService
{
    public const WORK_START = 8;   // 08:00
    public const WORK_END   = 17;  // 17:00

    public const ESCALATION_DEFAULT = 75;

    public function resolvePolicy(?int $projectId, string $priority): ?SlaPolicy
    {
        $candidates = SlaPolicy::where('priority', $priority)
            ->where(fn ($q) => $q->whereNull('project_id')->when($projectId, fn ($q2) => $q2->orWhere('project_id', $projectId)))
            ->orderBy('id')
            ->get();

        return ($projectId ? $candidates->firstWhere('project_id', $projectId) : null)
            ?? $candidates->first(fn ($p) => $p->project_id === null);
    }

    /** Aturan efektif (policy atau default hardcoded) dalam bentuk array seragam. */
    public function effectiveRule(?int $projectId, string $priority): array
    {
        $policy   = $this->resolvePolicy($projectId, $priority);
        $fallback = SlaPolicy::PRIORITIES[$priority] ?? SlaPolicy::PRIORITIES['medium'];

        return [
            'priority'       => $priority,
            'policy'         => $policy,
            'source'         => $policy && $policy->project_id ? 'project' : 'default',
            'response'       => (int) ($policy?->response_minutes ?? $fallback['response']),
            'resolution'     => (int) ($policy?->resolution_minutes ?? $fallback['resolution']),
            'escalation'     => (int) ($policy?->escalation_at_percent ?? self::ESCALATION_DEFAULT),
            'business_hours' => (bool) ($policy?->business_hours_only ?? false),
        ];
    }

    /** Aturan untuk ke-4 prioritas sebuah proyek (urut critical → low). */
    public function rulesForProject(?int $projectId): array
    {
        return collect(array_keys(SlaPolicy::PRIORITIES))
            ->mapWithKeys(fn ($p) => [$p => $this->effectiveRule($projectId, $p)])
            ->all();
    }

    private function companyIdOf(BugTicket $ticket): ?int
    {
        return Project::withoutGlobalScopes()->whereKey($ticket->project_id)->value('company_id');
    }

    /**
     * Isi atribut SLA tiket (TIDAK menyimpan). Dihitung dari waktu tiket dibuat,
     * ditambah total durasi pause yang sudah selesai — jadi aman dipanggil ulang
     * saat prioritas/aturan berubah tanpa "mereset" waktu yang sudah berjalan.
     */
    public function fillDueDates(BugTicket $ticket): void
    {
        $rule   = $this->effectiveRule($ticket->project_id, $ticket->priority ?? 'medium');
        $start  = Carbon::parse($ticket->created_at ?? now());
        $paused = $ticket->exists
            ? (int) SlaPause::where('ticket_id', $ticket->id)->whereNotNull('resumed_at')->get()
                ->sum(fn ($p) => $p->paused_at->diffInMinutes($p->resumed_at))
            : 0;

        $ticket->sla_policy_id = $rule['policy']?->id;
        $ticket->sla_due_at    = $this->addMinutes($start, $rule['resolution'], $rule['business_hours'], $this->companyIdOf($ticket))
            ->addMinutes($paused);

        if (!in_array($ticket->status, ['resolved', 'closed'], true)) {
            $ticket->sla_breached = $ticket->sla_due_at->isPast();
        }
    }

    /** Hitung & simpan SLA (setelah tiket dibuat / prioritas dieskalasi / aturan diubah). */
    public function applyPolicy(BugTicket $ticket): void
    {
        $this->fillDueDates($ticket);
        $ticket->saveQuietly();
    }

    /** Hitung ulang target semua tiket yang masih terbuka di proyek (setelah aturan diubah). */
    public function reapplyOpenTickets(Project $project): int
    {
        $count = 0;
        BugTicket::where('project_id', $project->id)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->each(function (BugTicket $t) use (&$count) {
                $this->applyPolicy($t);
                $count++;
            });

        return $count;
    }

    /** Tambah menit ke $start — kalender penuh, atau hanya jam kerja. */
    public function addMinutes(Carbon $start, int $minutes, bool $businessHours, ?int $companyId): Carbon
    {
        if (!$businessHours) {
            return $start->copy()->addMinutes($minutes);
        }

        $holidays = $companyId
            ? Holiday::where('company_id', $companyId)
                ->whereDate('date', '>=', $start->toDateString())
                ->whereDate('date', '<=', $start->copy()->addDays(400)->toDateString())
                ->pluck('date')->map(fn ($d) => Carbon::parse($d)->toDateString())->flip()
            : collect();

        $isWorkDay = fn (Carbon $d) => !$d->isWeekend() && !isset($holidays[$d->toDateString()]);

        // Kelipatan hari ("3 hari") = N HARI KERJA, berakhir di jam yang sama —
        // bukan N×24 jam kerja (yang bisa jadi hampir 3× lebih lama).
        if ($minutes > 0 && $minutes % 1440 === 0) {
            $cursor = $start->copy();
            if (!$isWorkDay($cursor) || $cursor->gte($cursor->copy()->setTime(self::WORK_END, 0))) {
                do { $cursor = $cursor->addDay()->setTime(self::WORK_START, 0); } while (!$isWorkDay($cursor));
            } elseif ($cursor->lt($cursor->copy()->setTime(self::WORK_START, 0))) {
                $cursor = $cursor->setTime(self::WORK_START, 0);
            }
            for ($days = intdiv($minutes, 1440), $guard = 0; $days > 0 && $guard < 1000; $guard++) {
                $cursor = $cursor->addDay();
                if ($isWorkDay($cursor)) {
                    $days--;
                }
            }

            return $cursor;
        }

        $cursor    = $start->copy();
        $remaining = $minutes;
        for ($guard = 0; $remaining > 0 && $guard < 1000; $guard++) {
            $dayStart = $cursor->copy()->setTime(self::WORK_START, 0);
            $dayEnd   = $cursor->copy()->setTime(self::WORK_END, 0);

            if ($cursor->isWeekend() || isset($holidays[$cursor->toDateString()]) || $cursor->gte($dayEnd)) {
                $cursor = $cursor->addDay()->setTime(self::WORK_START, 0);
                continue;
            }
            if ($cursor->lt($dayStart)) {
                $cursor = $dayStart;
            }

            $available = (int) $cursor->diffInMinutes($dayEnd);
            if ($remaining <= $available) {
                return $cursor->addMinutes($remaining);
            }
            $remaining -= $available;
            $cursor = $cursor->addDay()->setTime(self::WORK_START, 0);
        }

        return $cursor;
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

                $project = Project::withoutGlobalScopes()->find($ticket->project_id);
                app(NotificationService::class)->notifyProjectTeam(
                    $project,
                    'sla_breached',
                    'SLA Tiket Terlewat',
                    "Tiket \"{$ticket->title}\" melewati batas SLA penyelesaian.",
                    ['ticket_id' => $ticket->id, 'project_id' => $ticket->project_id],
                );

                if ($ticket->assignee_id) {
                    app(NotificationService::class)->send(
                        $ticket->assignee_id,
                        'sla_breached',
                        'SLA Tiket Anda Terlewat',
                        "Tiket \"{$ticket->title}\" sudah melewati batas SLA.",
                        ['ticket_id' => $ticket->id]
                    );
                }
            });
    }

    /**
     * Peringatan saat waktu SLA terpakai ≥ escalation_at_percent (default 75%).
     * Sebelumnya batas peringatan dihitung relatif ke "sekarang", jadi tidak pernah
     * terpenuhi — sekarang dihitung dari rentang created_at → sla_due_at.
     */
    public function sendWarnings(): void
    {
        BugTicket::whereNotNull('sla_due_at')
            ->where('sla_breached', false)
            ->where('sla_paused', false)
            ->whereNotIn('status', ['resolved', 'closed'])
            ->where('sla_due_at', '>', now())
            ->whereDoesntHave('slaLogs', fn ($q) => $q->where('event_type', 'warning'))
            ->with('slaPolicy')
            ->get()
            ->each(function (BugTicket $ticket) {
                $percent = (int) ($ticket->slaPolicy?->escalation_at_percent ?? self::ESCALATION_DEFAULT);
                if ($ticket->sla_percent_used === null || $ticket->sla_percent_used < $percent) {
                    return;
                }

                SlaLog::create(['ticket_id' => $ticket->id, 'event_type' => 'warning']);

                $project = Project::withoutGlobalScopes()->find($ticket->project_id);
                $message = "Tiket \"{$ticket->title}\" sudah memakai {$ticket->sla_percent_used}% waktu SLA (target selesai " . $ticket->sla_due_at->format('d M Y H:i') . ').';
                app(NotificationService::class)->notifyProjectTeam($project, 'sla_warning', 'Peringatan SLA Tiket', $message, ['ticket_id' => $ticket->id]);
                if ($ticket->assignee_id) {
                    app(NotificationService::class)->send($ticket->assignee_id, 'sla_warning', 'Peringatan SLA Tiket', $message, ['ticket_id' => $ticket->id]);
                }
            });
    }
}
