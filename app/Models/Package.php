<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    protected $fillable = [
        'slug', 'type', 'name', 'description', 'tagline',
        'price', 'price_display', 'price_period', 'duration_days', 'max_users',
        'is_active', 'is_popular', 'cta_label', 'cta_type', 'sort_order',
        'icon', 'color', 'fitur_text', 'hris_feature',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'is_popular' => 'boolean',
        'price'      => 'integer',
        'max_users'  => 'integer',
    ];

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
}
