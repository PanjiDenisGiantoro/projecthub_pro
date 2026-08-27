<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectFolder extends Model
{
    protected $fillable = ['project_id', 'path', 'created_by'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
