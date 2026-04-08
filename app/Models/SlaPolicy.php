<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaPolicy extends Model
{
    protected $fillable = [
        'project_id', 'priority', 'response_minutes', 'resolution_minutes',
        'escalation_at_percent', 'business_hours_only', 'created_by',
    ];

    protected function casts(): array
    {
        return ['business_hours_only' => 'boolean'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tickets()
    {
        return $this->hasMany(BugTicket::class);
    }
}
