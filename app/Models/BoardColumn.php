<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardColumn extends Model
{
    protected $fillable = ['project_id', 'name', 'slug', 'color', 'sort_order', 'is_done'];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
        ];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }
}
