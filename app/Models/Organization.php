<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model
{
    protected $fillable = [
        'company_id',
        'parent_id',
        'name',
        'code',
        'description',
        'head_id',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function parent()
    {
        return $this->belongsTo(Organization::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Organization::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function head()
    {
        return $this->belongsTo(User::class, 'head_id');
    }

    /** Level 1 = akar (parent_id null). Dihitung dari rantai parent, bukan disimpan statis. */
    public function depth(): int
    {
        $depth = 1;
        $node = $this;
        while ($node->parent_id) {
            $node = $node->parent;
            $depth++;
        }
        return $depth;
    }

    /** Semua id keturunan — dipakai untuk cegah sebuah node dijadikan parent dari leluhurnya sendiri (cycle). */
    public function descendantIds(): array
    {
        $ids = [];
        $stack = static::where('parent_id', $this->id)->pluck('id')->all();
        while ($stack) {
            $id = array_pop($stack);
            $ids[] = $id;
            $stack = array_merge($stack, static::where('parent_id', $id)->pluck('id')->all());
        }
        return $ids;
    }
}
