<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ClientPortalToken extends Model
{
    protected $fillable = [
        'project_id', 'client_user_id', 'token', 'label',
        'can_comment', 'can_approve', 'show_budget',
        'expires_at', 'last_accessed_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'can_comment' => 'boolean',
            'can_approve' => 'boolean',
            'show_budget' => 'boolean',
            'expires_at' => 'datetime',
            'last_accessed_at' => 'datetime',
        ];
    }

    public static function generateToken(): string
    {
        return Str::random(60);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return !$this->isExpired();
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function clientUser()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
