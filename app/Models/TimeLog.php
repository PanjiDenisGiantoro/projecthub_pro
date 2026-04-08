<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimeLog extends Model
{
    protected $fillable = [
        'task_id', 'user_id', 'started_at', 'ended_at', 'minutes', 'notes', 'is_running',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_running' => 'boolean',
        ];
    }

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function stop(): void
    {
        $this->ended_at = now();
        $this->minutes = (int) $this->started_at->diffInMinutes($this->ended_at);
        $this->is_running = false;
        $this->save();
    }
}
