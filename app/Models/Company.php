<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'address',
        'phone',
        'email',
        'website',
        'logo',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function branches()
    {
        return $this->hasMany(Branch::class);
    }

    public function divisions()
    {
        return $this->hasManyThrough(Division::class, Branch::class);
    }

    public function departments()
    {
        return $this->hasManyThrough(
            Department::class,
            Division::class,
            'branch_id',
            'division_id',
            'id',
            'id'
        );
    }
}
