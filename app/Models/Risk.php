<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Risk extends Model
{
    protected $fillable = [
        'project_id', 'title', 'description', 'category',
        'probability', 'impact', 'status', 'mitigation_plan', 'owner', 'created_by',
    ];

    public function score(): int
    {
        return $this->probability * $this->impact;
    }

    public function level(): string
    {
        $score = $this->score();
        if ($score >= 15) return 'critical';
        if ($score >= 8)  return 'high';
        if ($score >= 4)  return 'medium';
        return 'low';
    }

    public function levelColor(): string
    {
        return match($this->level()) {
            'critical' => 'red',
            'high'     => 'orange',
            'medium'   => 'yellow',
            default    => 'green',
        };
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
