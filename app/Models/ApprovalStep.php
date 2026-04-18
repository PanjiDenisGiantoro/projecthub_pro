<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApprovalStep extends Model
{
    protected $fillable = [
        'approval_id', 'step_order', 'approver_id',
        'approver_role', 'status', 'decided_at', 'notes',
    ];

    protected function casts(): array
    {
        return ['decided_at' => 'datetime'];
    }

    public function approval()
    {
        return $this->belongsTo(Approval::class);
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
