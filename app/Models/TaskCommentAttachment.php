<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskCommentAttachment extends Model
{
    protected $fillable = ['task_comment_id', 'file_name', 'file_path', 'mime_type', 'file_size'];

    public function comment()
    {
        return $this->belongsTo(TaskComment::class, 'task_comment_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function url(): string
    {
        return Storage::url($this->file_path);
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }
}
