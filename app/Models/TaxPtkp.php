<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxPtkp extends Model
{
    protected $table = 'tax_ptkp';

    protected $fillable = [
        'status_code', 'label', 'amount', 'description', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'amount'    => 'float',
        'is_active' => 'boolean',
    ];

    public static function getAmount(string $statusCode): float
    {
        return Cache::remember("tax_ptkp_{$statusCode}", 3600, fn() =>
            static::where('status_code', $statusCode)
                  ->where('is_active', true)
                  ->value('amount') ?? 54_000_000
        );
    }

    public static function forSelect(): array
    {
        return static::where('is_active', true)
                     ->orderBy('sort_order')
                     ->pluck('label', 'status_code')
                     ->toArray();
    }

    protected static function booted(): void
    {
        static::saved(fn(self $m) => Cache::forget("tax_ptkp_{$m->status_code}"));
    }
}
