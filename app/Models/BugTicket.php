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
        'project_id', 'reporter_id', 'assignee_id', 'merged_into_id',
        'title', 'description', 'type', 'error_category', 'solution', 'priority', 'status',
        'sla_policy_id', 'sla_due_at', 'sla_breached', 'sla_paused', 'sla_paused_at',
        'escalated_at', 'resolved_at', 'closed_at',
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
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('ticket');
    }

    public function project()       { return $this->belongsTo(Project::class); }
    public function reporter()      { return $this->belongsTo(User::class, 'reporter_id'); }
    public function assignee()      { return $this->belongsTo(User::class, 'assignee_id'); }
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

    public function getSlaPercentUsedAttribute(): ?int
    {
        if (!$this->slaPolicy || !$this->sla_due_at) return null;
        $total = $this->slaPolicy->resolution_minutes;
        $elapsed = $total - ($this->sla_remaining_minutes ?? 0);
        return (int) round(($elapsed / $total) * 100);
    }
}
