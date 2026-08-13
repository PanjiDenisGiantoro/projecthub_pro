<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Kasbon extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'tanggal', 'jumlah', 'cicilan_per_bulan',
        'sisa', 'periode_terakhir', 'potongan_periode_terakhir',
        'status', 'keterangan', 'created_by',
    ];

    protected $casts = [
        'tanggal'                    => 'date',
        'jumlah'                     => 'float',
        'cicilan_per_bulan'          => 'float',
        'sisa'                       => 'float',
        'potongan_periode_terakhir'  => 'float',
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

    public static function statusLabels(): array
    {
        return [
            'berjalan' => 'Berjalan',
            'lunas'    => 'Lunas',
        ];
    }
}
