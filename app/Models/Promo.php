<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class Promo extends Model
{
    protected $fillable = ['name', 'company_id', 'discount_type', 'discount_value', 'start_date', 'end_date', 'is_active', 'notes'];

    protected $casts = [
        'discount_value' => 'float',
        'start_date'     => 'date',
        'end_date'       => 'date',
        'is_active'      => 'boolean',
    ];

    public const TYPES = ['percent' => 'Persen (%)', 'nominal' => 'Nominal (Rp)'];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /** Promo aktif yang periodenya mencakup tanggal tsb, untuk semua perusahaan + (opsional) perusahaan tertentu. */
    public function scopeApplicable($query, ?int $companyId = null, ?CarbonInterface $date = null)
    {
        $day = ($date ?? now())->toDateString();

        return $query->where('is_active', true)
            ->whereDate('start_date', '<=', $day)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $day))
            ->where(fn ($q) => $q->whereNull('company_id')->when($companyId, fn ($q) => $q->orWhere('company_id', $companyId)));
    }

    /**
     * Promo dengan potongan terbesar untuk subtotal tsb (promo tidak digabung).
     * Tanpa $companyId hanya promo untuk semua perusahaan yang dipertimbangkan.
     */
    public static function bestFor(int $subtotal, ?int $companyId = null, ?CarbonInterface $date = null): ?self
    {
        if ($subtotal <= 0) {
            return null;
        }

        return static::applicable($companyId, $date)->get()
            ->sortByDesc(fn (self $p) => $p->discountFor($subtotal))
            ->first(fn (self $p) => $p->discountFor($subtotal) > 0);
    }

    /** Potongan rupiah untuk subtotal (tidak melebihi subtotal). */
    public function discountFor(int $subtotal): int
    {
        $discount = $this->discount_type === 'percent'
            ? (int) round($subtotal * $this->discount_value / 100)
            : (int) $this->discount_value;

        return max(0, min($discount, $subtotal));
    }

    /** "20%" / "Rp 50.000" */
    public function valueLabel(): string
    {
        return $this->discount_type === 'percent'
            ? PpnRate::formatRate($this->discount_value)
            : 'Rp ' . number_format($this->discount_value, 0, ',', '.');
    }

    /** berlaku | akan_datang | berakhir | nonaktif */
    public function status(): string
    {
        $today = now()->startOfDay();

        return match (true) {
            ! $this->is_active                              => 'nonaktif',
            $this->start_date->gt($today)                   => 'akan_datang',
            $this->end_date && $this->end_date->lt($today)  => 'berakhir',
            default                                         => 'berlaku',
        };
    }
}
