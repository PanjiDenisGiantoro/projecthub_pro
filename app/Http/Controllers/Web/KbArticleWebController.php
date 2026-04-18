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
        $query = $project->kbArticles()->with(['author', 'children', 'attachments'])->whereNull('parent_id')
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%")
                ->orWhere('description', 'like', "%{$request->search}%"));
        $articles = $query->latest()->get();
        return view('kb.index', compact('project', 'articles'));
    }

    public function store(Request $request, Project $project)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'description' => 'nullable|string|max:500',
            'files.*'     => 'nullable|file|max:20480',
        ]);

        $article = $project->kbArticles()->create([
            ...$request->only('title', 'description', 'body', 'parent_id', 'tags'),
            'author_id' => auth()->id(),
        ]);

        $this->handleFileUploads($request, $article);

        return back()->with('success', 'Artikel dibuat.');
    }

    public function show(Project $project, KbArticle $article)
    {
        $article->load(['author', 'parent', 'children.author', 'attachments.uploader']);
        return view('kb.show', compact('project', 'article'));
    }

    public function update(Request $request, Project $project, KbArticle $article)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'body'        => 'required|string',
            'description' => 'nullable|string|max:500',
            'files.*'     => 'nullable|file|max:20480',
        ]);

        $article->update([
            ...$request->only('title', 'description', 'body', 'tags'),
            'version' => $article->version + 1,
        ]);

        $this->handleFileUploads($request, $article);

        return back()->with('success', 'Artikel diperbarui.');
    }

    public function destroy(Project $project, KbArticle $article)
    {
        foreach ($article->attachments as $att) {
            Storage::disk('public')->delete($att->stored_name);
        }
        $article->delete();
        return redirect()->route('kb.index', $project)->with('success', 'Artikel dihapus.');
    }

    public function deleteAttachment(KbArticleAttachment $attachment)
    {
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
