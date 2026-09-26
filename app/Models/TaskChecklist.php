<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskChecklist extends Model
{
    protected $fillable = ['task_id', 'title', 'sort_order'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function items()
    {
        return $this->hasMany(TaskChecklistItem::class, 'checklist_id')->orderBy('sort_order');
    }

    public function doneCount(): int
    {
        return $this->items()->where('is_done', true)->count();
    }

    public function totalCount(): int
    {
        return $this->items()->count();
    }
}
