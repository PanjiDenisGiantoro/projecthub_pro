<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    /**
     * Slug dari nama perusahaan tidak dijamin unik (nama boleh sama/mirip
     * antar perusahaan), padahal kolom code punya unique constraint. Tambah
     * suffix angka kalau base code sudah dipakai.
     */
    public static function uniqueCodeFor(string $name): string
    {
        $base = Str::upper(Str::slug($name, '')) ?: 'CO';
        $code = $base;
        $suffix = 2;

        while (static::where('code', $code)->exists()) {
            $code = $base . $suffix;
            $suffix++;
        }

        return $code;
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
