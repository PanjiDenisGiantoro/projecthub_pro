<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

/**
 * Konfigurasi satu sumber API absensi eksternal (mis. mesin fingerprint,
 * HRIS lain). Admin mengatur method GET/POST, format request (query/body)
 * dan mapping field response ke kolom attendances lewat halaman
 * "Integrasi API" — lihat AttendanceApiController & AttendanceApiSyncService.
 */
class AttendanceApiSource extends Model
{
    use LogsActivity;

    protected $fillable = [
        'company_id', 'name', 'method', 'url',
        'auth_type', 'auth_config',
        'request_template', 'response_data_path', 'response_mapping',
        'auto_insert', 'overwrite_on_conflict', 'is_active',
    ];

    protected $hidden = ['auth_config'];

    protected static function booted(): void
    {
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && ! auth()->user()->is_super_admin && $cid = auth()->user()->company_id) {
                $builder->where('attendance_api_sources.company_id', $cid);
            }
        });

        static::creating(function (self $source) {
            if (! $source->company_id && auth()->check() && auth()->user()->company_id) {
                $source->company_id = auth()->user()->company_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'auth_config'            => 'encrypted:array',
            'request_template'       => 'array',
            'response_mapping'       => 'array',
            'auto_insert'            => 'boolean',
            'overwrite_on_conflict'  => 'boolean',
            'is_active'              => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function syncLogs(): HasMany
    {
        return $this->hasMany(AttendanceApiSyncLog::class)->latest();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'name', 'method', 'url', 'auth_type', 'response_data_path',
                'auto_insert', 'overwrite_on_conflict', 'is_active',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('attendance_api_source');
    }
}
