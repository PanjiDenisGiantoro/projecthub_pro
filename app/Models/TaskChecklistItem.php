<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskChecklistItem extends Model
{
    protected $fillable = ['checklist_id', 'title', 'is_done', 'sort_order', 'completed_at', 'completed_by'];

    protected function casts(): array
    {
        return [
            'is_done'      => 'boolean',
            'completed_at' => 'datetime',
        ];
    }

    public function checklist()
    {
        return $this->belongsTo(TaskChecklist::class, 'checklist_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }
}
