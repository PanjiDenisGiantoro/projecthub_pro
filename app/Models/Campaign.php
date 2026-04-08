<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Campaign extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'channel', 'budget', 'target', 'start_date', 'end_date',
        'status', 'created_by', 'project_id', 'impressions', 'leads_count',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'budget' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function leads()
    {
        return $this->hasMany(Lead::class);
    }

    public function getConversionRateAttribute(): float
    {
        if ($this->leads_count === 0) return 0;
        $converted = $this->leads()->where('status', 'client')->count();
        return round(($converted / $this->leads_count) * 100, 2);
    }
}
