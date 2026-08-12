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
        // Cache array mentah, bukan Collection — menyimpan objek Eloquent lewat cache
        // driver "database" gagal di-unserialize di proses/request baru (jadi
        // __PHP_Incomplete_Class). Array biasa aman, lalu di-hydrate lagi jadi model.
        $rows = Cache::remember('tax_brackets_active', 3600, fn() =>
            static::where('is_active', true)->orderBy('sort_order')->get()->toArray()
        );

        return static::hydrate($rows);
    }

    protected static function booted(): void
    {
        static::saved(fn() => Cache::forget('tax_brackets_active'));
    }
}
