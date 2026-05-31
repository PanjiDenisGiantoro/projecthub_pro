<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OvertimeRule extends Model
{
    protected $fillable = [
        'company_id', 'day_type', 'hour_from', 'hour_to',
        'multiplier', 'label', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'multiplier' => 'float',
        'is_active'  => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function forCompany(int $companyId, string $dayType): Collection
    {
        return static::where('company_id', $companyId)
            ->where('day_type', $dayType)
            ->where('is_active', true)
            ->orderBy('hour_from')
            ->get();
    }
}
