<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bonus extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'year', 'month',
        'type', 'amount', 'description', 'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public static function typeLabels(): array
    {
        return [
            'bonus'         => 'Bonus',
            'thr'           => 'THR',
            'gratifikasi'   => 'Gratifikasi',
            'jasa_produksi' => 'Jasa Produksi',
            'lainnya'       => 'Lainnya',
        ];
    }
}
