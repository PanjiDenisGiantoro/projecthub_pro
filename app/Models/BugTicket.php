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
        'project_id', 'reporter_id', 'assignee_id', 'title', 'description',
        'type', 'priority', 'status', 'sla_policy_id', 'sla_due_at',
        'sla_breached', 'escalated_at', 'resolved_at', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'sla_due_at' => 'datetime',
            'escalated_at' => 'datetime',
            'resolved_at' => 'datetime',
            'closed_at' => 'datetime',
            'sla_breached' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('ticket');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function slaPolicy()
    {
        return $this->belongsTo(SlaPolicy::class);
    }

    public function slaLogs()
    {
        return $this->hasMany(SlaLog::class, 'ticket_id');
    }

    public function comments()
    {
        return $this->hasMany(TicketComment::class, 'ticket_id');
    }

    public function histories()
    {
        return $this->hasMany(TicketHistory::class, 'ticket_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'ticket_id');
    }

    public function getSlaRemainingMinutesAttribute(): ?int
    {
        if (!$this->sla_due_at) {
            return null;
        }
        return max(0, (int) now()->diffInMinutes($this->sla_due_at, false));
    }

    public function getSlaPercentUsedAttribute(): ?int
    {
        if (!$this->slaPolicy || !$this->sla_due_at) {
            return null;
        }
        $total = $this->slaPolicy->resolution_minutes;
        $elapsed = $total - $this->sla_remaining_minutes;
        return (int) round(($elapsed / $total) * 100);
    }
}
