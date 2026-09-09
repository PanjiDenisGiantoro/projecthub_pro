<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceApiSyncLog extends Model
{
    protected $fillable = [
        'attendance_api_source_id', 'triggered_by', 'mode', 'status',
        'date_from', 'date_to', 'request_payload', 'response_payload',
        'rows_fetched', 'rows_created', 'rows_updated', 'rows_skipped', 'rows_failed',
        'error_message',
    ];

    protected $casts = [
        'date_from' => 'date',
        'date_to'   => 'date',
    ];

    public function source(): BelongsTo
    {
        return $this->belongsTo(AttendanceApiSource::class, 'attendance_api_source_id');
    }

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }
}
