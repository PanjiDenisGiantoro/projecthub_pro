<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Pph21Setting extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id',
        'method',
        'payment_scheme',
        'jkk_rate',
        'potong_alpha',
        'potongan_alpha_metode',
        'potongan_alpha_nominal',
        'tax_tunjangan_jabatan',
        'tax_tunjangan_transport',
        'tax_tunjangan_makan',
        'overtime_needs_approval',
        'reimbursement_needs_approval',
    ];

    protected $casts = [
        'jkk_rate'                     => 'float',
        'potong_alpha'                 => 'boolean',
        'potongan_alpha_nominal'       => 'float',
        'tax_tunjangan_jabatan'        => 'boolean',
        'tax_tunjangan_transport'      => 'boolean',
        'tax_tunjangan_makan'          => 'boolean',
        'overtime_needs_approval'      => 'boolean',
        'reimbursement_needs_approval' => 'boolean',
    ];

    /** Tarif JKK (Jaminan Kecelakaan Kerja) employer per kelas risiko, sesuai PP 82/2019. */
    public const JKK_RATES = [
        'I'   => 0.0024,
        'II'  => 0.0054,
        'III' => 0.0089,
        'IV'  => 0.0127,
        'V'   => 0.0174,
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'method', 'payment_scheme', 'jkk_rate',
                'potong_alpha', 'potongan_alpha_metode', 'potongan_alpha_nominal',
                'tax_tunjangan_jabatan', 'tax_tunjangan_transport', 'tax_tunjangan_makan',
                'overtime_needs_approval', 'reimbursement_needs_approval',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('payroll_setting');
    }

    /** Get or create settings for a company */
    public static function forCompany(int $companyId): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            ['method' => 'progresif']
        );
    }

    /**
     * Total komponen gaji yang dihitung sebagai bruto pajak, sesuai komponen
     * yang diaktifkan di pengaturan ini. Gaji pokok selalu ikut dihitung.
     */
    public function brutoPajak(
        float $gajiPokok,
        float $tunjanganJabatan,
        float $tunjanganTransport,
        float $tunjanganMakan
    ): float {
        return $gajiPokok
            + ($this->tax_tunjangan_jabatan ? $tunjanganJabatan : 0)
            + ($this->tax_tunjangan_transport ? $tunjanganTransport : 0)
            + ($this->tax_tunjangan_makan ? $tunjanganMakan : 0);
    }
}
