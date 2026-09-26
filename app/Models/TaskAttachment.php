<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TaskAttachment extends Model
{
    protected $fillable = ['task_id', 'type', 'file_name', 'file_path', 'url', 'mime_type', 'file_size', 'created_by'];

    public function task()
    {
        return $this->belongsTo(Task::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function isLink(): bool
    {
        return $this->type === 'link';
    }

    public function publicUrl(): string
    {
        if ($this->type === 'link') {
            return $this->url ?? '#';
        }
        return $this->file_path ? Storage::url($this->file_path) : ($this->url ?? '#');
    }

    public function humanSize(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024)    return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function extension(): string
    {
        if ($this->type === 'link') return 'link';
        return strtolower(pathinfo($this->file_name ?? '', PATHINFO_EXTENSION)) ?: 'file';
    }
}
