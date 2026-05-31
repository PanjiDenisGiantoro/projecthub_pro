<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Payroll extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'year', 'month',
        'gaji_pokok', 'tunjangan_transport', 'tunjangan_makan', 'tunjangan_jabatan',
        'tunjangan_lainnya', 'lembur', 'reimburse', 'penghasilan_bruto',
        'potongan_bpjs_kes', 'potongan_bpjs_tk', 'potongan_pph21', 'potongan_lainnya',
        'total_potongan', 'gaji_bersih', 'status', 'paid_at',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function reimbursements(): HasMany
    {
        return $this->hasMany(Reimbursement::class);
    }
}
