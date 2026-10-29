<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectStatus extends Model
{
    protected $fillable = ['project_id', 'name', 'color', 'sort_order'];

    /**
     * Default task statuses available for any project.
     */
    public static array $defaultStatuses = [
        ['name' => 'In Progress', 'color' => 'blue'],
        ['name' => 'On Hold',     'color' => 'amber'],
        ['name' => 'Review',      'color' => 'purple'],
        ['name' => 'QA',          'color' => 'indigo'],
        ['name' => 'Completed',   'color' => 'emerald'],
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}

