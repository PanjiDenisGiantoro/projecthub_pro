<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketChecklist extends Model
{
    protected $fillable = ['ticket_id', 'body', 'is_done', 'sort_order', 'created_by'];

    protected function casts(): array
    {
        return ['is_done' => 'boolean'];
    }

    public function ticket()
    {
        return $this->belongsTo(BugTicket::class, 'ticket_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
