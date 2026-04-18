<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplateTask extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'template_milestone_id', 'title', 'description', 'priority',
        'estimated_hours', 'story_points', 'sort_order',
    ];

    public function milestone()
    {
        return $this->belongsTo(ProjectTemplateMilestone::class, 'template_milestone_id');
    }
}
