<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $fillable = [
        'project_id', 'name', 'goal', 'start_date', 'end_date', 'status', 'created_by',
    ];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

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
}
