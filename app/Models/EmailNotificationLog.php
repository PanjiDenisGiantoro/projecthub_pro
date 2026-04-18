<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailNotificationLog extends Model
{
    protected $fillable = [
        'user_id', 'type', 'notifiable_type', 'notifiable_id',
        'email', 'subject', 'status', 'error',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function notifiable()
    {
        return $this->morphTo();
    }
}
