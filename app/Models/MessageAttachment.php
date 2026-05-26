<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class MessageAttachment extends Model
{
    protected $fillable = ['message_id', 'file_name', 'file_path', 'mime_type', 'file_size'];

    public function message()
    {
        return $this->belongsTo(ProjectMessage::class, 'message_id');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    public function url(): string
    {
        return Storage::url($this->file_path);
    }
}
