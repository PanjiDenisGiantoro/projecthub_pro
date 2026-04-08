<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SlaLog extends Model
{
    protected $fillable = ['ticket_id', 'event_type', 'actor_id', 'notes'];

    public function ticket()
    {
        return $this->belongsTo(BugTicket::class, 'ticket_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
