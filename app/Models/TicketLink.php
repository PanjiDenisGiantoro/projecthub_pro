<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketLink extends Model
{
    protected $fillable = ['source_ticket_id', 'target_ticket_id', 'link_type', 'created_by'];

    public function sourceTicket()
    {
        return $this->belongsTo(BugTicket::class, 'source_ticket_id');
    }

    public function targetTicket()
    {
        return $this->belongsTo(BugTicket::class, 'target_ticket_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
