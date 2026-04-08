<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Task extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'project_id', 'milestone_id', 'ticket_id', 'title', 'description',
        'assigned_to', 'created_by', 'status', 'priority', 'due_date', 'estimated_hours',
    ];

    protected function casts(): array
    {
        return ['due_date' => 'date'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('task');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function ticket()
    {
        return $this->belongsTo(BugTicket::class, 'ticket_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timeLogs()
    {
        return $this->hasMany(TimeLog::class);
    }

    public function totalMinutes(): int
    {
        return (int) $this->timeLogs()->sum('minutes');
    }
}
