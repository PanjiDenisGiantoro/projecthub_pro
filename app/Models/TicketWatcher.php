<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketWatcher extends Model
{
    protected $fillable = ['ticket_id', 'user_id'];

    public function ticket()
    {
        return $this->belongsTo(BugTicket::class, 'ticket_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
