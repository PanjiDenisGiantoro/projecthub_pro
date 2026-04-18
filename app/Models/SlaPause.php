<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPause extends Model
{
    protected $fillable = ['ticket_id', 'paused_by', 'reason', 'paused_at', 'resumed_at'];

    protected function casts(): array
    {
        return [
            'paused_at' => 'datetime',
            'resumed_at' => 'datetime',
        ];
    }

    public function ticket()
    {
        return $this->belongsTo(BugTicket::class, 'ticket_id');
    }

    public function pausedBy()
    {
        return $this->belongsTo(User::class, 'paused_by');
    }

    public function getDurationMinutesAttribute(): ?int
    {
        if (!$this->resumed_at) return null;
        return (int) $this->paused_at->diffInMinutes($this->resumed_at);
    }
}
