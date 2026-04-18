<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ProjectFile extends Model
{
    protected $fillable = [
        'project_id', 'folder', 'original_name', 'stored_name',
        'mime_type', 'size', 'description', 'uploaded_by',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->stored_name);
    }

    public function humanSize(): string
    {
        $bytes = $this->size;
        if ($bytes >= 1048576) return round($bytes / 1048576, 1) . ' MB';
        if ($bytes >= 1024) return round($bytes / 1024, 1) . ' KB';
        return $bytes . ' B';
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function icon(): string
    {
        $ext = strtolower(pathinfo($this->original_name, PATHINFO_EXTENSION));
        return match(true) {
            in_array($ext, ['pdf']) => '📄',
            in_array($ext, ['doc', 'docx']) => '📝',
            in_array($ext, ['xls', 'xlsx']) => '📊',
            in_array($ext, ['ppt', 'pptx']) => '📑',
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']) => '🖼️',
            in_array($ext, ['zip', 'rar', '7z']) => '📦',
            in_array($ext, ['mp4', 'avi', 'mov']) => '🎬',
            in_array($ext, ['mp3', 'wav']) => '🎵',
            default => '📎',
        };
    }
}
