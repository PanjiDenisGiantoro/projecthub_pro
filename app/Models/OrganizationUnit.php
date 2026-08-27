<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrganizationUnit extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
        'level',
        'order',
        'head_id',
        'is_active',
        'color',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(OrganizationUnit::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(OrganizationUnit::class, 'parent_id');
    }

    public function head(): BelongsTo
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function descendants()
    {
        return static::where('company_id', $this->company_id)
            ->where('code', 'like', $this->code . '.%');
    }

    /** Warna kotak di Bagan Organisasi: dipilih manual per unit, atau default bergilir per level. */
    public function displayColor(): string
    {
        return $this->color ?: self::defaultColorForLevel($this->level);
    }

    public static function defaultColorForLevel(int $level): string
    {
        $palette = ['#1d4ed8', '#7c3aed', '#db2777', '#d97706', '#0891b2', '#16a34a'];
        return $palette[($level - 1) % count($palette)];
    }

    public function scopeRoots($query)
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Flat list dalam urutan DFS (root pertama lalu semua anaknya secara
     * rekursif) berdasarkan kolom `order` per grup sibling. Menghindari sort
     * berbasis string pada `code` yang keliru untuk "1.10" vs "1.2".
     */
    public static function orderedTree(int $companyId, array $exceptIds = []): \Illuminate\Support\Collection
    {
        $byParent = static::where('company_id', $companyId)
            ->when($exceptIds, fn($q) => $q->whereNotIn('id', $exceptIds))
            ->orderBy('order')
            ->get()
            ->groupBy(fn($unit) => $unit->parent_id ?? 'root');

        $flatten = function ($parentKey) use (&$flatten, $byParent) {
            $result = collect();
            foreach ($byParent->get($parentKey, collect()) as $node) {
                $result->push($node);
                $result = $result->merge($flatten($node->id));
            }
            return $result;
        };

        return new \Illuminate\Database\Eloquent\Collection($flatten('root')->all());
    }

    /**
     * Materialized-path code ("1", "1.2", "1.2.2") + level dihitung dari
     * posisi di antara sibling, bukan diinput manual, supaya kedalaman
     * pohon bebas tanpa perlu tabel/migration baru per tingkat.
     */
    public static function nextCodeForParent(?int $parentId, int $companyId): array
    {
        $order = static::where('company_id', $companyId)
            ->where('parent_id', $parentId)
            ->count() + 1;

        if ($parentId === null) {
            return [
                'code'  => (string) $order,
                'level' => 1,
                'order' => $order,
            ];
        }

        $parent = static::findOrFail($parentId);

        return [
            'code'  => $parent->code . '.' . $order,
            'level' => $parent->level + 1,
            'order' => $order,
        ];
    }

    /**
     * Dipanggil setelah unit ini dipindah ke parent lain: code/level unit
     * ini sendiri sudah di-update oleh caller, di sini tinggal menurunkan
     * ulang code/level seluruh descendant-nya secara rekursif.
     */
    public function regenerateDescendantCodes(): void
    {
        foreach ($this->children()->get() as $child) {
            $child->update([
                'code'  => $this->code . '.' . $child->order,
                'level' => $this->level + 1,
            ]);
            $child->regenerateDescendantCodes();
        }
    }
}
