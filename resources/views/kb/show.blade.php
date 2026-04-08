@extends('layouts.app')
@section('title', $article->title)
@section('page-title', 'Knowledge Base')

@section('content')
<div class="py-4 max-w-3xl" x-data="{editing:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('kb.index', $project) }}" class="hover:text-blue-600">Knowledge Base</a>
        @if($article->parent)
            <span class="mx-2">/</span>
            <a href="{{ route('kb.show', [$project, $article->parent]) }}" class="hover:text-blue-600">{{ $article->parent->title }}</a>
        @endif
        <span class="mx-2">/</span>
        <span class="text-gray-700">{{ $article->title }}</span>
    </nav>

    {{-- Article --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between">
            <div>
                <h2 class="text-xl font-semibold text-gray-800">{{ $article->title }}</h2>
                <p class="text-xs text-gray-500 mt-1">
                    Ditulis oleh <strong>{{ $article->author->name }}</strong> ·
                    v{{ $article->version }} ·
                    Diperbarui {{ $article->updated_at->diffForHumans() }}
                </p>
                @if($article->tags)
                    <div class="flex gap-1 mt-2">
                        @foreach($article->tags as $tag)
                            <span class="badge bg-blue-50 text-blue-600 text-xs">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif
            </div>
            @if(!auth()->user()->hasRole('customer'))
            <div class="flex gap-2">
                <button @click="editing=!editing" class="text-sm text-blue-600 hover:text-blue-800 font-medium" x-text="editing ? 'Batal' : 'Edit'"></button>
                <form method="POST" action="{{ route('kb.destroy', [$project, $article]) }}" onsubmit="return confirm('Hapus artikel ini?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="text-sm text-red-500 hover:text-red-700">Hapus</button>
                </form>
            </div>
            @endif
        </div>

        {{-- View mode --}}
        <div x-show="!editing" class="px-6 py-5 prose prose-sm max-w-none">
            <pre class="whitespace-pre-wrap text-sm text-gray-700 font-sans leading-relaxed">{{ $article->body }}</pre>
        </div>

        {{-- Edit mode --}}
        <div x-show="editing" x-cloak class="px-6 py-5">
            <form method="POST" action="{{ route('kb.update', [$project, $article]) }}" class="space-y-4">
                @csrf @method('PUT')
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Judul</label>
                    <input type="text" name="title" value="{{ $article->title }}" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Konten</label>
                    <textarea name="body" rows="12" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono">{{ $article->body }}</textarea>
                </div>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan</button>
            </form>
        </div>
    </div>

    {{-- Sub-articles --}}
    @if($article->children->count())
    <div class="mt-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-3">Sub-artikel ({{ $article->children->count() }})</h4>
        <div class="space-y-2">
            @foreach($article->children as $child)
            <a href="{{ route('kb.show', [$project, $child]) }}"
               class="flex items-center justify-between bg-white rounded-lg border border-gray-200 px-4 py-3 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                <div>
                    <span class="text-sm font-medium text-gray-800">{{ $child->title }}</span>
                    <span class="ml-3 text-xs text-gray-400">v{{ $child->version }} · {{ $child->author->name }}</span>
                </div>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </a>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
