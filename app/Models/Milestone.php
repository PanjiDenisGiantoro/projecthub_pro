<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = [
        'project_id', 'code', 'title', 'description', 'start_date', 'due_date', 'status', 'assigned_to',
        'priority', 'release_target',
        'google_event_id', 'google_meet_link', 'meeting_starts_at', 'google_meeting_organizer_id',
        'client_approved_at', 'client_approved_via_token_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'due_date' => 'date',
            'meeting_starts_at' => 'datetime',
            'client_approved_at' => 'datetime',
        ];
    }

    // ─── Boot: Auto-generate code ────────────────────────────────────────
    protected static function booted(): void
    {
        static::creating(function (self $ms) {
            if (empty($ms->code)) {
                $count = self::where('project_id', $ms->project_id)->count() + 1;
                $ms->code = 'MS-' . str_pad($count, 2, '0', STR_PAD_LEFT);
            }
        });
    }

    public function setStatusAttribute($value): void
    {
        $map = [
            'planning'    => 'pending',
            'pending'     => 'pending',
            'in_progress' => 'in_progress',
            'active'      => 'in_progress',
            'at_risk'     => 'in_progress',
            'completed'   => 'completed',
        ];
        $this->attributes['status'] = $map[$value] ?? ($value ?: 'pending');
    }

    // ─── Option Lists ────────────────────────────────────────────────────
    public static function statusOptions(): array
    {
        return [
            'planning'    => ['label' => 'Planning Phase', 'dot' => 'bg-slate-400',  'pill' => 'bg-slate-100 text-slate-700'],
            'in_progress' => ['label' => 'In Progress',    'dot' => 'bg-blue-500',   'pill' => 'bg-blue-100 text-blue-700'],
            'completed'   => ['label' => 'Completed',      'dot' => 'bg-emerald-500','pill' => 'bg-emerald-100 text-emerald-700'],
            'at_risk'     => ['label' => 'At Risk / Issue', 'dot' => 'bg-red-500',   'pill' => 'bg-red-100 text-red-700'],
            'pending'     => ['label' => 'Planning Phase',  'dot' => 'bg-slate-400',  'pill' => 'bg-slate-100 text-slate-700'],
        ];
    }

    public static function priorityOptions(): array
    {
        return [
            'low'    => ['label' => 'Low',           'color' => '#10b981', 'pill' => 'bg-emerald-100 text-emerald-700'],
            'medium' => ['label' => 'Medium',        'color' => '#f59e0b', 'pill' => 'bg-amber-100 text-amber-700'],
            'high'   => ['label' => 'High Priority', 'color' => '#f97316', 'pill' => 'bg-orange-100 text-orange-700'],
            'urgent' => ['label' => 'Urgent',        'color' => '#ef4444', 'pill' => 'bg-red-100 text-red-700'],
        ];
    }

    public static function releaseTargetOptions(): array
    {
        return [
            'Phase 1 Release (Q1 2026)',
            'Phase 2 Release (Q2 2026)',
            'Phase 3 Release (Q3 2026)',
            'Phase 4 Release (Q4 2026)',
            'Phase 1 Release (Q1 2027)',
            'Beta Release',
            'GA Release',
            'Hotfix / Patch',
        ];
    }

    // ─── Status & Priority Helpers ───────────────────────────────────────
    public function statusLabel(): string
    {
        return self::statusOptions()[$this->status ?? 'pending']['label'] ?? ucwords(str_replace('_', ' ', $this->status ?? 'pending'));
    }

    public function statusPillClass(): string
    {
        return self::statusOptions()[$this->status ?? 'pending']['pill'] ?? 'bg-gray-100 text-gray-600';
    }

    public function statusDotClass(): string
    {
        return self::statusOptions()[$this->status ?? 'pending']['dot'] ?? 'bg-gray-400';
    }

    public function priorityLabel(): string
    {
        return self::priorityOptions()[$this->priority ?? 'low']['label'] ?? 'Low';
    }

    public function priorityPillClass(): string
    {
        return self::priorityOptions()[$this->priority ?? 'low']['pill'] ?? 'bg-emerald-100 text-emerald-700';
    }

    // ─── Relationships ───────────────────────────────────────────────────
    public function isClientApproved(): bool
    {
        return $this->client_approved_at !== null;
    }

    public function clientApprovedVia()
    {
        return $this->belongsTo(ClientPortalToken::class, 'client_approved_via_token_id');
    }

    public function meetingOrganizer()
    {
        return $this->belongsTo(User::class, 'google_meeting_organizer_id');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function sprints()
    {
        return $this->hasMany(Sprint::class);
    }

    public function standaloneTasks()
    {
        return $this->hasMany(Task::class)->whereNull('sprint_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    // ─── Progress & Stats ────────────────────────────────────────────────
    public function taskProgressPercent(): int
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        if ($total === 0) return 0;
        return (int) round($tasks->filter(fn($t) => method_exists($t, 'isDone') ? $t->isDone() : ($t->status === 'done'))->count() / $total * 100);
    }

    public function rollupProgressPercent(): int
    {
        return $this->taskProgressPercent();
    }

    public function getRollupProgressPercentAttribute(): int
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

    public function statusStyle(): array
    {
        return self::statusOptions()[$this->status ?? 'pending'] ?? ['label' => 'Planning Phase', 'dot' => 'bg-slate-400', 'pill' => 'bg-slate-100 text-slate-700'];
    }

    public function priorityStyle(): array
    {
        return self::priorityOptions()[$this->priority ?? 'low'] ?? ['label' => 'Low', 'color' => '#10b981', 'pill' => 'bg-emerald-100 text-emerald-700'];
    }

    public function rollupStats(): array
    {
        $tasks = $this->tasks;
        $total = $tasks->count();
        $done = $tasks->filter(fn($t) => method_exists($t, 'isDone') ? $t->isDone() : ($t->status === 'done'))->count();
        $pct = $total > 0 ? (int) round($done / $total * 100) : 0;

        $sprintCount = $this->sprints()->count();
        $directTaskCount = $this->standaloneTasks()->count();

        return compact('total', 'done', 'pct', 'sprintCount', 'directTaskCount');
    }

    public function daysRemaining(): ?int
    {
        if (!$this->due_date || $this->status === 'completed') return null;
        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }

    public function workingDaysRemaining(): ?int
    {
        if (!$this->due_date || $this->status === 'completed') return null;
        $start = now()->startOfDay();
        $end = $this->due_date->copy()->startOfDay();
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
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }
}
