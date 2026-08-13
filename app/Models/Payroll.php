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
        'tunjangan_lainnya', 'bonus', 'lembur', 'reimburse',
        'hari_kerja', 'hari_hadir', 'hari_cuti', 'hari_alpha', 'potongan_alpha',
        'potongan_kasbon',
        'penghasilan_bruto',
        'potongan_bpjs_kes', 'potongan_bpjs_tk', 'potongan_pph21', 'tunjangan_pph21',
        'pph21_method', 'pph21_scheme', 'potongan_lainnya',
        'total_potongan',
        'tanggungan_bpjs_kes', 'tanggungan_bpjs_tk', 'tanggungan_pph21', 'total_tanggungan_perusahaan',
        'gaji_bersih', 'status', 'paid_at',
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
