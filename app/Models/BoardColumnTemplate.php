<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class BoardColumnTemplate extends Model
{
    protected $fillable = ['company_id', 'name', 'description', 'category', 'created_by'];

    /**
     * Suggested categories shown in the create form. The column itself is a
     * free-text string, so this is only a curated shortlist, not an enum.
     */
    public const CATEGORIES = [
        'umum' => 'Umum',
        'hr' => 'HR',
        'education' => 'Education',
        'marketing' => 'Marketing',
        'sales' => 'Sales',
        'finance' => 'Finance',
        'lainnya' => 'Lainnya',
    ];

    public function items()
    {
        return $this->hasMany(BoardColumnTemplateItem::class, 'template_id')->orderBy('sort_order');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * The system-wide default template applied automatically to every new
     * project and used by the board-columns backfill command.
     */
    public static function default(): ?self
    {
        return static::whereNull('company_id')->where('name', 'Kanban Dasar')->first();
    }

    /**
     * Clone this template's items into real board_columns rows for a
     * project. Shared by project auto-create, manual "apply template", and
     * the backfill command so the clone logic only lives in one place.
     */
    public function applyTo(Project $project): void
    {
        DB::transaction(function () use ($project) {
            foreach ($this->items as $item) {
                $project->boardColumns()->create([
                    'name' => $item->name,
                    'slug' => $item->slug,
                    'color' => $item->color,
                    'sort_order' => $item->sort_order,
                    'is_done' => $item->is_done,
                ]);
            }
        });
    }
}
