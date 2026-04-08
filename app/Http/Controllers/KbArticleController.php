<?php

namespace App\Http\Controllers;

use App\Models\KbArticle;
use App\Models\Project;
use Illuminate\Http\Request;

class KbArticleController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $query = $project->kbArticles()
            ->with(['author', 'parent'])
            ->whereNull('parent_id') // root articles only
            ->when($request->search, fn($q) => $q->whereFullText(['title', 'body'], $request->search));

        return response()->json($query->latest()->get());
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'body' => 'required|string',
            'parent_id' => 'nullable|exists:kb_articles,id',
            'tags' => 'nullable|array',
        ]);

        $article = $project->kbArticles()->create([
            ...$request->only('title', 'body', 'parent_id', 'tags'),
            'author_id' => $request->user()->id,
        ]);

        return response()->json($article->load(['author', 'parent']), 201);
    }

    public function show(Project $project, KbArticle $kbArticle)
    {
        return response()->json($kbArticle->load(['author', 'parent', 'children']));
    }

    public function update(Request $request, Project $project, KbArticle $kbArticle)
    {
        $request->validate([
            'title' => 'sometimes|string|max:255',
            'body' => 'sometimes|string',
            'tags' => 'nullable|array',
        ]);

        $kbArticle->update([
            ...$request->only('title', 'body', 'parent_id', 'tags'),
            'version' => $kbArticle->version + 1,
        ]);

        return response()->json($kbArticle->fresh()->load(['author']));
    }

    public function destroy(Project $project, KbArticle $kbArticle)
    {
        $kbArticle->delete();
        return response()->json(['message' => 'Article deleted.']);
    }
}
