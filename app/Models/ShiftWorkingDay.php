<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShiftWorkingDay extends Model
{
    protected $fillable = ['shift_id', 'day_of_week', 'start_time', 'end_time'];

    public function shift(): BelongsTo
    {
        return $this->belongsTo(Shift::class);
    }
}
