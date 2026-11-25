<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class BugTicket extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'project_id', 'milestone_id', 'reporter_id', 'assignee_id', 'merged_into_id',
        'title', 'description', 'type', 'error_category', 'solution', 'priority', 'status',
        'sla_policy_id', 'sla_due_at', 'sla_breached', 'sla_paused', 'sla_paused_at',
        'escalated_at', 'resolved_at', 'closed_at',
        'google_event_id', 'google_meet_link', 'meeting_starts_at',
    ];

    protected function casts(): array
    {
        return [
            'sla_due_at'    => 'datetime',
            'sla_paused_at' => 'datetime',
            'escalated_at'  => 'datetime',
            'resolved_at'   => 'datetime',
            'closed_at'     => 'datetime',
            'sla_breached'  => 'boolean',
            'sla_paused'    => 'boolean',
            'meeting_starts_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Prioritas berubah lewat jalur mana pun (web, API, bulk action, approval
        // eskalasi) → target SLA dihitung ulang dari waktu tiket dibuat.
        static::updating(function (BugTicket $ticket) {
            if ($ticket->isDirty('priority') && !$ticket->isDirty('sla_due_at')) {
                app(\App\Services\SlaService::class)->fillDueDates($ticket);
            }
        });
    }

    /** on_track | at_risk | breached | met | missed | paused | none — untuk badge SLA. */
    public function slaState(): string
    {
        if (!$this->sla_due_at) return 'none';
        if (in_array($this->status, ['resolved', 'closed'], true)) {
            $doneAt = $this->resolved_at ?? $this->closed_at ?? $this->updated_at;
            return $doneAt && $doneAt->gt($this->sla_due_at) ? 'missed' : 'met';
        }
        if ($this->sla_paused) return 'paused';
        if ($this->sla_breached || $this->sla_due_at->isPast()) return 'breached';
        $pct       = $this->sla_percent_used;
        $threshold = $this->slaPolicy?->escalation_at_percent ?? \App\Services\SlaService::ESCALATION_DEFAULT;
        return $pct !== null && $pct >= $threshold ? 'at_risk' : 'on_track';
    }

    public const SLA_STATES = [
        'on_track' => ['label' => 'Aman',      'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'],
        'at_risk'  => ['label' => 'Berisiko',  'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400'],
        'breached' => ['label' => 'Terlewat',  'badge' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300'],
        'met'      => ['label' => 'Tercapai',  'badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'],
        'missed'   => ['label' => 'Telat selesai', 'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'],
        'paused'   => ['label' => 'Dijeda',    'badge' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'],
        'none'     => ['label' => '—',         'badge' => 'text-gray-400'],
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('ticket');
    }

    public function project()       { return $this->belongsTo(Project::class); }
    public function milestone()     { return $this->belongsTo(Milestone::class); }
    public function reporter()      { return $this->belongsTo(User::class, 'reporter_id'); }
    public function assignee()      { return $this->belongsTo(User::class, 'assignee_id'); }
    public function assignees()     { return $this->belongsToMany(User::class, 'ticket_assignees', 'ticket_id', 'user_id')->withTimestamps(); }
    public function slaPolicy()     { return $this->belongsTo(SlaPolicy::class); }
    public function mergedInto()    { return $this->belongsTo(BugTicket::class, 'merged_into_id'); }
    public function mergedTickets() { return $this->hasMany(BugTicket::class, 'merged_into_id'); }

    public function slaLogs()    { return $this->hasMany(SlaLog::class, 'ticket_id'); }
    public function slaPauses()  { return $this->hasMany(SlaPause::class, 'ticket_id'); }
    public function comments()   { return $this->hasMany(TicketComment::class, 'ticket_id'); }
    public function attachments() { return $this->hasMany(TicketAttachment::class, 'ticket_id'); }
    public function histories()  { return $this->hasMany(TicketHistory::class, 'ticket_id'); }
    public function tasks()      { return $this->hasMany(Task::class, 'ticket_id'); }
    public function watchers()   { return $this->hasMany(TicketWatcher::class, 'ticket_id'); }
    public function checklists() { return $this->hasMany(TicketChecklist::class, 'ticket_id')->orderBy('sort_order'); }

    public function outgoingLinks() { return $this->hasMany(TicketLink::class, 'source_ticket_id'); }
    public function incomingLinks() { return $this->hasMany(TicketLink::class, 'target_ticket_id'); }

    public function approvals()
    {
        return $this->morphMany(\App\Models\Approval::class, 'approvable');
    }

    public function pendingApprovals()
    {
        return $this->morphMany(\App\Models\Approval::class, 'approvable')->where('status', 'pending');
    }

    public function getSlaRemainingMinutesAttribute(): ?int
    {
        if (!$this->sla_due_at || $this->sla_paused) return null;
        return max(0, (int) now()->diffInMinutes($this->sla_due_at, false));
    }

    /** % waktu SLA terpakai, dari rentang created_at → sla_due_at (tidak bergantung policy). */
    public function getSlaPercentUsedAttribute(): ?int
    {
        if (!$this->sla_due_at || !$this->created_at) return null;
        $total = $this->created_at->diffInMinutes($this->sla_due_at);
        if ($total <= 0) return 100;
        $until = $this->sla_paused && $this->sla_paused_at ? $this->sla_paused_at : now();
        return max(0, (int) round($this->created_at->diffInMinutes($until) / $total * 100));
    }
}
