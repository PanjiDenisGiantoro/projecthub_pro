<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalPolicy extends Model
{
    protected $fillable = [
        'module', 'action', 'flow_type', 'approver_roles',
        'timeout_hours', 'is_active', 'description',
    ];

    protected function casts(): array
    {
        return [
            'approver_roles' => 'array',
            'is_active'      => 'boolean',
        ];
    }

    public function approvals()
    {
        return $this->hasMany(Approval::class, 'policy_id');
    }
}
