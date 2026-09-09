<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Holiday extends Model
{
    protected $fillable = ['company_id', 'date', 'name'];

    protected $casts = ['date' => 'date'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Daftar tanggal libur ("Y-m-d") suatu company — dipakai OvertimeService::dayType(). */
    public static function datesForCompany(int $companyId): array
    {
        return static::where('company_id', $companyId)->pluck('date')->map(fn ($d) => $d->toDateString())->all();
    }
}
