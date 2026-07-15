<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'channel', 'budget', 'actual_spend', 'target',
        'start_date', 'end_date', 'status', 'created_by', 'owner_id', 'project_id',
        'impressions', 'clicks', 'reach', 'leads_count', 'goal_leads',
    ];

    protected static function booted(): void
    {
        // Auto-filter per tenant; super admin bypass (sama seperti Project::booted()).
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && ! auth()->user()->is_super_admin && $cid = auth()->user()->company_id) {
                $builder->where('campaigns.company_id', $cid);
            }
        });

        static::creating(function (Campaign $campaign) {
            if (! $campaign->company_id && auth()->check() && auth()->user()->company_id) {
                $campaign->company_id = auth()->user()->company_id;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date'   => 'date',
            'end_date'     => 'date',
            'budget'       => 'decimal:2',
            'actual_spend' => 'decimal:2',
        ];
    }

    public function company()  { return $this->belongsTo(Company::class); }
    public function creator()  { return $this->belongsTo(User::class, 'created_by'); }
    public function owner()    { return $this->belongsTo(User::class, 'owner_id'); }
    public function project()  { return $this->belongsTo(Project::class); }
    public function leads()    { return $this->hasMany(Lead::class); }

    // ── Computed metrics ────────────────────────────────────────────────────────

    public function getConversionRateAttribute(): float
    {
        if ($this->leads_count === 0) return 0;
        return round(($this->leads()->where('status', 'client')->count() / $this->leads_count) * 100, 1);
    }

    public function getCtrAttribute(): float
    {
        if ($this->impressions === 0) return 0;
        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    public function getCplAttribute(): float
    {
        if ($this->leads_count === 0) return 0;
        return round($this->actual_spend / $this->leads_count, 0);
    }

    public function getProgressPercentAttribute(): int
    {
        if ($this->goal_leads === 0) return 0;
        return min(100, (int) round($this->leads_count / $this->goal_leads * 100));
    }

    public function getDaysRemainingAttribute(): ?int
    {
        if (!$this->end_date) return null;
        return (int) now()->startOfDay()->diffInDays($this->end_date->startOfDay(), false);
    }

    public function isOverdue(): bool
    {
        return $this->end_date && $this->end_date->isPast() && !in_array($this->status, ['completed', 'cancelled']);
    }
}
