<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectMessage extends Model
{
    use SoftDeletes;

    protected $fillable = ['project_id', 'user_id', 'parent_id', 'body', 'edited_at'];

    protected $casts = ['edited_at' => 'datetime'];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function parent()
    {
        return $this->belongsTo(ProjectMessage::class, 'parent_id')->withTrashed();
    }

    public function replies()
    {
        return $this->hasMany(ProjectMessage::class, 'parent_id');
    }

    public function reactions()
    {
        return $this->hasMany(MessageReaction::class, 'message_id');
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class, 'message_id');
    }

    public function attachments()
    {
        return $this->hasMany(MessageAttachment::class, 'message_id');
    }
}
