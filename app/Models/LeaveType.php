<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id', 'name', 'code', 'description', 'default_quota',
        'is_paid', 'needs_attachment', 'needs_approval', 'has_balance',
        'gender_restriction', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_paid'            => 'boolean',
        'needs_attachment'   => 'boolean',
        'needs_approval'     => 'boolean',
        'has_balance'        => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function isEligible(User $user): bool
    {
        if (!$this->is_active) return false;
        if ($this->gender_restriction !== 'all' && $this->gender_restriction !== ($user->gender ?? 'all')) return false;
        if ($this->tenureBlockedMessage($user) !== null) return false;
        return true;
    }

    /**
     * Cuti Tahunan (UU Ketenagakerjaan Pasal 79) baru berhak diambil setelah 12 bulan
     * masa kerja terus-menerus. Jenis cuti lain (sakit, melahirkan, dll) tidak digerbang
     * masa kerja. Kalau hire_date belum diisi admin, TIDAK diblokir — data tidak ada bukan
     * berarti karyawannya baru, jadi tidak boleh tiba-tiba menutup akses cuti yang sudah
     * berjalan normal sebelum field ini ada.
     */
    public function tenureBlockedMessage(User $user): ?string
    {
        if ($this->code !== 'TAHUNAN' || ! $user->hire_date) {
            return null;
        }

        $months = $user->tenureMonths();
        if ($months >= 12) {
            return null;
        }

        $sisa = 12 - $months;
        return "Cuti Tahunan baru bisa diajukan setelah 12 bulan masa kerja (masa kerja Anda saat ini {$months} bulan, {$sisa} bulan lagi).";
    }
}
