<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RecurringTaskDefinition extends Model
{
    protected $fillable = [
        'project_id', 'milestone_id', 'title', 'description', 'assigned_to',
        'frequency', 'day_of_week', 'day_of_month', 'priority',
        'estimated_hours', 'due_offset_days', 'is_active', 'last_generated_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_generated_at' => 'date',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function milestone()
    {
        return $this->belongsTo(Milestone::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class, 'recurring_definition_id');
    }
}
