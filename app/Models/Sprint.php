<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sprint extends Model
{
    protected $fillable = [
        'project_id', 'name', 'goal', 'start_date', 'end_date', 'status', 'created_by',
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

    public function project()
    {
        return $this->belongsTo(Project::class);
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
