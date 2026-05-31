<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxBracket extends Model
{
    protected $fillable = [
        'income_from', 'income_to', 'rate', 'label', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'income_from' => 'float',
        'income_to'   => 'float',
        'rate'        => 'float',
        'is_active'   => 'boolean',
    ];

    public static function getActive(): Collection
    {
        return Cache::remember('tax_brackets_active', 3600, fn() =>
            static::where('is_active', true)->orderBy('sort_order')->get()
        );
    }

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('tax_brackets_active'));
    }
}
