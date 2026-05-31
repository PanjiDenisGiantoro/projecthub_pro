<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Overtime extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'date', 'day_type', 'start_time', 'end_time',
        'total_hours', 'upah_sejam', 'total_amount', 'breakdown', 'description',
        'status', 'approved_by', 'approved_at',
    ];

    protected $casts = [
        'date'        => 'date',
        'approved_at' => 'datetime',
        'breakdown'   => 'array',
        'total_hours' => 'float',
        'upah_sejam'  => 'float',
        'total_amount'=> 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
