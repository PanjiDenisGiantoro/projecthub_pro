<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KbArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'author_id', 'title', 'description', 'body', 'parent_id', 'tags', 'version',
    ];

    protected function casts(): array
    {
        return ['tags' => 'array'];
    }

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function parent()
    {
        return $this->belongsTo(KbArticle::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(KbArticle::class, 'parent_id');
    }

    public function attachments()
    {
        return $this->hasMany(KbArticleAttachment::class, 'article_id');
    }
}
