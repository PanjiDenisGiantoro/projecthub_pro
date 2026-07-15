<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmployeeSalary extends Model
{
    protected $fillable = [
        'user_id', 'gaji_pokok', 'tunjangan_transport', 'tunjangan_makan',
        'tunjangan_jabatan', 'npwp', 'status_pajak',
        'bpjs_kesehatan', 'bpjs_ketenagakerjaan', 'effective_date',
    ];

    protected $casts = [
        'effective_date'       => 'date',
        'bpjs_kesehatan'       => 'boolean',
        'bpjs_ketenagakerjaan' => 'boolean',
        'gaji_pokok'           => 'float',
        'tunjangan_transport'  => 'float',
        'tunjangan_makan'      => 'float',
        'tunjangan_jabatan'    => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
