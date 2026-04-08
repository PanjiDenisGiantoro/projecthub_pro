<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\Project;
use Illuminate\Http\Request;

class KbArticleWebController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $query = $project->kbArticles()->with(['author', 'children'])->whereNull('parent_id')
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"));
        $articles = $query->latest()->get();
        return view('kb.index', compact('project', 'articles'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate(['title' => 'required|string|max:255', 'body' => 'required|string']);
        $project->kbArticles()->create([...$request->only('title', 'body', 'parent_id', 'tags'), 'author_id' => auth()->id()]);
        return back()->with('success', 'Artikel dibuat.');
    }

    public function show(Project $project, KbArticle $article)
    {
        $article->load(['author', 'parent', 'children.author']);
        return view('kb.show', compact('project', 'article'));
    }

    public function update(Request $request, Project $project, KbArticle $article)
    {
        $request->validate(['title' => 'required|string|max:255', 'body' => 'required|string']);
        $article->update([...$request->only('title', 'body', 'tags'), 'version' => $article->version + 1]);
        return back()->with('success', 'Artikel diperbarui.');
    }

    public function destroy(Project $project, KbArticle $article)
    {
        $article->delete();
        return redirect()->route('kb.index', $project)->with('success', 'Artikel dihapus.');
    }
}
