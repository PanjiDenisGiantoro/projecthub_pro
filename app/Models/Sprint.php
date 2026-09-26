<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $fillable = [
        'project_id', 'code', 'milestone_id', 'name', 'goal', 'start_date', 'end_date', 'status',
        'assigned_to', 'priority', 'created_by',
        'google_event_id', 'google_meet_link', 'meeting_starts_at', 'google_meeting_organizer_id',
        'google_meeting_is_recurring',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'meeting_starts_at' => 'datetime',
            'google_meeting_is_recurring' => 'boolean',
        ];
    }

    // ─── Boot: Auto-generate code ────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $sprint) {
            if (empty($sprint->code)) {
                $count = self::where('project_id', $sprint->project_id)->count() + 1;
                $sprint->code = 'SP-' . str_pad($count, 2, '0', STR_PAD_LEFT);
            }
        });
    }

    public function setStatusAttribute($value): void
    {
        $map = [
            'planning'    => 'planned',
            'planned'     => 'planned',
            'not_started' => 'planned',
            'active'      => 'active',
            'in_progress' => 'active',
            'at_risk'     => 'active',
            'completed'   => 'completed',
        ];
        $this->attributes['status'] = $map[$value] ?? ($value ?: 'planned');
    }

    // ─── Option Lists ────────────────────────────────────────────────────
    public static function statusOptions(): array
    {
        return [
            'not_started' => ['label' => 'Not Started',    'dot' => 'bg-gray-400',    'pill' => 'bg-gray-100 text-gray-600'],
            'planning'    => ['label' => 'Planning',       'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-700'],
            'active'      => ['label' => 'In Progress',    'dot' => 'bg-blue-500',    'pill' => 'bg-blue-100 text-blue-700'],
            'in_progress' => ['label' => 'In Progress',    'dot' => 'bg-blue-500',    'pill' => 'bg-blue-100 text-blue-700'],
            'completed'   => ['label' => 'Completed',      'dot' => 'bg-emerald-500', 'pill' => 'bg-emerald-100 text-emerald-700'],
            'at_risk'     => ['label' => 'At Risk / Issue', 'dot' => 'bg-red-500',    'pill' => 'bg-red-100 text-red-700'],
            'planned'     => ['label' => 'Planning',       'dot' => 'bg-slate-400',   'pill' => 'bg-slate-100 text-slate-700'],
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'normal' => ['label' => 'Normal',        'pill' => 'bg-gray-100 text-gray-600'],
            'low'    => ['label' => 'Low',            'pill' => 'bg-emerald-100 text-emerald-700'],
            'high'   => ['label' => 'High Priority',  'pill' => 'bg-orange-100 text-orange-700'],
            'urgent' => ['label' => 'Urgent',         'pill' => 'bg-red-100 text-red-700'],
        ];
    }

    // ─── Status & Priority Helpers ───────────────────────────────────────
    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status ?? 'planned']['label'] ?? ucfirst($this->status ?? 'Planning');
    }

    public function statusPillClass(): string
    {
        return self::statusOptions()[$this->status ?? 'planned']['pill'] ?? 'bg-gray-100 text-gray-600';
    }

    public function statusDotClass(): string
    {
        return self::statusOptions()[$this->status ?? 'planned']['dot'] ?? 'bg-gray-400';
    }

    public function priorityLabel(): string
    {
        return self::priorityOptions()[$this->priority ?? 'normal']['label'] ?? 'Normal';
    }

    public function priorityPillClass(): string
    {
        return self::priorityOptions()[$this->priority ?? 'normal']['pill'] ?? 'bg-gray-100 text-gray-600';
    }

    public function statusStyle(): array
    {
        return self::statusOptions()[$this->status ?? 'planned'] ?? ['label' => 'Planning', 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-700'];
    }

    public function priorityStyle(): array
    {
        return self::priorityOptions()[$this->priority ?? 'normal'] ?? ['label' => 'Normal', 'pill' => 'bg-gray-100 text-gray-600'];
    }

    // ─── Relationships ───────────────────────────────────────────────────
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function meetingOrganizer()
    {
        return $this->belongsTo(User::class, 'google_meeting_organizer_id');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function lead()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ─── Progress & Stats ────────────────────────────────────────────────
    public function velocity(): float
    {
        return (float) $this->tasks()->where('status', 'done')->sum('story_points');
    }

    public function totalPoints(): int
    {
        return (int) $this->tasks()->sum('story_points');
    }

    public function completedPoints(): int
    {
        return (int) $this->tasks()->where('status', 'done')->sum('story_points');
    }

    public function taskStats(): array
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        $done = $tasks->filter(fn($t) => method_exists($t, 'isDone') ? $t->isDone() : ($t->status === 'done'))->count();
        $pct = $total > 0 ? (int) round($done / $total * 100) : 0;
        return compact('total', 'done', 'pct');
    }

    public function taskProgressPercent(): int
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        if ($total === 0) return 0;
        return (int) round($tasks->filter(fn($t) => method_exists($t, 'isDone') ? $t->isDone() : ($t->status === 'done'))->count() / $total * 100);
    }

    public function getTaskProgressPercentAttribute(): int
    {
        return $this->taskProgressPercent();
    }

    public function progressPercent(): int
    {
        return $this->taskProgressPercent();
    }

    public function getProgressPercentAttribute(): int
    {
        return $this->taskProgressPercent();
    }

    public function rollupProgressPercent(): int
    {
        return $this->taskProgressPercent();
    }

    public function daysRemaining(): ?int
    {
        if (!$this->end_date || $this->status === 'completed') return null;
        return (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false);
    }

    public function workingDaysRemaining(): ?int
    {
        if (!$this->end_date || $this->status === 'completed') return null;
        $start = now()->startOfDay();
        $end = $this->end_date->copy()->startOfDay();
        if ($end->lte($start)) return 0;
        $days = 0;
        $current = $start->copy();
        while ($current->lt($end)) {
            if (!$current->isWeekend()) $days++;
            $current->addDay();
        }
        return $days;
    }

    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && $this->status !== 'completed';
    }
}
