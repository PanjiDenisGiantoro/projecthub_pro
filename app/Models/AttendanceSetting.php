<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceSetting extends Model
{
    protected $fillable = [
        'company_id',
        'is_location_enabled',
        'office_name',
        'office_latitude',
        'office_longitude',
        'max_distance_meters',
        'require_location_for_checkout',
        'is_face_recognition_enabled',
        'face_recognition_threshold',
        'require_face_for_checkout',
    ];

    protected $casts = [
        'is_location_enabled'           => 'boolean',
        'require_location_for_checkout' => 'boolean',
        'is_face_recognition_enabled'   => 'boolean',
        'require_face_for_checkout'     => 'boolean',
        'office_latitude'               => 'float',
        'office_longitude'              => 'float',
        'face_recognition_threshold'    => 'float',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Get or create settings for a company */
    public static function forCompany(int $companyId): self
    {
        return self::firstOrCreate(
            ['company_id' => $companyId],
            ['max_distance_meters' => 100, 'face_recognition_threshold' => 0.55]
        );
    }

    /** Haversine distance in meters between two coordinates */
    public function distanceFrom(float $lat, float $lng): float
    {
        if (!$this->office_latitude || !$this->office_longitude) return 0;

        $R    = 6371000; // Earth radius in meters
        $φ1   = deg2rad($this->office_latitude);
        $φ2   = deg2rad($lat);
        $Δφ   = deg2rad($lat  - $this->office_latitude);
        $Δλ   = deg2rad($lng  - $this->office_longitude);

        $a = sin($Δφ / 2) ** 2 + cos($φ1) * cos($φ2) * sin($Δλ / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($R * $c);
    }
}
