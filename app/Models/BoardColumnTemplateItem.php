<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BoardColumnTemplateItem extends Model
{
    public $timestamps = false;

    protected $fillable = ['template_id', 'name', 'slug', 'color', 'sort_order', 'is_done'];

    protected function casts(): array
    {
        return [
            'is_done' => 'boolean',
        ];
    }

    public function template()
    {
        return $this->belongsTo(BoardColumnTemplate::class, 'template_id');
    }
}
