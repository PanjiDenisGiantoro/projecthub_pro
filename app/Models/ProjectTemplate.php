<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectTemplate extends Model
{
    protected $fillable = ['name', 'description', 'category', 'created_by'];

    public function milestones()
    {
        return $this->hasMany(ProjectTemplateMilestone::class, 'template_id')->orderBy('sort_order');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
