<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxTerRate extends Model
{
    protected $fillable = [
        'category', 'income_from', 'income_to', 'rate', 'label', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'income_from' => 'float',
        'income_to'   => 'float',
        'rate'        => 'float',
        'is_active'   => 'boolean',
    ];

    public static function getActive(string $category): Collection
    {
        // Cache array mentah, bukan Collection — lihat catatan di TaxBracket::getActive().
        $rows = Cache::remember("tax_ter_rates_{$category}", 3600, fn() =>
            static::where('category', $category)
                  ->where('is_active', true)
                  ->orderBy('sort_order')
                  ->get()
                  ->toArray()
        );

        return static::hydrate($rows);
    }

    /** Cari tarif TER yang berlaku untuk penghasilan bruto bulanan tertentu. */
    public static function rateFor(string $category, float $brutoBulanan): float
    {
        $bracket = static::getActive($category)->first(function ($r) use ($brutoBulanan) {
            $atas = $r->income_to ?? PHP_FLOAT_MAX;
            return $brutoBulanan >= $r->income_from && $brutoBulanan <= $atas;
        });

        return $bracket->rate ?? 0.0;
    }

    protected static function booted(): void
    {
        static::saved(fn(self $m) => Cache::forget("tax_ter_rates_{$m->category}"));
        static::deleted(fn(self $m) => Cache::forget("tax_ter_rates_{$m->category}"));
    }
}
