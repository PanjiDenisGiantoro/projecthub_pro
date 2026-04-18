<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = ['project_id', 'title', 'description', 'start_date', 'due_date', 'status', 'assigned_to'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'due_date' => 'date'];
    }

    public function taskProgressPercent(): int
    {
        $total = $this->tasks()->count();
        if ($total === 0) return 0;
        return (int) round($this->tasks()->where('status', 'done')->count() / $total * 100);
    }

    public function daysRemaining(): ?int
    {
        if (!$this->due_date || $this->status === 'completed') return null;
        return (int) now()->startOfDay()->diffInDays($this->due_date->startOfDay(), false);
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'completed';
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
