<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'slug', 'type', 'name', 'description', 'tagline',
        'price', 'price_yearly', 'price_display', 'price_period', 'duration_days', 'min_users', 'max_users',
        'max_workspaces', 'max_projects', 'storage_label', 'has_android', 'has_ios', 'support_label',
        'is_active', 'is_popular', 'cta_label', 'cta_type', 'sort_order',
        'icon', 'color', 'fitur_text', 'hris_feature',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_popular' => 'boolean',
        'price'      => 'integer',
        'max_users'  => 'integer',
        'price_yearly'   => 'integer',
        'min_users'      => 'integer',
        'max_workspaces' => 'integer',
        'max_projects'   => 'integer',
        'has_android'    => 'boolean',
        'has_ios'        => 'boolean',
    ];

    /** Bulan per siklus tagihan. */
    public const CYCLE_MONTHS = ['monthly' => 1, 'yearly' => 12];

    public function users()
    {
        return $this->belongsToMany(User::class)->withTimestamps();
    }

    public function features()
    {
        return $this->hasMany(PackageFeature::class)->orderBy('sort_order');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTiers($query)
    {
        return $query->where('type', 'tier');
    }

    public function priceDisplay(): string
    {
        if ($this->price_display) {
            return $this->price_display;
        }

        return $this->price === null ? 'Custom' : 'Rp ' . number_format($this->price, 0, ',', '.');
    }

    public function maxUsersDisplay(): string
    {
        return $this->max_users === null ? 'Custom' : (string) $this->max_users;
    }

    /** Harga per user per bulan untuk siklus tagihan (monthly | yearly). */
    public function pricePerUser(string $cycle = 'monthly'): int
    {
        return (int) ($cycle === 'yearly' ? ($this->price_yearly ?? $this->price) : $this->price);
    }

    /** Subtotal (sebelum PPN) = harga per user × jumlah user × bulan dalam siklus. */
    public function subtotalFor(int $users, string $cycle = 'monthly'): int
    {
        return $this->pricePerUser($cycle) * $users * (self::CYCLE_MONTHS[$cycle] ?? 1);
    }

    public function durationDaysFor(string $cycle = 'monthly'): int
    {
        return $cycle === 'yearly' ? 365 : ($this->duration_days ?: 30);
    }

    /** Jumlah user yang sah untuk paket ini (dipaksa ke rentang min–max). */
    public function clampUsers(?int $users): int
    {
        $users = max((int) $users, $this->min_users ?? 1);

        return $this->max_users ? min($users, $this->max_users) : $users;
    }

    /** "9 – 19", "≤ 8", "≥ 200" */
    public function usersRangeDisplay(): string
    {
        if (! $this->max_users) {
            return '≥ ' . ($this->min_users ?? 1);
        }

        return ($this->min_users ?? 1) <= 1 ? '≤ ' . $this->max_users : $this->min_users . ' – ' . $this->max_users;
    }

    /**
     * Rincian harga satu order: harga per user × user × bulan → potong promo terbaik → + PPN.
     * 'subtotal' = setelah promo (dasar PPN, dipakai order & DOKU), 'gross' = sebelum promo.
     * Tanpa $companyId hanya promo untuk semua perusahaan yang berlaku (mis. landing / daftar).
     */
    public function quote(?int $users = null, string $cycle = 'monthly', ?int $companyId = null): array
    {
        $cycle    = array_key_exists($cycle, self::CYCLE_MONTHS) ? $cycle : 'monthly';
        $users    = $this->clampUsers($users);
        $gross    = $this->subtotalFor($users, $cycle);
        $promo    = Promo::bestFor($gross, $companyId);
        $discount = $promo?->discountFor($gross) ?? 0;

        return [
            'users'          => $users,
            'cycle'          => $cycle,
            'months'         => self::CYCLE_MONTHS[$cycle],
            'price_per_user' => $this->pricePerUser($cycle),
            'gross'          => $gross,
            'promo'          => $promo,
            'discount'       => $discount,
            'duration_days'  => $this->durationDaysFor($cycle),
        ] + PpnRate::breakdown($gross - $discount);
    }

    /** Persen hemat tagihan tahunan dibanding bulanan (mis. 30), 0 kalau tidak ada. */
    public function yearlySavingPercent(): int
    {
        if (! $this->price || ! $this->price_yearly || $this->price_yearly >= $this->price) {
            return 0;
        }

        return (int) round((1 - $this->price_yearly / $this->price) * 100);
    }

    public static function rupiah(?int $value): string
    {
        return 'Rp ' . number_format((int) $value, 0, ',', '.');
    }

    public static function limitDisplay(?int $value): string
    {
        return $value === null ? 'Unlimited' : number_format($value, 0, ',', '.');
    }
}
