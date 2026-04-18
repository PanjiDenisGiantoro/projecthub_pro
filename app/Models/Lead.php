<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'contact', 'email', 'phone',
        'company', 'source', 'score', 'value',
        'status', 'notes', 'lost_reason',
        'follow_up_at', 'last_contacted_at',
        'converted_to_client_at', 'assigned_to',
    ];

    protected function casts(): array
    {
        return [
            'converted_to_client_at' => 'datetime',
            'last_contacted_at'      => 'datetime',
            'follow_up_at'           => 'date',
        ];
    }

    public function campaign() { return $this->belongsTo(Campaign::class); }
    public function assignee() { return $this->belongsTo(User::class, 'assigned_to'); }

    public function getScoreLabelAttribute(): string
    {
        return match(true) {
            $this->score >= 8 => 'hot',
            $this->score >= 5 => 'warm',
            $this->score > 0  => 'cold',
            default           => '-',
        };
    }

    public function isFollowUpDue(): bool
    {
        return $this->follow_up_at && $this->follow_up_at->isPast() && !in_array($this->status, ['client', 'lost']);
    }
}
