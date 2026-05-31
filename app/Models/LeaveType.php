<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'company_id', 'name', 'code', 'description', 'default_quota',
        'is_paid', 'needs_attachment', 'needs_approval', 'has_balance',
        'gender_restriction', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_paid'            => 'boolean',
        'needs_attachment'   => 'boolean',
        'needs_approval'     => 'boolean',
        'has_balance'        => 'boolean',
        'is_active'          => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function requests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function isEligible(User $user): bool
    {
        if (!$this->is_active) return false;
        if ($this->gender_restriction === 'all') return true;
        return $this->gender_restriction === ($user->gender ?? 'all');
    }
}
