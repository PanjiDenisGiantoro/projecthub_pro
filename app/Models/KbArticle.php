<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class KbArticle extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'project_id', 'author_id', 'title', 'category', 'description', 'external_url', 'body', 'parent_id', 'tags', 'version', 'is_pinned',
    ];

    protected function casts(): array
    {
        return [
            'tags'      => 'array',
            'is_pinned' => 'boolean',
        ];
    }

    public const CATEGORIES = [
        'brd'          => ['label' => 'BRD', 'name' => 'Business Requirement Document', 'bg' => 'bg-purple-50 dark:bg-purple-950/40', 'text' => 'text-purple-700 dark:text-purple-300', 'border' => 'border-purple-200 dark:border-purple-800', 'dot' => 'bg-purple-500'],
        'prd'          => ['label' => 'PRD', 'name' => 'Product Requirement Document', 'bg' => 'bg-blue-50 dark:bg-blue-950/40', 'text' => 'text-blue-700 dark:text-blue-300', 'border' => 'border-blue-200 dark:border-blue-800', 'dot' => 'bg-blue-500'],
        'fsd'          => ['label' => 'FSD', 'name' => 'Functional Specification Document', 'bg' => 'bg-indigo-50 dark:bg-indigo-950/40', 'text' => 'text-indigo-700 dark:text-indigo-300', 'border' => 'border-indigo-200 dark:border-indigo-800', 'dot' => 'bg-indigo-500'],
        'mom'          => ['label' => 'MOM', 'name' => 'Minutes of Meeting', 'bg' => 'bg-emerald-50 dark:bg-emerald-950/40', 'text' => 'text-emerald-700 dark:text-emerald-300', 'border' => 'border-emerald-200 dark:border-emerald-800', 'dot' => 'bg-emerald-500'],
        'architecture' => ['label' => 'Tech Spec', 'name' => 'Architecture & System Design', 'bg' => 'bg-amber-50 dark:bg-amber-950/40', 'text' => 'text-amber-700 dark:text-amber-300', 'border' => 'border-amber-200 dark:border-amber-800', 'dot' => 'bg-amber-500'],
        'guide'        => ['label' => 'Guide', 'name' => 'Onboarding & Guidelines', 'bg' => 'bg-cyan-50 dark:bg-cyan-950/40', 'text' => 'text-cyan-700 dark:text-cyan-300', 'border' => 'border-cyan-200 dark:border-cyan-800', 'dot' => 'bg-cyan-500'],
        'other'        => ['label' => 'General', 'name' => 'General Document', 'bg' => 'bg-slate-100 dark:bg-slate-800', 'text' => 'text-slate-700 dark:text-slate-300', 'border' => 'border-slate-200 dark:border-slate-700', 'dot' => 'bg-slate-400'],
    ];

    public function categoryMeta(): array
    {
        return self::CATEGORIES[$this->category] ?? self::CATEGORIES['other'];
    }

    public function domainName(): ?string
    {
        if (empty($this->external_url)) {
            return null;
        }

        $host = parse_url($this->external_url, PHP_URL_HOST);
        if (!$host) return 'External Link';

        $host = strtolower(preg_replace('/^www\./', '', $host));

        if (str_contains($host, 'figma.com')) return 'Figma';
        if (str_contains($host, 'docs.google.com/document')) return 'Google Docs';
        if (str_contains($host, 'docs.google.com/spreadsheets')) return 'Google Sheets';
        if (str_contains($host, 'docs.google.com/presentation')) return 'Google Slides';
        if (str_contains($host, 'drive.google.com')) return 'Google Drive';
        if (str_contains($host, 'notion.site') || str_contains($host, 'notion.so')) return 'Notion';
        if (str_contains($host, 'miro.com')) return 'Miro';
        if (str_contains($host, 'github.com')) return 'GitHub';
        if (str_contains($host, 'gitlab.com')) return 'GitLab';
        if (str_contains($host, 'atlassian.net') || str_contains($host, 'jira')) return 'Jira / Confluence';
        if (str_contains($host, 'loom.com')) return 'Loom';

        return ucfirst(explode('.', $host)[0]);
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
