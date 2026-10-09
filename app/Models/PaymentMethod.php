<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    protected $fillable = ['code', 'name', 'group', 'fee_flat', 'fee_percent', 'is_active', 'sort_order'];

    protected $casts = [
        'fee_flat'    => 'integer',
        'fee_percent' => 'float',
        'is_active'   => 'boolean',
        'sort_order'  => 'integer',
    ];

    public const GROUPS = ['Virtual Account', 'QRIS', 'E-Wallet', 'Kartu Kredit'];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    /** Biaya layanan untuk nominal (harga + PPN): flat + persen, dibulatkan ke atas. */
    public function feeFor(int $base): int
    {
        return $this->fee_flat + (int) ceil($base * $this->fee_percent / 100);
    }

    /** "Rp 4.500", "2%", "2,9% + Rp 2.000", "Gratis" */
    public function feeLabel(): string
    {
        $parts = [];
        if ($this->fee_percent > 0) {
            $parts[] = PpnRate::formatRate($this->fee_percent);
        }
        if ($this->fee_flat > 0) {
            $parts[] = 'Rp ' . number_format($this->fee_flat, 0, ',', '.');
        }

        return $parts ? implode(' + ', $parts) : 'Gratis';
    }
}
