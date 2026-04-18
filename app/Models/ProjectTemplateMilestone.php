<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplateMilestone extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'template_id', 'title', 'description', 'offset_days', 'duration_days', 'sort_order',
    ];

    public function template()
    {
        return $this->belongsTo(ProjectTemplate::class);
    }

    public function tasks()
    {
        return $this->hasMany(ProjectTemplateTask::class, 'template_milestone_id')->orderBy('sort_order');
    }
}
