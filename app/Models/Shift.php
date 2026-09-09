<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    protected $fillable = [
        'company_id', 'name', 'start_time', 'end_time',
        'break_start_time', 'break_end_time',
        'tolerance_minutes', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function workingDays(): HasMany
    {
        return $this->hasMany(ShiftWorkingDay::class);
    }

    /** "07:00 - 16:00" buat ditampilkan di dropdown/list, jam disimpan H:i:s. */
    public function timeRangeLabel(): string
    {
        return substr($this->start_time, 0, 5) . ' - ' . substr($this->end_time, 0, 5);
    }

    /** Split shift = 2 sesi kerja dalam sehari dengan jeda panjang di tengah (mis. kurir/outlet). */
    public function isSplit(): bool
    {
        return $this->break_start_time !== null && $this->break_end_time !== null;
    }

    /** "08:00-12:00 & 16:00-20:00" buat shift split, atau timeRangeLabel() biasa kalau bukan split. */
    public function sessionsLabel(): string
    {
        if (!$this->isSplit()) {
            return $this->timeRangeLabel();
        }

        return substr($this->start_time, 0, 5) . '-' . substr($this->break_start_time, 0, 5)
            . ' & ' . substr($this->break_end_time, 0, 5) . '-' . substr($this->end_time, 0, 5);
    }

    /** 0 = Minggu ... 6 = Sabtu, ikut konvensi Carbon::dayOfWeek(). */
    public const DAY_LABELS = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];

    public function isWorkingDay(int $dayOfWeek): bool
    {
        return $this->workingDays->contains('day_of_week', $dayOfWeek);
    }

    /** "Senin - Jumat", "Senin - Sabtu", atau "Senin, Rabu, Jumat" kalau harinya tidak berurutan. */
    public function workingDaysLabel(): string
    {
        $days = $this->workingDays->pluck('day_of_week');
        if ($days->isEmpty()) {
            return 'Belum diatur';
        }

        return $this->dayRangeLabel($days);
    }

    /** "Senin - Jumat" dsb dari daftar day_of_week — dipakai workingDaysLabel() dan hoursSummary(). */
    private function dayRangeLabel($days): string
    {
        $days = $days->sort()->values();

        // Senin(1)..Minggu(0) berurutan tanpa celah -> tampilkan sebagai rentang.
        $ordered = $days->map(fn ($d) => $d === 0 ? 7 : $d)->sort()->values();
        $isConsecutive = $ordered->count() > 1
            && $ordered->last() - $ordered->first() === $ordered->count() - 1;

        if ($isConsecutive) {
            return self::DAY_LABELS[$ordered->first() % 7] . ' - ' . self::DAY_LABELS[$ordered->last() % 7];
        }

        return $days->map(fn ($d) => self::DAY_LABELS[$d])->implode(', ');
    }

    /** Jam masuk efektif di hari tsb — ikut jam custom hari itu kalau diatur, kalau tidak ikut jam default shift. */
    public function effectiveStartTime(int $dayOfWeek): ?string
    {
        return $this->workingDays->firstWhere('day_of_week', $dayOfWeek)?->start_time ?? $this->start_time;
    }

    /** Jam pulang efektif di hari tsb — ikut jam custom hari itu kalau diatur, kalau tidak ikut jam default shift. */
    public function effectiveEndTime(int $dayOfWeek): ?string
    {
        return $this->workingDays->firstWhere('day_of_week', $dayOfWeek)?->end_time ?? $this->end_time;
    }

    /** True kalau ada minimal 1 hari kerja yang jamnya beda dari jam default shift (mis. Sabtu setengah hari). */
    public function hasCustomDayHours(): bool
    {
        return $this->workingDays->contains(fn (ShiftWorkingDay $wd) => $wd->start_time !== null);
    }

    /**
     * Ringkasan jam kerja buat ditampilkan di daftar shift. Kalau semua hari kerja jamnya
     * sama, sama kayak timeRangeLabel()/sessionsLabel() biasa. Kalau ada hari yang jamnya
     * beda (mis. Sabtu setengah hari), dikelompokkan per jam: "Sen - Jum 08:00-17:00, Sab 08:00-12:00".
     */
    public function hoursSummary(): string
    {
        if (!$this->hasCustomDayHours() || $this->workingDays->isEmpty()) {
            return $this->isSplit() ? $this->sessionsLabel() : $this->timeRangeLabel();
        }

        return $this->workingDays
            ->groupBy(fn (ShiftWorkingDay $wd) => ($wd->start_time ?? $this->start_time) . '|' . ($wd->end_time ?? $this->end_time))
            ->map(function ($group, $key) {
                [$start, $end] = explode('|', $key);
                $dayLabel = $this->dayRangeLabel($group->pluck('day_of_week'));

                return $dayLabel . ' ' . substr($start, 0, 5) . '-' . substr($end, 0, 5);
            })
            ->implode(', ');
    }
}
