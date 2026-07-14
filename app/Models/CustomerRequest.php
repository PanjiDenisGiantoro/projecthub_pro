<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerRequest extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'company_id', 'project_id', 'customer_id', 'title', 'description', 'type',
        'priority', 'status', 'rejection_reason', 'marketing_notes',
        'reviewed_by', 'approved_by', 'approved_at', 'attachment_path',
    ];

    protected static function booted(): void
    {
        // Auto-filter per tenant; super admin bypass (sama seperti Project::booted()).
        static::addGlobalScope('company', function (Builder $builder) {
            if (auth()->check() && ! auth()->user()->is_super_admin && $cid = auth()->user()->company_id) {
                $builder->where('customer_requests.company_id', $cid);
            }
        });

        static::creating(function (CustomerRequest $request) {
            if (! $request->company_id && auth()->check() && auth()->user()->company_id) {
                $request->company_id = auth()->user()->company_id;
            }
        });
    }

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logAll()->useLogName('customer_request');
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
