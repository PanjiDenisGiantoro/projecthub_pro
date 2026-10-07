@extends('layouts.app')
@section('title', 'Knowledge Base & Docs — ' . $project->name)
@section('page-title', 'Knowledge Base')

@section('main-class', 'flex-1 px-4 sm:px-6 pt-0 pb-8 overflow-y-auto overflow-x-hidden w-full max-w-full min-w-0')

@section('content')
<div class="pt-4 pb-6 flex flex-col lg:flex-row gap-6 items-start w-full max-w-full min-w-0"
     x-data="kbManagerComponent()"
     style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">

    {{-- Project Sidebar --}}
    @include('projects.partials.sidebar', ['project' => $project, 'tab' => 'kb'])

    {{-- Main Content Area --}}
    <div class="flex-1 min-w-0 w-full space-y-6">

        {{-- ── 1. Breadcrumbs & Header ──────────────────────────────────────── --}}
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-1">
            <div>
                <nav class="flex items-center gap-1.5 text-xs text-slate-500 dark:text-slate-400 mb-1.5">
                    <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600 transition flex items-center gap-1 font-medium">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                        <span>{{ $project->name }}</span>
                    </a>
                    <span>/</span>
                    <span class="text-slate-800 dark:text-slate-200 font-semibold">Knowledge Base &amp; Docs</span>
                </nav>
                <h1 class="text-2xl font-extrabold text-slate-900 dark:text-white tracking-tight flex items-center gap-2.5">
                    <span>Knowledge Base &amp; Documentation</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-800">
                        {{ $categoryCounts['all'] ?? 0 }} Dokumen
                    </span>
                </h1>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                    Pusat dokumentasi tim &amp; onboarding: BRD, PRD, FSD, MOM hasil meeting, arsitektur sistem, dan panduan teknis.
                </p>
            </div>

            <div class="flex items-center gap-2.5 shrink-0">
                @if(!auth()->user()->hasRole('client'))
                    <button type="button" @click="openCreateModal()"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Tambah Dokumen</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- ── 2. Category KPI & Filter Pills ──────────────────────────────── --}}
        @php
            $activeCategory = request('category', 'all');
            $categories = [
                'all'          => ['label' => 'Semua Dokumen', 'code' => 'ALL', 'dot' => 'bg-slate-500'],
                'brd'          => ['label' => 'BRD', 'code' => 'BRD', 'desc' => 'Business Req', 'dot' => 'bg-purple-500'],
                'prd'          => ['label' => 'PRD', 'code' => 'PRD', 'desc' => 'Product Req', 'dot' => 'bg-blue-500'],
                'fsd'          => ['label' => 'FSD', 'code' => 'FSD', 'desc' => 'Func. Spec', 'dot' => 'bg-indigo-500'],
                'mom'          => ['label' => 'MOM', 'code' => 'MOM', 'desc' => 'Minutes of Meeting', 'dot' => 'bg-emerald-500'],
                'architecture' => ['label' => 'Tech Spec', 'code' => 'ARCH', 'desc' => 'Architecture', 'dot' => 'bg-amber-500'],
                'guide'        => ['label' => 'Onboarding', 'code' => 'GUIDE', 'desc' => 'Guides & SOP', 'dot' => 'bg-cyan-500'],
            ];
        @endphp

        <div class="flex items-center gap-2 overflow-x-auto pb-1 scrollbar-thin">
            @foreach($categories as $catKey => $catInfo)
                @php
                    $isActive = ($activeCategory === $catKey);
                    $count = $categoryCounts[$catKey] ?? 0;
                @endphp
                <a href="{{ route('kb.index', array_merge(['project' => $project], $catKey === 'all' ? request()->except('category', 'page') : array_merge(request()->except('page'), ['category' => $catKey]))) }}"
                   class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl text-xs font-semibold transition shrink-0 cursor-pointer border {{ $isActive ? 'bg-blue-600 text-white border-blue-600 shadow-xs' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200/90 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-700/60' }}">
                    <span class="w-2 h-2 rounded-full {{ $isActive ? 'bg-white' : $catInfo['dot'] }}"></span>
                    <span>{{ $catInfo['label'] }}</span>
                    <span class="px-1.5 py-0.2 rounded-md text-[10px] {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-400' }}">
                        {{ $count }}
                    </span>
                </a>
            @endforeach
        </div>

        {{-- ── 3. Search & Filter Bar ──────────────────────────────────────── --}}
        <div class="bg-white dark:bg-slate-800 rounded-2xl p-4 flex flex-col sm:flex-row items-center justify-between gap-3">
            <form method="GET" action="{{ route('kb.index', $project) }}" class="flex-1 w-full flex items-center gap-2">
                @if(request('category'))
                    <input type="hidden" name="category" value="{{ request('category') }}">
                @endif
                <div class="relative flex-1">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z"/>
                    </svg>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari berdasarkan judul, ringkasan, atau isi dokumen..."
                           class="w-full pl-9.5 pr-4 py-2 text-xs sm:text-sm bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 dark:text-white transition">
                </div>
                <button type="submit"
                        class="px-4 py-2 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs sm:text-sm font-semibold rounded-xl transition cursor-pointer">
                    Cari
                </button>
                @if(request('search') || request('category'))
                    <a href="{{ route('kb.index', $project) }}"
                       class="px-3 py-2 text-xs text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition">
                        Reset
                    </a>
                @endif
            </form>

            <div class="text-xs text-slate-500 dark:text-slate-400 shrink-0 self-end sm:self-center">
                Menampilkan <strong class="text-slate-800 dark:text-slate-200 font-bold">{{ $articles->count() }}</strong> dokumen
            </div>
        </div>

        {{-- ── 4. Documents Grid ───────────────────────────────────────────── --}}
        @if($articles->isEmpty())
            <div class="bg-white dark:bg-slate-800 rounded-2xl p-12 text-center">
                <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 mx-auto flex items-center justify-center mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                    </svg>
                </div>
                <h3 class="text-base font-bold text-slate-900 dark:text-white mb-1">Belum Ada Dokumen di Kategori Ini</h3>
                <p class="text-xs text-slate-500 dark:text-slate-400 max-w-sm mx-auto mb-5">
                    @if(request('search') || request('category'))
                        Tidak ada dokumen yang cocok dengan filter pencarian. Silakan sesuaikan atau reset pencarian.
                    @else
                        Mulai dokumentasikan project requirement (BRD, PRD, FSD), hasil diskusi (MOM), atau arsitektur agar tim baru cepat paham.
                    @endif
                </p>
                @if(!auth()->user()->hasRole('client'))
                    <button type="button" @click="openCreateModal('{{ request('category') !== 'all' ? request('category', 'brd') : 'brd' }}')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span>Buat Dokumen Baru</span>
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                @foreach($articles as $article)
                    @php
                        $meta = $article->categoryMeta();
                        $hasUrl = !empty($article->external_url);
                        $domain = $article->domainName();
                        $attachmentsCount = $article->attachments->count();
                        $subCount = $article->children->count();

                        $articlePayload = json_encode([
                            'id'           => $article->id,
                            'title'        => $article->title,
                            'category'     => $article->category ?? 'other',
                            'description'  => $article->description ?? '',
                            'external_url' => $article->external_url ?? '',
                            'body'         => $article->body ?? '',
                            'parent_id'    => $article->parent_id,
                            'is_pinned'    => (bool) $article->is_pinned,
                            'tags'         => $article->tags ?? [],
                        ]);
                    @endphp

                    <div class="bg-white dark:bg-slate-800 rounded-2xl p-5 hover:bg-slate-50/80 dark:hover:bg-slate-750 transition-all flex flex-col justify-between group relative">

                        <div>
                            {{-- Card Header: Badges & Pinned & Type --}}
                            <div class="flex items-center justify-between gap-2 mb-3">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-[11px] font-bold border {{ $meta['bg'] }} {{ $meta['text'] }} {{ $meta['border'] }}">
                                        <span class="w-1.5 h-1.5 rounded-full {{ $meta['dot'] }}"></span>
                                        {{ $meta['label'] }}
                                    </span>

                                    @if($article->is_pinned)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border border-amber-200 dark:border-amber-800" title="Disematkan di atas">
                                            <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M10 2a1 1 0 011 1v4.243l2.828 2.829a1 1 0 01.293.707V13a1 1 0 01-1 1h-2v4a1 1 0 11-2 0v-4H7a1 1 0 01-1-1v-2.221a1 1 0 01.293-.707L9.121 7.243V3a1 1 0 011-1z"/>
                                            </svg>
                                            Pinned
                                        </span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1 text-slate-400 text-xs">
                                    <span class="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300">
                                        v{{ $article->version }}
                                    </span>
                                </div>
                            </div>

                            {{-- Title --}}
                            <a href="{{ route('kb.show', [$project, $article]) }}"
                               class="text-base font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition leading-snug block line-clamp-2 mb-2">
                                {{ $article->title }}
                            </a>

                            {{-- Description / Excerpt --}}
                            <p class="text-xs text-slate-500 dark:text-slate-400 leading-relaxed line-clamp-3 mb-4">
                                {{ $article->description ?: (Str::limit(strip_tags($article->body), 120) ?: 'Belum ada ringkasan teks.') }}
                            </p>

                            {{-- External Resource Banner (if link present) --}}
                            @if($hasUrl)
                                <div class="mb-3">
                                    <a href="{{ $article->external_url }}" target="_blank" rel="noopener noreferrer"
                                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-blue-50/80 hover:bg-blue-100/90 dark:bg-blue-950/40 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-xl text-xs font-semibold transition border border-blue-200/80 dark:border-blue-800/80 w-full group/link">
                                        <svg class="w-3.5 h-3.5 text-blue-600 shrink-0 group-hover/link:translate-x-0.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                        </svg>
                                        <span class="truncate flex-1">Buka di {{ $domain }}</span>
                                        <span class="text-[10px] text-blue-400 font-mono">&nearr;</span>
                                    </a>
                                </div>
                            @endif

                            {{-- Attachments Chips (if files uploaded) --}}
                            @if($attachmentsCount > 0)
                                <div class="mb-3 space-y-1.5">
                                    <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">LAMPIRAN FILE ({{ $attachmentsCount }})</span>
                                    <div class="flex flex-wrap gap-1.5">
                                        @foreach($article->attachments->take(2) as $att)
                                            <a href="{{ Storage::url($att->stored_name) }}" target="_blank" download
                                               class="inline-flex items-center gap-1.5 px-2 py-1 bg-slate-50 dark:bg-slate-700/50 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 rounded-lg text-[11px] font-medium border border-slate-200/80 dark:border-slate-700 transition max-w-[200px]"
                                               title="{{ $att->original_name }}">
                                                <svg class="w-3 h-3 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span class="truncate">{{ $att->original_name }}</span>
                                                <span class="text-[9px] text-slate-400 shrink-0 font-mono">{{ $att->formattedSize() }}</span>
                                            </a>
                                        @endforeach
                                        @if($attachmentsCount > 2)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] text-slate-400 bg-slate-100 dark:bg-slate-700">
                                                +{{ $attachmentsCount - 2 }} lagi
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Card Footer: Author, Date, Actions --}}
                        <div class="pt-3 border-t border-slate-100 dark:border-slate-700/60 mt-3 flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2 min-w-0">
                                @if($article->author?->avatar)
                                    <img src="{{ Storage::url($article->author->avatar) }}" alt="{{ $article->author->name }}"
                                         class="w-6 h-6 rounded-full object-cover shrink-0">
                                @else
                                    <div class="w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                                         style="background-color: {{ $article->author ? $article->author->avatarColor() : '#3b82f6' }}">
                                        {{ $article->author ? $article->author->initials() : 'NA' }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <p class="text-xs font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $article->author->name ?? 'Tim' }}</p>
                                    <p class="text-[10px] text-slate-400">{{ $article->updated_at->diffForHumans() }}</p>
                                </div>
                            </div>

                            <div class="flex items-center gap-1.5 shrink-0">
                                @if(!auth()->user()->hasRole('client'))
                                    <button type="button" @click="openEditModal({{ $articlePayload }})"
                                            title="Edit Dokumen"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L6.832 19.82a4.5 4.5 0 01-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 011.13-1.897L16.863 4.487zm0 0L19.5 7.125"/>
                                        </svg>
                                    </button>
                                @endif

                                <a href="{{ route('kb.show', [$project, $article]) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-blue-50 hover:bg-blue-100 text-blue-600 dark:bg-blue-950/50 dark:hover:bg-blue-900/50 dark:text-blue-400 rounded-lg text-xs font-semibold transition cursor-pointer">
                                    <span>Baca</span>
                                    <span class="text-xs">&rarr;</span>
                                </a>
                            </div>
                        </div>

                    </div>
                @endforeach
            </div>
        @endif

    </div>

    {{-- ── 5. Create / Edit Knowledge Base Document Modal ───────────────── --}}
    <template x-teleport="body">
        <div x-show="isModalOpen" x-cloak class="relative z-[100]">
            {{-- Backdrop --}}
            <div x-show="isModalOpen"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0"
                 class="fixed inset-0 z-[100] w-screen h-screen bg-slate-900/60 backdrop-blur-xs"></div>

            {{-- Dialog Container --}}
            <div x-show="isModalOpen" @click.self="closeModal()"
                 class="fixed inset-0 z-[101] w-screen h-screen overflow-y-auto p-3 sm:p-6 flex items-center justify-center">

                <div x-show="isModalOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-150"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     class="relative w-full max-w-2xl max-h-[92vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200/90 dark:border-slate-800 overflow-hidden text-slate-800 dark:text-slate-100">

                    {{-- Header --}}
                    <div class="px-6 py-4.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-white dark:bg-slate-900 shrink-0">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                                 :class="modalMode === 'create' ? 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400' : 'bg-indigo-50 text-indigo-600 dark:bg-indigo-950/50 dark:text-indigo-400'">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 006 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 016 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 016-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0018 18a8.967 8.967 0 00-6 2.292m0-14.25v14.25"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight"
                                    x-text="modalMode === 'create' ? 'Tambah Dokumen Knowledge Base' : 'Edit Dokumen Knowledge Base'"></h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    Simpan requirement BRD/PRD/FSD, MOM, dokumen panduan, atau tautan eksternal tim.
                                </p>
                            </div>
                        </div>

                        <button type="button" @click="closeModal()"
                                class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-slate-800 transition cursor-pointer">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Form --}}
                    <form :action="formAction" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col min-h-0">
                        @csrf
                        <template x-if="modalMode === 'edit'">
                            <input type="hidden" name="_method" value="PUT">
                        </template>

                        <div class="flex-1 overflow-y-auto p-6 space-y-4.5">

                            {{-- Title --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Judul Dokumen <span class="text-red-500">*</span>
                                </label>
                                <input type="text" name="title" x-model="formData.title" required maxlength="255"
                                       placeholder="Misal: BRD Sistem Manajemen Inventori v1.0 / MOM Kickoff Meeting"
                                       class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                            </div>

                            {{-- Category Custom Dropdown --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Kategori Dokumen <span class="text-red-500">*</span>
                                </label>
                                <input type="hidden" name="category" :value="formData.category">

                                <div class="relative" x-data="{ openCat: false }">
                                    <button type="button" @click="openCat = !openCat"
                                            class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-gray-300 dark:border-slate-700 rounded-xl text-xs sm:text-sm font-medium hover:border-gray-400 transition cursor-pointer">
                                        <div class="flex items-center gap-2.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="getCategoryDot(formData.category)"></span>
                                            <span class="font-semibold text-slate-800 dark:text-slate-200" x-text="getCategoryLabel(formData.category)"></span>
                                            <span class="text-xs text-slate-400 font-normal" x-text="getCategorySub(formData.category)"></span>
                                        </div>
                                        <svg class="w-4 h-4 text-slate-400 transition-transform duration-150" :class="openCat ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                        </svg>
                                    </button>

                                    <div x-show="openCat" @click.outside="openCat = false" x-cloak
                                         class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-gray-100 dark:border-slate-700 py-1.5 z-50 max-h-60 overflow-y-auto space-y-1">
                                        <template x-for="cat in availableCategories" :key="cat.id">
                                            <button type="button" @click="formData.category = cat.id; openCat = false"
                                                    class="w-full text-left px-3.5 py-2 text-xs flex items-center justify-between rounded-lg hover:bg-slate-50 dark:hover:bg-slate-700/60 transition cursor-pointer"
                                                    :class="formData.category === cat.id ? 'bg-blue-50/70 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-bold' : 'text-slate-700 dark:text-slate-300'">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="w-2 h-2 rounded-full shrink-0" :class="cat.dot"></span>
                                                    <div>
                                                        <span class="font-semibold" x-text="cat.label"></span>
                                                        <span class="text-[11px] text-slate-400 ml-1.5" x-text="'(' + cat.desc + ')'"></span>
                                                    </div>
                                                </div>
                                                <svg x-show="formData.category === cat.id" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Description --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">
                                    Deskripsi / Ringkasan Dokumen
                                </label>
                                <textarea name="description" x-model="formData.description" rows="2"
                                          placeholder="Ringkasan poin utama dokumen ini agar tim baru cepat memahaminya..."
                                          class="w-full text-sm border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none transition"></textarea>
                            </div>

                            {{-- External Resource URL (Figma, Google Docs, Notion, Sheets) --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                    <span>Tautan Eksternal (Link Figma, Google Docs, Notion, Miro, dll.)</span>
                                    <span class="text-[11px] text-slate-400 font-normal">Opsional</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                        </svg>
                                    </div>
                                    <input type="url" name="external_url" x-model="formData.external_url"
                                           placeholder="https://www.figma.com/file/... atau https://docs.google.com/document/..."
                                           class="w-full text-sm pl-10 border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition">
                                </div>
                            </div>

                            {{-- Attachments Upload --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                    <span>Lampiran Berkas (PDF, DOCX, XLSX, PPTX, ZIP, Gambar)</span>
                                    <span class="text-[11px] text-slate-400 font-normal">Maks. 20MB per berkas</span>
                                </label>

                                <div class="border-2 border-dashed border-gray-300 dark:border-slate-700 rounded-xl p-4 bg-slate-50/50 dark:bg-slate-800/40 hover:border-blue-400 transition">
                                    <input type="file" name="files[]" multiple
                                           accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.md,.jpg,.jpeg,.png,.webp,.zip"
                                           @change="handleFileChange($event)"
                                           class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">

                                    <div x-show="selectedFiles.length > 0" class="mt-3 space-y-1.5">
                                        <template x-for="(file, i) in selectedFiles" :key="i">
                                            <div class="flex items-center gap-2 text-xs bg-white dark:bg-slate-800 rounded-lg p-2 border border-slate-200 dark:border-slate-700">
                                                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                <span class="flex-1 truncate font-medium text-slate-700 dark:text-slate-300" x-text="file.name"></span>
                                                <input type="text" :name="'file_descriptions['+i+']'" placeholder="Catatan berkas (opsional)"
                                                       class="border border-gray-200 dark:border-slate-700 rounded px-2 py-0.5 text-xs w-44 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- In-app Notes / Markdown Body --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5 flex items-center justify-between">
                                    <span>Catatan Dokumen / Detail Konten</span>
                                    <span class="text-[11px] text-slate-400 font-normal">Mendukung format Markdown atau plain text</span>
                                </label>
                                <textarea name="body" x-model="formData.body" rows="6"
                                          placeholder="Tuliskan spesifikasi detail, agenda meeting, atau petunjuk teknis di sini..."
                                          class="w-full text-sm font-mono border border-gray-300 dark:border-slate-700 dark:bg-slate-800 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500 transition leading-relaxed"></textarea>
                            </div>

                            {{-- Options: Pin to Top --}}
                            <div class="pt-1 flex items-center gap-2">
                                <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-700 dark:text-slate-300">
                                    <input type="checkbox" name="is_pinned" value="1" x-model="formData.is_pinned"
                                           class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span>Sematkan dokumen ini di bagian atas (Pinned document)</span>
                                </label>
                            </div>

                        </div>

                        {{-- Footer Actions --}}
                        <div class="px-6 py-4 bg-gray-50/80 dark:bg-slate-800/50 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3 shrink-0">
                            <button type="button" @click="closeModal()"
                                    class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-200/60 dark:hover:bg-slate-700 rounded-xl transition cursor-pointer">
                                Batal
                            </button>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-sm cursor-pointer">
                                <span x-text="modalMode === 'create' ? 'Simpan Dokumen' : 'Perbarui Dokumen'"></span>
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </template>

</div>

<script>
    function kbManagerComponent() {
        return {
            isModalOpen: false,
            modalMode: 'create',
            formAction: '{{ route('kb.store', $project) }}',
            formData: {
                id: null,
                title: '',
                category: 'brd',
                description: '',
                external_url: '',
                body: '',
                parent_id: '',
                is_pinned: false,
            },
            selectedFiles: [],
            availableCategories: [
                { id: 'brd',          label: 'BRD', desc: 'Business Requirement Document', dot: 'bg-purple-500' },
                { id: 'prd',          label: 'PRD', desc: 'Product Requirement Document', dot: 'bg-blue-500' },
                { id: 'fsd',          label: 'FSD', desc: 'Functional Specification Document', dot: 'bg-indigo-500' },
                { id: 'mom',          label: 'MOM', desc: 'Minutes of Meeting', dot: 'bg-emerald-500' },
                { id: 'architecture', label: 'Tech Spec', desc: 'System Architecture & Tech Spec', dot: 'bg-amber-500' },
                { id: 'guide',        label: 'Onboarding', desc: 'Guidelines & SOP Onboarding', dot: 'bg-cyan-500' },
                { id: 'other',        label: 'General', desc: 'Dokumen / Catatan Lainnya', dot: 'bg-slate-400' },
            ],

            openCreateModal(defaultCategory = 'brd') {
                this.modalMode = 'create';
                this.formAction = '{{ route('kb.store', $project) }}';
                this.formData = {
                    id: null,
                    title: '',
                    category: defaultCategory,
                    description: '',
                    external_url: '',
                    body: '',
                    parent_id: '',
                    is_pinned: false,
                };
                this.selectedFiles = [];
                this.isModalOpen = true;
            },

            openEditModal(doc) {
                this.modalMode = 'edit';
                this.formAction = '/projects/{{ $project->slug ?: $project->id }}/kb/' + doc.id;
                this.formData = {
                    id: doc.id,
                    title: doc.title || '',
                    category: doc.category || 'other',
                    description: doc.description || '',
                    external_url: doc.external_url || '',
                    body: doc.body || '',
                    parent_id: doc.parent_id || '',
                    is_pinned: !!doc.is_pinned,
                };
                this.selectedFiles = [];
                this.isModalOpen = true;
            },

            closeModal() {
                this.isModalOpen = false;
                this.selectedFiles = [];
            },

            handleFileChange(e) {
                this.selectedFiles = Array.from(e.target.files);
            },

            getCategoryLabel(id) {
                const found = this.availableCategories.find(c => c.id === id);
                return found ? found.label : 'Pilih Kategori';
            },

            getCategorySub(id) {
                const found = this.availableCategories.find(c => c.id === id);
                return found ? ('— ' + found.desc) : '';
            },

            getCategoryDot(id) {
                const found = this.availableCategories.find(c => c.id === id);
                return found ? found.dot : 'bg-slate-400';
            }
        };
    }
</script>
@endsection
