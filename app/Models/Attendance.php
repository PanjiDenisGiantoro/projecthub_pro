<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = [
        'user_id', 'company_id', 'date', 'check_in', 'check_out',
        'status', 'location_in', 'location_out', 'photo_in', 'notes',
        'lat_in', 'lng_in', 'distance_in',
        'lat_out', 'lng_out',
        'face_verified_in', 'face_verified_out',
        // Sesi ke-2, dipakai kalau shift-nya split (lihat Shift::isSplit()) — tetap
        // null untuk shift biasa.
        'check_in_2', 'check_out_2', 'location_in_2',
        'lat_in_2', 'lng_in_2', 'distance_in_2',
        'lat_out_2', 'lng_out_2',
        'face_verified_in_2', 'face_verified_out_2',
    ];

    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Total menit kerja sesi 1 + sesi 2 (sesi 2 cuma keisi buat shift split). */
    public function workMinutes(): int
    {
        $minutes = 0;

        if ($this->check_in && $this->check_out) {
            $minutes += self::sessionMinutes($this->check_in, $this->check_out);
        }
        if ($this->check_in_2 && $this->check_out_2) {
            $minutes += self::sessionMinutes($this->check_in_2, $this->check_out_2);
        }

        return $minutes;
    }

    /**
     * Durasi 1 sesi dari jam masuk & keluar (cuma jam, tanpa tanggal). Jam keluar lebih kecil
     * dari jam masuk = check-out besok paginya (shift malam, mis. 22:00 -> 05:00 = 7 jam).
     */
    private static function sessionMinutes(string $in, string $out): int
    {
        $minutes = (int) Carbon::parse($in)->diffInMinutes(Carbon::parse($out));

        return $minutes < 0 ? $minutes + 1440 : $minutes;
    }

    /** True kalau sesi 2 (shift split) sudah/sedang dipakai hari itu. */
    public function hasSession2(): bool
    {
        return $this->check_in_2 !== null;
    }
}
