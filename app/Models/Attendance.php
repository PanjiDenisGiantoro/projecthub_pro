<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'date', 'check_in', 'check_out',
        'status', 'location_in', 'location_out', 'photo_in', 'notes',
    ];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function workMinutes(): int
    {
        if (!$this->check_in || !$this->check_out) return 0;
        return Carbon::parse($this->check_in)->diffInMinutes(Carbon::parse($this->check_out));
    }
}
