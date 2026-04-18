<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approval extends Model
{
    protected $fillable = [
        'approvable_type', 'approvable_id', 'policy_id', 'action',
        'status', 'requested_by', 'metadata', 'expires_at', 'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'expires_at' => 'datetime',
            'decided_at' => 'datetime',
        ];
    }

    public function approvable()
    {
        return $this->morphTo();
    }

    public function policy()
    {
        return $this->belongsTo(ApprovalPolicy::class);
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function steps()
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('step_order');
    }

    public function currentStep()
    {
        return $this->steps()->where('status', 'pending')->orderBy('step_order')->first();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
