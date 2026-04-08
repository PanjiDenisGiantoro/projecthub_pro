@extends('layouts.app')
@section('title', 'Knowledge Base — ' . $project->name)
@section('page-title', 'Knowledge Base')

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">Knowledge Base</span>
    </nav>

    <div class="flex justify-between items-center mb-4">
        <form method="GET" class="flex gap-2">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari artikel..." class="px-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 w-64">
            <button type="submit" class="bg-gray-100 hover:bg-gray-200 text-gray-700 text-sm px-4 py-2 rounded-lg transition-colors">Cari</button>
        </form>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            + Artikel Baru
        </button>
        @endif
    </div>

    {{-- New Article Form --}}
    @if(!auth()->user()->hasRole('customer'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Artikel Baru</h4>
        <form method="POST" action="{{ route('kb.store', $project) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Judul *</label>
                <input type="text" name="title" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Konten *</label>
                <textarea name="body" rows="6" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"></textarea>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Parent Artikel</label>
                <select name="parent_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">— Root (tidak ada parent) —</option>
                    @foreach($articles as $a)
                        <option value="{{ $a->id }}">{{ $a->title }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan Artikel</button>
        </form>
    </div>
    @endif

    {{-- Articles List --}}
    <div class="space-y-3">
        @forelse($articles as $article)
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <a href="{{ route('kb.show', [$project, $article]) }}" class="text-lg font-semibold text-gray-800 hover:text-blue-600">
                        {{ $article->title }}
                    </a>
                    <div class="flex items-center gap-3 mt-1 text-xs text-gray-500">
                        <span>v{{ $article->version }}</span>
                        <span>·</span>
                        <span>{{ $article->author->name }}</span>
                        <span>·</span>
                        <span>{{ $article->updated_at->diffForHumans() }}</span>
                        @if($article->children->count())
                            <span>·</span>
                            <span>{{ $article->children->count() }} sub-artikel</span>
                        @endif
                    </div>
                    @if($article->tags)
                        <div class="flex gap-1 mt-2 flex-wrap">
                            @foreach($article->tags as $tag)
                                <span class="badge bg-blue-50 text-blue-600 text-xs">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <a href="{{ route('kb.show', [$project, $article]) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium ml-4">Baca →</a>
            </div>

            {{-- Sub-articles --}}
            @if($article->children->count())
            <div class="mt-3 pl-4 border-l-2 border-gray-100 space-y-1">
                @foreach($article->children as $child)
                <a href="{{ route('kb.show', [$project, $child]) }}" class="block text-sm text-gray-600 hover:text-blue-600">
                    📄 {{ $child->title }}
                </a>
                @endforeach
            </div>
            @endif
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">
            <p class="text-lg mb-2">📚</p>
            <p>Belum ada artikel. Mulai dokumentasi proyek Anda.</p>
        </div>
        @endforelse
    </div>
</div>
@endsection
