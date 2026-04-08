<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Lead extends Model
{
    protected $fillable = [
        'campaign_id', 'name', 'contact', 'company', 'status',
        'notes', 'converted_to_client_at', 'assigned_to',
    ];

    protected function casts(): array
    {
        return ['converted_to_client_at' => 'datetime'];
    }

    public function campaign()
    {
        return $this->belongsTo(Campaign::class);
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }
}
