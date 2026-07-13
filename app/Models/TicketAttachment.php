<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TicketAttachment extends Model
{
    protected $fillable = ['ticket_id', 'uploaded_by', 'file_name', 'file_path', 'mime_type', 'file_size'];

    public function ticket()
    {
        return $this->belongsTo(BugTicket::class, 'ticket_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
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
