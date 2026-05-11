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
        'project_id', 'milestone_id', 'ticket_id', 'sprint_id', 'title', 'description',
        'completion_notes', 'assigned_to', 'created_by', 'status', 'priority', 'start_date', 'due_date',
        'estimated_hours', 'story_points', 'sort_order', 'recurring_definition_id',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'due_date' => 'date'];
    }

    public function daysRemaining(): ?int
    {
        if (!$this->due_date || $this->status === 'done') return null;
        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    public function timeProgressPercent(): int
    {
        if (!$this->estimated_hours || $this->estimated_hours <= 0) return 0;
        return min(100, (int) round(($this->totalMinutes() / 60) / $this->estimated_hours * 100));
    }

    public function durationDays(): ?int
    {
        if (!$this->start_date || !$this->due_date) return null;
        return (int) $this->start_date->diffInDays($this->due_date) + 1;
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

    public function sprint()
    {
        return $this->belongsTo(Sprint::class);
    }

    public function recurringDefinition()
    {
        return $this->belongsTo(RecurringTaskDefinition::class, 'recurring_definition_id');
    }

    public function dependencies()
    {
        return $this->hasMany(TaskDependency::class, 'task_id');
    }

    public function blockedBy()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id');
    }

    public function blocks()
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id');
    }

    public function isBlocked(): bool
    {
        return $this->blockedBy()->whereNotIn('status', ['done'])->exists();
    }
}
