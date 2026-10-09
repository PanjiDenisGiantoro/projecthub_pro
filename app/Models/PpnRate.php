<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;

class PpnRate extends Model
{
    protected $fillable = ['rate', 'start_date', 'end_date', 'notes'];

    protected $casts = [
        'rate'       => 'float',
        'start_date' => 'date',
        'end_date'   => 'date',
    ];

    /** Tarif yang berlaku pada tanggal tertentu (default hari ini), null kalau tidak ada. */
    public static function activeOn(?CarbonInterface $date = null): ?self
    {
        $day = ($date ?? now())->toDateString();

        return static::whereDate('start_date', '<=', $day)
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $day))
            ->orderByDesc('start_date')
            ->first();
    }

    /** Rincian harga: ['subtotal', 'rate', 'ppn', 'total'] — PPN dibulatkan ke rupiah terdekat. */
    public static function breakdown(int $subtotal, ?CarbonInterface $date = null): array
    {
        $rate = static::activeOn($date)?->rate ?? 0;
        $ppn  = (int) round($subtotal * $rate / 100);

        return ['subtotal' => $subtotal, 'rate' => $rate, 'ppn' => $ppn, 'total' => $subtotal + $ppn];
    }

    /** "11%" / "11,5%" */
    public static function formatRate(float $rate): string
    {
        return rtrim(rtrim(number_format($rate, 2, ',', '.'), '0'), ',') . '%';
    }

    public function isCurrent(): bool
    {
        $today = now()->startOfDay();

        return $this->start_date->lte($today) && ($this->end_date === null || $this->end_date->gte($today));
    }
}
