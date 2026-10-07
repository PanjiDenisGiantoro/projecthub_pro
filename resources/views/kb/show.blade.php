@extends('layouts.app')
@section('title', $article->title . ' — Knowledge Base')
@section('page-title', 'Knowledge Base')

@section('main-class', 'flex-1 px-4 sm:px-6 pt-0 pb-8 overflow-y-auto overflow-x-hidden w-full max-w-full min-w-0')

@section('content')
<div class="pt-4 pb-6 flex flex-col lg:flex-row gap-6 items-start w-full max-w-full min-w-0"
     x-data="{ editing: false }"
     style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">

    {{-- Project Sidebar --}}
    @include('projects.partials.sidebar', ['project' => $project, 'tab' => 'kb'])

    {{-- Main Reader Area --}}
    <div class="flex-1 min-w-0 w-full max-w-4xl space-y-5">

        {{-- Breadcrumbs & Top Navigation --}}
        <div class="flex items-center justify-between gap-4">
            <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 flex-wrap">
                <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600 transition">{{ $project->name }}</a>
                <span>/</span>
                <a href="{{ route('kb.index', $project) }}" class="hover:text-blue-600 transition">Knowledge Base</a>
                @if($article->parent)
                    <span>/</span>
                    <a href="{{ route('kb.show', [$project, $article->parent]) }}" class="hover:text-blue-600 transition">{{ $article->parent->title }}</a>
                @endif
                <span>/</span>
                <span class="text-slate-800 dark:text-slate-200 font-semibold truncate max-w-[200px]">{{ $article->title }}</span>
            </nav>

            <a href="{{ route('kb.index', $project) }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-300 hover:bg-slate-50 transition shrink-0">
                <span>&larr;</span>
                <span>Kembali ke Daftar</span>
            </a>
        </div>

        @php
            $meta = $article->categoryMeta();
            $hasUrl = !empty($article->external_url);
            $domain = $article->domainName();
        @endphp

        {{-- Document Main Card --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl overflow-hidden">

            {{-- Document Header --}}
            <div class="px-6 py-6 border-b border-slate-100 dark:border-slate-700/80">
                <div class="flex flex-col sm:flex-row sm:items-start justify-between gap-4">
                    <div class="space-y-2 flex-1 min-w-0">
                        {{-- Badges Row --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold border {{ $meta['bg'] }} {{ $meta['text'] }} {{ $meta['border'] }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                {{ $meta['label'] }} &mdash; {{ $meta['name'] }}
                            </span>

                            @if($article->is_pinned)
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-md text-xs font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                    <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                        <path d="M10 2a1 1 0 011 1v4.243l2.828 2.829a1 1 0 01.293.707V13a1 1 0 01-1 1h-2v4a1 1 0 11-2 0v-4H7a1 1 0 01-1-1v-2.221a1 1 0 01.293-.707L9.121 7.243V3a1 1 0 011-1z"/>
                                    </svg>
                                    Pinned
                                </span>
                            @endif

                            <span class="text-xs font-mono px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                Versi {{ $article->version }}
                            </span>
                        </div>

                        {{-- Title --}}
                        <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight leading-snug">
                            {{ $article->title }}
                        </h1>

                        {{-- Description / Subtitle --}}
                        @if($article->description)
                            <p class="text-sm text-slate-600 dark:text-slate-300 leading-relaxed font-normal pt-1">
                                {{ $article->description }}
                            </p>
                        @endif

                        {{-- Author Metadata --}}
                        <div class="flex items-center gap-3 pt-2 text-xs text-slate-400">
                            <div class="flex items-center gap-2">
                                @if($article->author?->avatar)
                                    <img src="{{ Storage::url($article->author->avatar) }}" alt="{{ $article->author->name }}" class="w-5 h-5 rounded-full object-cover">
                                @else
                                    <div class="w-5 h-5 rounded-full flex items-center justify-center text-[9px] font-bold text-white" style="background-color: {{ $article->author ? $article->author->avatarColor() : '#3b82f6' }}">
                                        {{ $article->author ? $article->author->initials() : 'NA' }}
                                    </div>
                                @endif
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $article->author->name ?? 'Tim' }}</span>
                            </div>
                            <span>&bull;</span>
                            <span>Diperbarui {{ $article->updated_at->isoFormat('D MMMM Y, HH:mm') }} ({{ $article->updated_at->diffForHumans() }})</span>
                        </div>
                    </div>

                    {{-- Actions (Edit & Delete) --}}
                    @if(!auth()->user()->hasRole('client'))
                        <div class="flex items-center gap-2 shrink-0 self-start">
                            <button type="button" @click="editing = !editing"
                                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold transition border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700 cursor-pointer"
                                    :class="editing ? 'bg-slate-100 text-slate-800 dark:bg-slate-700 dark:text-white' : 'text-slate-700 dark:text-slate-200'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                                </svg>
                                <span x-text="editing ? 'Batal Edit' : 'Edit Dokumen'"></span>
                            </button>

                            <form method="POST" action="{{ route('kb.destroy', [$project, $article]) }}"
                                  onsubmit="return confirm('Apakah Anda yakin ingin menghapus dokumen ini?')">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        class="p-2 rounded-xl text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-950/40 transition cursor-pointer"
                                        title="Hapus Dokumen">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @endif
                </div>
            </div>

            {{-- External Resource Banner (If Available) --}}
            @if($hasUrl)
                <div class="p-6 bg-gradient-to-r from-blue-50/70 via-indigo-50/40 to-cyan-50/50 dark:from-slate-800/80 dark:to-slate-800/40 border-b border-slate-100 dark:border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center shrink-0 shadow-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                            </svg>
                        </div>
                        <div>
                            <h4 class="text-sm font-bold text-slate-900 dark:text-white">Tautan Sumber Dokumen ({{ $domain }})</h4>
                            <p class="text-xs text-slate-500 dark:text-slate-400 truncate max-w-md">{{ $article->external_url }}</p>
                        </div>
                    </div>

                    <a href="{{ $article->external_url }}" target="_blank" rel="noopener noreferrer"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-xs font-semibold shadow-xs transition shrink-0">
                        <span>Buka Sumber di {{ $domain }}</span>
                        <span class="text-xs">&nearr;</span>
                    </a>
                </div>
            @endif

            {{-- Edit Form Mode --}}
            <div x-show="editing" x-cloak class="p-6 bg-slate-50/50 dark:bg-slate-900/40 border-b border-slate-100 dark:border-slate-700/80">
                <form method="POST" action="{{ route('kb.update', [$project, $article]) }}" enctype="multipart/form-data" class="space-y-4">
                    @csrf @method('PUT')

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Judul Dokumen</label>
                            <input type="text" name="title" value="{{ $article->title }}" required
                                   class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Kategori Dokumen</label>
                            <select name="category" class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                @foreach(\App\Models\KbArticle::CATEGORIES as $ck => $cv)
                                    <option value="{{ $ck }}" {{ ($article->category ?? 'other') === $ck ? 'selected' : '' }}>
                                        {{ $cv['label'] }} — {{ $cv['name'] }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Deskripsi Singkat</label>
                        <textarea name="description" rows="2"
                                  class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">{{ $article->description }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tautan Eksternal (URL)</label>
                        <input type="url" name="external_url" value="{{ $article->external_url }}"
                               placeholder="https://www.figma.com/file/... atau https://docs.google.com/document/..."
                               class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Isi Konten / Catatan (Markdown)</label>
                        <textarea name="body" rows="12"
                                  class="w-full text-sm font-mono border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 leading-relaxed">{{ $article->body }}</textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 dark:text-slate-300 mb-1.5">Tambah Berkas Lampiran</label>
                        <input type="file" name="files[]" multiple
                               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.jpg,.jpeg,.png,.webp,.zip"
                               class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    </div>

                    <div class="flex items-center justify-between pt-2">
                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                            <input type="checkbox" name="is_pinned" value="1" {{ $article->is_pinned ? 'checked' : '' }}
                                   class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span>Sematkan di atas (Pinned)</span>
                        </label>

                        <div class="flex items-center gap-2">
                            <button type="button" @click="editing = false"
                                    class="px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-200 rounded-xl transition">
                                Batal
                            </button>
                            <button type="submit"
                                    class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-xs">
                                Simpan Perubahan
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Document Body Content Reader --}}
            <div x-show="!editing" class="p-6 sm:p-8">
                @if(trim($article->body))
                    <div class="prose dark:prose-invert max-w-none text-slate-800 dark:text-slate-200 text-sm leading-relaxed whitespace-pre-wrap font-sans">
{{ $article->body }}
                    </div>
                @else
                    <div class="py-8 text-center text-slate-400 text-xs">
                        <p>Dokumen ini tidak memiliki catatan teks langsung.</p>
                        @if($hasUrl)
                            <p class="mt-1">Silakan buka tautan eksternal di atas untuk meninjau dokumen lengkap.</p>
                        @endif
                    </div>
                @endif
            </div>

        </div>

        {{-- Attached Files Section --}}
        @if($article->attachments->isNotEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-6">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                        </svg>
                        <span>Berkas &amp; Lampiran Terkait ({{ $article->attachments->count() }})</span>
                    </h3>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    @foreach($article->attachments as $att)
                        <div class="flex items-center justify-between gap-3 p-3 rounded-xl border border-slate-100 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800/40 hover:bg-slate-50 transition group">
                            <div class="flex items-center gap-3 min-w-0">
                                <div class="w-9 h-9 rounded-lg bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $att->original_name }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $att->formattedSize() }} &bull; Diunggah {{ $att->created_at->diffForHumans() }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                <a href="{{ Storage::url($att->stored_name) }}" target="_blank" download
                                   class="p-2 rounded-lg bg-white dark:bg-slate-700 border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-200 hover:text-blue-600 text-xs font-semibold transition"
                                   title="Unduh Berkas">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                                    </svg>
                                </a>

                                @if(!auth()->user()->hasRole('client'))
                                    <form method="POST" action="{{ route('kb.attachment.destroy', $att) }}"
                                          onsubmit="return confirm('Hapus lampiran ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 transition" title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

    </div>

</div>
@endsection
