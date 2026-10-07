<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\KbArticle;
use App\Models\KbArticleAttachment;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class KbArticleWebController extends Controller
{
    public function index(Request $request, Project $project)
    {
        $baseQuery = $project->kbArticles()->whereNull('parent_id');

        // Category counts for quick tabs / KPI metrics
        $categoryCounts = [
            'all'          => (clone $baseQuery)->count(),
            'brd'          => (clone $baseQuery)->where('category', 'brd')->count(),
            'prd'          => (clone $baseQuery)->where('category', 'prd')->count(),
            'fsd'          => (clone $baseQuery)->where('category', 'fsd')->count(),
            'mom'          => (clone $baseQuery)->where('category', 'mom')->count(),
            'architecture' => (clone $baseQuery)->where('category', 'architecture')->count(),
            'guide'        => (clone $baseQuery)->where('category', 'guide')->count(),
            'other'        => (clone $baseQuery)->where('category', 'other')->count(),
        ];

        $query = (clone $baseQuery)->with(['author', 'children', 'attachments.uploader']);

        if ($request->filled('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('body', 'like', "%{$search}%");
            });
        }

        $articles = $query->orderByDesc('is_pinned')->latest()->get();

        return view('kb.index', compact('project', 'articles', 'categoryCounts'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'nullable|string|max:50',
            'external_url' => 'nullable|url|max:2000',
            'description'  => 'nullable|string|max:500',
            'body'         => 'nullable|string',
            'parent_id'    => 'nullable|exists:kb_articles,id',
            'is_pinned'    => 'nullable|boolean',
            'files.*'      => 'nullable|file|max:20480',
        ]);

        $article = $project->kbArticles()->create([
            'title'        => $request->title,
            'category'     => $request->category ?: 'other',
            'external_url' => $request->external_url,
            'description'  => $request->description,
            'body'         => $request->body ?? '',
            'parent_id'    => $request->parent_id,
            'tags'         => $request->tags ? (is_array($request->tags) ? $request->tags : array_filter(array_map('trim', explode(',', $request->tags)))) : null,
            'is_pinned'    => $request->boolean('is_pinned'),
            'author_id'    => auth()->id(),
        ]);

        $this->handleFileUploads($request, $article);

        return back()->with('success', 'Dokumen Knowledge Base berhasil ditambahkan.');
    }

    public function show(Project $project, KbArticle $article)
    {
        $article->load(['author', 'parent', 'children.author', 'attachments.uploader']);
        return view('kb.show', compact('project', 'article'));
    }

    public function update(Request $request, Project $project, KbArticle $article)
    {
        $request->validate([
            'title'        => 'required|string|max:255',
            'category'     => 'nullable|string|max:50',
            'external_url' => 'nullable|url|max:2000',
            'description'  => 'nullable|string|max:500',
            'body'         => 'nullable|string',
            'parent_id'    => 'nullable|exists:kb_articles,id',
            'is_pinned'    => 'nullable|boolean',
            'files.*'      => 'nullable|file|max:20480',
        ]);

        $article->update([
            'title'        => $request->title,
            'category'     => $request->category ?: $article->category,
            'external_url' => $request->external_url,
            'description'  => $request->description,
            'body'         => $request->body ?? $article->body ?? '',
            'parent_id'    => $request->parent_id,
            'tags'         => $request->tags ? (is_array($request->tags) ? $request->tags : array_filter(array_map('trim', explode(',', $request->tags)))) : $article->tags,
            'is_pinned'    => $request->has('is_pinned') ? $request->boolean('is_pinned') : $article->is_pinned,
            'version'      => $article->version + 1,
        ]);

        $this->handleFileUploads($request, $article);

        return back()->with('success', 'Dokumen Knowledge Base diperbarui.');
    }

    public function destroy(Project $project, KbArticle $article)
    {
        foreach ($article->attachments as $att) {
            Storage::disk('public')->delete($att->stored_name);
        }
        $article->delete();
        return redirect()->route('kb.index', $project)->with('success', 'Dokumen dihapus.');
    }

    public function deleteAttachment(KbArticleAttachment $attachment)
    {
        $this->authorize('view', $attachment->article->project);

        Storage::disk('public')->delete($attachment->stored_name);
        $attachment->delete();
        return back()->with('success', 'Lampiran dihapus.');
    }

    private function handleFileUploads(Request $request, KbArticle $article): void
    {
        if (!$request->hasFile('files')) return;

        $descriptions = $request->input('file_descriptions', []);

        foreach ($request->file('files') as $index => $file) {
            if (!$file->isValid()) continue;
            $stored = $file->store("kb-attachments/{$article->id}", 'public');
            $article->attachments()->create([
                'original_name' => $file->getClientOriginalName(),
                'stored_name'   => $stored,
                'mime_type'     => $file->getMimeType(),
                'size'          => $file->getSize(),
                'description'   => $descriptions[$index] ?? null,
                'uploaded_by'   => auth()->id(),
            ]);
        }
    }
}
