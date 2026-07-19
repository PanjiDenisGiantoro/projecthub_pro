@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="py-8"
     x-data="{ tab: new URLSearchParams(location.search).get('tab') || 'overview' }">

    {{-- ============================================================
         PROJECT HEADER
    ============================================================ --}}
    @php
        $statusConfig = [
            'draft'     => ['label' => 'Draft',      'class' => 'bg-gray-100 text-gray-700',   'dot' => 'bg-gray-400'],
            'active'    => ['label' => 'Aktif',      'class' => 'bg-green-100 text-green-700', 'dot' => 'bg-green-500'],
            'on_hold'   => ['label' => 'On Hold',    'class' => 'bg-yellow-100 text-yellow-700','dot' => 'bg-yellow-500'],
            'completed' => ['label' => 'Selesai',    'class' => 'bg-blue-100 text-blue-700',   'dot' => 'bg-blue-500'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-100 text-red-700',     'dot' => 'bg-red-500'],
        ];
        $sc       = $statusConfig[$project->status] ?? ['label' => ucfirst($project->status), 'class' => 'bg-gray-100 text-gray-700', 'dot' => 'bg-gray-400'];
        $progress = $project->progress ?? 0;
        $totalTasks = $project->tasks->count();
        $doneTasks  = $project->tasks->where('status','done')->count();
        $daysLeft   = $project->end_date ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($project->end_date)->startOfDay(), false) : null;

        $tabs = [
            ['key' => 'overview',   'label' => 'Overview',   'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
            ['key' => 'tasks',      'label' => 'Tasks',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>'],
            ['key' => 'milestones', 'label' => 'Milestone',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>'],
            ['key' => 'sprints',    'label' => 'Sprints',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>'],
            ['key' => 'team',       'label' => 'Tim',        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>'],
            ['key' => 'tickets',    'label' => 'Tickets',    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>'],
            ['key' => 'timesheet',  'label' => 'Timesheet',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ['key' => 'kb',         'label' => 'KB',         'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>'],
            ['key' => 'files',      'label' => 'Files',      'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/>'],
            ['key' => 'budget',     'label' => 'Budget',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>'],
            ['key' => 'risks',      'label' => 'Risiko',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.618 5.984A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016zM12 9v2m0 4h.01"/>'],
            ['key' => 'recurring',  'label' => 'Recurring',  'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>'],
            ['key' => 'portal',     'label' => 'Portal',     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>'],
            ['key' => 'chat',       'label' => 'Chat',       'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>'],
        ];
        $coreTabs     = array_slice($tabs, 0, 5);
        $optionalTabs = array_slice($tabs, 5);
    @endphp

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-1.5 text-xs text-gray-400 mb-4">
        <a href="{{ route('projects.index') }}" class="hover:text-indigo-600 transition">Proyek</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-600 truncate max-w-[200px]">{{ $project->name }}</span>
    </div>

    <div class="flex flex-col lg:flex-row gap-6 items-start" x-data="{ showOptional: true }">
        {{-- ============================================================
             SIDEBAR NAVIGATION
        ============================================================ --}}
        <aside class="w-full lg:w-52 shrink-0 lg:sticky lg:top-4">
            <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide px-2.5 mb-2">Inti</p>
            <nav class="space-y-1">
                @foreach($coreTabs as $t)
                <button
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}'
                        ? 'bg-white border-indigo-400 text-indigo-700 shadow-sm'
                        : 'border-transparent text-gray-500 hover:bg-white hover:text-gray-700'"
                    class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg border text-sm font-medium transition-all">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $t['icon'] !!}</svg>
                    {{ $t['label'] }}
                </button>
                @endforeach
            </nav>

            <button @click="showOptional = !showOptional"
                    class="w-full flex items-center gap-1.5 px-2.5 py-2 mt-2 text-xs font-medium text-gray-500 hover:text-gray-700 transition">
                <svg class="w-3.5 h-3.5 transition-transform shrink-0" :class="showOptional ? '' : '-rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                <span x-text="showOptional ? 'Sembunyikan' : 'Tampilkan'"></span>
            </button>

            <nav x-show="showOptional" x-cloak class="space-y-1">
                @foreach($optionalTabs as $t)
                <button
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}'
                        ? 'bg-white border-indigo-400 text-indigo-700 shadow-sm'
                        : 'border-transparent text-gray-500 hover:bg-white hover:text-gray-700'"
                    class="w-full flex items-center gap-2.5 px-2.5 py-2 rounded-lg border text-sm font-medium transition-all">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">{!! $t['icon'] !!}</svg>
                    {{ $t['label'] }}
                </button>
                @endforeach
            </nav>
        </aside>

        {{-- ============================================================
             MAIN CONTENT
        ============================================================ --}}
        <div class="flex-1 min-w-0 w-full">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200 mb-5 overflow-hidden">
        {{-- Top bar --}}
        <div class="px-4 sm:px-6 pt-5 pb-4">
            <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-3">
                <div class="flex-1 min-w-0">
                    {{-- Title + Status --}}
                    <div class="flex items-center gap-2 flex-wrap mb-1">
                        <h1 class="text-lg sm:text-xl font-bold text-gray-900 leading-tight">{{ $project->name }}</h1>
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc['class'] }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sc['dot'] }}"></span>
                            {{ $sc['label'] }}
                        </span>
                    </div>
                    {{-- Meta --}}
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-gray-500">
                        @if($project->client)
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                            {{ $project->client->name }}
                        </span>
                        @endif
                        @if($project->manager)
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            {{ $project->manager->name }}
                        </span>
                        @endif
                        @if($project->start_date || $project->end_date)
                        <span class="flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '?' }}
                            –
                            {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '?' }}
                        </span>
                        @endif
                    </div>
                </div>

                @if(!auth()->user()->hasRole('customer'))
                <a href="{{ route('projects.edit', $project) }}"
                   class="shrink-0 self-start inline-flex items-center gap-1.5 px-3 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition shadow-sm">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    Edit proyek
                </a>
                @endif
            </div>
        </div>

        {{-- Progress --}}
        <div class="px-4 sm:px-6 pb-5">
            <div class="flex items-center justify-between text-xs text-gray-500 mb-1.5">
                <span class="font-medium text-gray-700">Progress Keseluruhan</span>
                <span class="font-semibold {{ $progress >= 100 ? 'text-green-600' : ($progress >= 70 ? 'text-indigo-600' : 'text-gray-600') }}">{{ $progress }}%</span>
            </div>
            <div class="w-full bg-gray-100 rounded-full h-2">
                <div class="h-2 rounded-full transition-all {{ $progress >= 100 ? 'bg-green-500' : 'bg-indigo-500' }}"
                     style="width: {{ min($progress, 100) }}%"></div>
            </div>
        </div>
        </div>

        {{-- Quick stats --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-5">
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3.5">
                <p class="text-xs text-gray-400 mb-1">Total Task</p>
                <p class="text-xl font-bold text-gray-800">{{ $totalTasks }}</p>
            </div>
            <div class="bg-green-50 rounded-xl border border-green-100 shadow-sm px-4 py-3.5">
                <p class="text-xs text-green-600/70 mb-1">Selesai</p>
                <p class="text-xl font-bold text-green-700">{{ $doneTasks }}</p>
            </div>
            <div class="bg-indigo-50 rounded-xl border border-indigo-100 shadow-sm px-4 py-3.5">
                <p class="text-xs text-indigo-600/70 mb-1">Anggota</p>
                <p class="text-xl font-bold text-indigo-700">{{ $project->members->count() }}</p>
            </div>
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm px-4 py-3.5">
                @if($daysLeft === null)
                    <p class="text-xs text-gray-400 mb-1">Deadline</p>
                    <p class="text-xl font-bold text-gray-400">—</p>
                @elseif($daysLeft < 0)
                    <p class="text-xs text-red-400 mb-1">Terlambat</p>
                    <p class="text-xl font-bold text-red-600">{{ abs($daysLeft) }}h</p>
                @elseif($daysLeft === 0)
                    <p class="text-xs text-orange-400 mb-1">Deadline</p>
                    <p class="text-xl font-bold text-orange-600">Hari ini</p>
                @else
                    <p class="text-xs text-gray-400 mb-1">Sisa hari</p>
                    <p class="text-xl font-bold text-blue-700">{{ $daysLeft }}h</p>
                @endif
            </div>
        </div>

    {{-- ============================================================
         TAB: OVERVIEW
    ============================================================ --}}
    <div x-show="tab === 'overview'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Description --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Deskripsi</h2>
                <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                    {{ $project->description ?: 'Tidak ada deskripsi.' }}
                </p>
            </div>
            {{-- Info Sidebar --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Informasi</h2>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Budget</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $project->budget ? 'Rp ' . number_format($project->budget, 0, ',', '.') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Tanggal Mulai</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Tanggal Selesai</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Anggota</p>
                    <p class="text-sm font-medium text-gray-800">{{ $project->members->count() }} orang</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: TASKS
    ============================================================ --}}
    <div x-show="tab === 'tasks'" x-cloak
         x-data="{ showAddTask: false, taskView: 'kanban' }">
        @php
            $tPc  = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
            $tSc  = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
            $tPlb = ['low'=>'border-l-green-400','medium'=>'border-l-yellow-400','high'=>'border-l-orange-400','urgent'=>'border-l-red-500'];
        @endphp

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button @click="taskView='kanban'" :class="taskView==='kanban' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                        class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Kanban
                </button>
                <button @click="taskView='list'" :class="taskView==='list' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                        class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    List
                </button>
            </div>
            @if(!auth()->user()->hasRole('customer'))
            <button @click="showAddTask = !showAddTask"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Task
            </button>
            @endif
        </div>

        {{-- Add Task Form --}}
        @if(!auth()->user()->hasRole('customer'))
        <div x-show="showAddTask" x-cloak class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-4">
            <form action="{{ route('tasks.store', $project) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    <div class="lg:col-span-2">
                        <input type="text" name="title" placeholder="Judul task *" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Assignee —</option>
                            @foreach($developers as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="date" name="start_date" placeholder="Start date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <input type="date" name="due_date" placeholder="Due date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="milestone_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Milestone —</option>
                            @foreach($project->milestones as $ms)
                                <option value="{{ $ms->id }}">{{ $ms->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="number" name="estimated_hours" min="1" placeholder="Estimasi (jam)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">Simpan Task</button>
                    <button type="button" @click="showAddTask = false"
                            class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                </div>
            </form>
        </div>
        @endif

        {{-- LIST VIEW --}}
        <div x-show="taskView==='list'" x-cloak class="space-y-2">
            @forelse($project->tasks as $task)
            @php
                $taskOverdue = $task->isOverdue();
                $taskDays    = $task->daysRemaining();
                $tPl         = $tPlb[$task->priority] ?? 'border-l-gray-300';
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 border-l-4 {{ $tPl }} hover:shadow-sm transition-shadow">
                <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('tasks.show', [$project, $task]) }}"
                               class="font-medium text-gray-800 hover:text-indigo-600 text-sm truncate">{{ $task->title }}</a>
                            <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium {{ $tPc[$task->priority] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($task->priority) }}</span>
                            @if($taskOverdue)
                                <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">Overdue</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                            @if($task->assignee)
                                <span>{{ $task->assignee->name }}</span>
                            @endif
                            @if($task->start_date || $task->due_date)
                                <span class="{{ $taskOverdue ? 'text-red-500' : '' }}">
                                    {{ $task->start_date?->format('d M') ?? '?' }} → {{ $task->due_date?->format('d M Y') ?? '?' }}
                                </span>
                            @endif
                            @if($task->milestone)
                                <span>{{ $task->milestone->title }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($taskDays !== null)
                            @if($taskOverdue)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ abs($taskDays) }}h lalu</span>
                            @elseif($taskDays <= 3)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">{{ $taskDays === 0 ? 'Hari ini' : $taskDays.'h lagi' }}</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ $taskDays }}h lagi</span>
                            @endif
                        @endif
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $tSc[$task->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucwords(str_replace('_',' ',$task->status)) }}
                        </span>
                        <a href="{{ route('tasks.show', [$project, $task]) }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                @if($task->estimated_hours)
                @php $tPct = $task->timeProgressPercent(); @endphp
                <div class="px-4 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $tPct >= 100 ? 'bg-red-400' : 'bg-indigo-400' }}" style="width: {{ $tPct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ round($task->totalMinutes()/60,1) }}j / {{ $task->estimated_hours }}j</span>
                    </div>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-sm text-gray-400">Belum ada task.</div>
            @endforelse
        </div>

        {{-- KANBAN VIEW --}}
        <div x-show="taskView==='kanban'" x-cloak>
            @php
                $kCols = [
                    'todo'        => ['label' => 'To Do',       'dot' => 'bg-gray-400',   'hdr' => 'bg-gray-50 border-gray-200'],
                    'in_progress' => ['label' => 'In Progress', 'dot' => 'bg-blue-500',   'hdr' => 'bg-blue-50 border-blue-100'],
                    'review'      => ['label' => 'Review',      'dot' => 'bg-purple-500', 'hdr' => 'bg-purple-50 border-purple-100'],
                    'done'        => ['label' => 'Done',        'dot' => 'bg-green-500',  'hdr' => 'bg-green-50 border-green-100'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" id="kanban-board">
                @foreach($kCols as $kSt => $kCol)
                @php $kTasks = $project->tasks->where('status', $kSt); @endphp
                <div class="flex flex-col min-h-48">
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl border border-b-0 {{ $kCol['hdr'] }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $kCol['dot'] }}"></span>
                        <span class="text-sm font-semibold text-gray-700">{{ $kCol['label'] }}</span>
                        <span class="ml-auto bg-white text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full border border-gray-200 kb-count" id="kb-count-{{ $kSt }}">{{ $kTasks->count() }}</span>
                    </div>
                    <div class="flex-1 border border-t-0 border-gray-200 rounded-b-xl bg-gray-50/80 p-2 space-y-2 min-h-16 kb-col transition-all"
                         id="kb-col-{{ $kSt }}"
                         data-status="{{ $kSt }}"
                         ondragover="event.preventDefault(); kbDragOver(this)"
                         ondragleave="kbDragLeave(this)"
                         ondrop="kbDrop(event, '{{ $kSt }}')">
                        @forelse($kTasks as $task)
                        @php
                            $kOver = $task->isOverdue();
                            $kPl   = $tPlb[$task->priority] ?? 'border-l-gray-300';
                        @endphp
                        <div class="bg-white rounded-lg border border-gray-200 border-l-4 {{ $kPl }} p-3 hover:shadow-sm transition-shadow cursor-grab active:cursor-grabbing kb-card"
                             draggable="true"
                             data-task-id="{{ $task->id }}"
                             data-task-title="{{ addslashes($task->title) }}"
                             data-status="{{ $task->status }}"
                             ondragstart="kbDragStart(event)"
                             ondragend="kbDragEnd(event)">
                            <a href="{{ route('tasks.show', [$project, $task]) }}"
                               class="text-sm font-medium text-gray-800 hover:text-indigo-600 leading-snug block mb-1.5">{{ $task->title }}</a>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $tPc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                                @if($kOver)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-600">Overdue</span>
                                @endif
                            </div>
                            @if($task->assignee)
                            <div class="flex items-center gap-1.5 mt-2">
                                <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($task->assignee->name,0,1)) }}
                                </div>
                                <span class="text-xs text-gray-500">{{ $task->assignee->name }}</span>
                            </div>
                            @endif
                            @if($task->due_date)
                            <div class="mt-1.5 text-xs {{ $kOver ? 'text-red-500' : 'text-gray-400' }}">
                                {{ $task->due_date->format('d M Y') }}
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="py-4 text-center text-xs text-gray-400 kb-empty">Kosong</div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============================================================
         KANBAN MODAL — Status Update
    ============================================================ --}}
    <div id="kb-modal" class="fixed inset-0 z-50 flex items-center justify-center hidden" aria-modal="true" role="dialog">
        <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="kbModalCancel()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md mx-4 p-6 animate-fade-in">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-semibold text-gray-900">Update Status Task</h3>
                <button onclick="kbModalCancel()" class="text-gray-400 hover:text-gray-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <p class="text-xs text-gray-500 mb-0.5">Task</p>
                <p class="text-sm font-medium text-gray-800" id="kb-modal-title">—</p>
            </div>

            <div class="flex items-center gap-2 mb-4">
                <span class="text-xs text-gray-500">Status:</span>
                <span id="kb-modal-old-status" class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 font-medium"></span>
                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span id="kb-modal-new-status" class="text-xs px-2 py-0.5 rounded-full font-medium"></span>
            </div>

            <div class="mb-5">
                <label class="block text-sm font-medium text-gray-700 mb-1.5">
                    Catatan Penyelesaian
                    <span class="text-gray-400 font-normal">(opsional)</span>
                </label>
                <textarea id="kb-modal-notes" rows="4"
                          placeholder="Deskripsikan apa yang sudah dikerjakan, hambatan, atau catatan penting lainnya..."
                          class="w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
            </div>

            <div class="flex gap-3">
                <button id="kb-modal-submit"
                        onclick="kbModalSubmit()"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
                    Simpan
                </button>
                <button onclick="kbModalCancel()"
                        class="flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-sm font-medium px-4 py-2.5 rounded-lg transition-colors">
                    Batal
                </button>
            </div>
        </div>
    </div>

    {{-- Kanban Toast --}}
    <div id="kb-toast" class="fixed bottom-6 right-6 z-50 hidden">
        <div class="flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white" id="kb-toast-inner">
            <span id="kb-toast-msg"></span>
        </div>
    </div>

    {{-- ============================================================
         TAB: MILESTONES
    ============================================================ --}}
    <div x-show="tab === 'milestones'" x-cloak
         x-data="{ showAddMilestone: false }">

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900">Milestones</h2>
            @if(!auth()->user()->hasRole('customer'))
            <button @click="showAddMilestone = !showAddMilestone"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Milestone
            </button>
            @endif
        </div>

        {{-- Add Milestone Form --}}
        @if(!auth()->user()->hasRole('customer'))
        <div x-show="showAddMilestone" x-cloak class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-5">
            <form action="{{ route('milestones.store', $project) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    <div class="lg:col-span-2">
                        <input type="text" name="title" placeholder="Nama milestone *" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <input type="date" name="start_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <input type="date" name="due_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Assignee (PIC)</label>
                        <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Tidak ditugaskan —</option>
                            @foreach($developers as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <textarea name="description" rows="2" placeholder="Deskripsi (opsional)"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">Simpan Milestone</button>
                    <button type="button" @click="showAddMilestone = false"
                            class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Milestones Grid --}}
        @php
            $mSc = ['pending'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700'];
        @endphp
        @if($project->milestones->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-sm text-gray-400">Belum ada milestone.</div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($project->milestones as $ms)
            @php
                $mPct     = $ms->taskProgressPercent();
                $mDays    = $ms->daysRemaining();
                $mOverdue = $ms->isOverdue();
                $mTotal   = $ms->tasks->count();
                $mDone    = $ms->tasks->where('status','done')->count();
                $mInProg  = $ms->tasks->where('status','in_progress')->count();
                $mTodo    = $ms->tasks->where('status','todo')->count();

                // SVG ring
                $r = 28; $circ = round(2 * M_PI * $r, 2);
                $dash = round($mPct / 100 * $circ, 2);

                // Timeline bar
                if ($ms->start_date && $ms->due_date) {
                    $mTotalDays   = max(1, $ms->start_date->diffInDays($ms->due_date));
                    $mElapsed     = min($mTotalDays, max(0, $ms->start_date->diffInDays(now())));
                    $mTimelinePct = round($mElapsed / $mTotalDays * 100);
                } else {
                    $mTimelinePct = 0;
                }
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow"
                 x-data="{ editing: false }">

                {{-- ── VIEW MODE ── --}}
                <div x-show="!editing">
                    {{-- Header --}}
                    <div class="flex items-start gap-4 mb-4">
                        {{-- SVG progress ring --}}
                        <div class="shrink-0">
                            <svg width="68" height="68" viewBox="0 0 68 68">
                                <circle cx="34" cy="34" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="6"/>
                                <circle cx="34" cy="34" r="{{ $r }}" fill="none"
                                        stroke="{{ $mPct >= 100 ? '#22c55e' : ($mOverdue ? '#ef4444' : '#6366f1') }}"
                                        stroke-width="6"
                                        stroke-dasharray="{{ $dash }} {{ $circ }}"
                                        stroke-dashoffset="{{ round($circ / 4, 2) }}"
                                        stroke-linecap="round"/>
                                <text x="34" y="34" text-anchor="middle" dy="0.35em"
                                      font-size="13" font-weight="700"
                                      fill="{{ $mPct >= 100 ? '#16a34a' : ($mOverdue ? '#dc2626' : '#4f46e5') }}">{{ $mPct }}%</text>
                            </svg>
                        </div>

                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2 mb-1">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-sm font-semibold text-gray-800">{{ $ms->title }}</h3>
                                    <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium {{ $mSc[$ms->status ?? 'pending'] ?? 'bg-gray-100 text-gray-600' }}">
                                        {{ ucwords(str_replace('_', ' ', $ms->status ?? 'pending')) }}
                                    </span>
                                    @if($mOverdue)
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">Overdue</span>
                                    @endif
                                </div>
                                @if(!auth()->user()->hasRole('customer'))
                                <div class="flex gap-1.5 shrink-0">
                                    <button @click="editing = true"
                                            class="p-1.5 text-gray-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Edit">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z"/></svg>
                                    </button>
                                    <form method="POST" action="{{ route('milestones.destroy', [$project, $ms]) }}"
                                          data-confirm-delete="{{ $ms->title }}" data-confirm-label="Hapus Milestone">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5"/></svg>
                                        </button>
                                    </form>
                                </div>
                                @endif
                            </div>

                            {{-- Task breakdown --}}
                            <div class="flex items-center gap-2 text-xs flex-wrap">
                                @if($mTotal > 0)
                                <span class="flex items-center gap-1 text-gray-500">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ $mDone }} selesai
                                </span>
                                <span class="flex items-center gap-1 text-gray-500">
                                    <span class="w-2 h-2 rounded-full bg-blue-500"></span> {{ $mInProg }} progress
                                </span>
                                <span class="flex items-center gap-1 text-gray-500">
                                    <span class="w-2 h-2 rounded-full bg-gray-300"></span> {{ $mTodo }} todo
                                </span>
                                @else
                                <span class="text-gray-400">Belum ada task</span>
                                @endif
                            </div>

                            {{-- Assignee --}}
                            @if($ms->assignee)
                            <div class="flex items-center gap-1.5 mt-1.5">
                                <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                    {{ strtoupper(substr($ms->assignee->name, 0, 1)) }}
                                </div>
                                <span class="text-xs text-gray-500">{{ $ms->assignee->name }}</span>
                            </div>
                            @endif

                            {{-- Days remaining --}}
                            @if($mDays !== null)
                            <div class="mt-1.5">
                                @if($mOverdue)
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ abs($mDays) }} hari terlambat</span>
                                @elseif($mDays === 0)
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">Deadline hari ini!</span>
                                @elseif($mDays <= 7)
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">{{ $mDays }} hari lagi</span>
                                @else
                                    <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ $mDays }} hari lagi</span>
                                @endif
                            </div>
                            @endif
                        </div>
                    </div>

                    {{-- Task mini progress bar --}}
                    @if($mTotal > 0)
                    <div class="mb-3">
                        <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden flex">
                            @if($mDone > 0)
                            <div class="h-full bg-green-500 transition-all" style="width: {{ round($mDone/$mTotal*100) }}%"></div>
                            @endif
                            @if($mInProg > 0)
                            <div class="h-full bg-blue-400 transition-all" style="width: {{ round($mInProg/$mTotal*100) }}%"></div>
                            @endif
                        </div>
                        <div class="text-xs text-gray-400 mt-1">{{ $mDone }}/{{ $mTotal }} task selesai</div>
                    </div>
                    @endif

                    {{-- Timeline --}}
                    @if($ms->start_date && $ms->due_date)
                    <div class="pt-3 border-t border-gray-100">
                        <div class="flex justify-between text-xs text-gray-400 mb-1">
                            <span>{{ $ms->start_date->format('d M') }}</span>
                            <span class="{{ $mOverdue ? 'text-red-500 font-medium' : '' }}">{{ $ms->due_date->format('d M Y') }}</span>
                        </div>
                        <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden relative">
                            <div class="h-2.5 rounded-full {{ $mOverdue ? 'bg-red-400' : 'bg-indigo-400' }} transition-all"
                                 style="width: {{ $mTimelinePct }}%"></div>
                        </div>
                    </div>
                    @elseif($ms->due_date)
                    <div class="pt-3 border-t border-gray-100 text-xs text-gray-400">
                        Due: {{ $ms->due_date->format('d M Y') }}
                    </div>
                    @endif
                </div>

                {{-- ── EDIT MODE ── --}}
                <div x-show="editing" x-cloak>
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-sm font-semibold text-gray-700">Edit Milestone</p>
                        <button @click="editing = false" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <form method="POST" action="{{ route('milestones.update', [$project, $ms]) }}" class="space-y-3">
                        @csrf @method('PUT')
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Nama Milestone <span class="text-red-500">*</span></label>
                            <input type="text" name="title" value="{{ $ms->title }}" required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
                                <input type="date" name="start_date" value="{{ $ms->start_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Due Date</label>
                                <input type="date" name="due_date" value="{{ $ms->due_date?->format('Y-m-d') }}"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Assignee (PIC)</label>
                                <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">— Tidak ditugaskan —</option>
                                    @foreach($developers as $dev)
                                        <option value="{{ $dev->id }}" {{ $ms->assigned_to == $dev->id ? 'selected' : '' }}>{{ $dev->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                                <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="pending"     {{ ($ms->status ?? 'pending') === 'pending'     ? 'selected' : '' }}>Pending</option>
                                    <option value="in_progress" {{ ($ms->status ?? '') === 'in_progress' ? 'selected' : '' }}>In Progress</option>
                                    <option value="completed"   {{ ($ms->status ?? '') === 'completed'   ? 'selected' : '' }}>Completed</option>
                                </select>
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                            <textarea name="description" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none">{{ $ms->description }}</textarea>
                        </div>
                        <div class="flex gap-2 pt-1">
                            <button type="submit" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-medium px-4 py-2 rounded-lg transition">Simpan</button>
                            <button type="button" @click="editing = false"
                                    class="flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 text-xs font-medium px-4 py-2 rounded-lg transition">Batal</button>
                        </div>
                    </form>
                </div>

            </div>
            @endforeach
        </div>
        @endif
    </div>

    {{-- ============================================================
         TAB: TICKETS
    ============================================================ --}}
    <div x-show="tab === 'tickets'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Tiket Terkini</h2>
                <div class="flex gap-2">
                    <a href="{{ route('tickets.create', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat Tiket
                    </a>
                    <a href="{{ route('tickets.index', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
                        Lihat Semua
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Judul</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Reporter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recentTickets as $ticket)
                            @php
                                $tPriorityClass = [
                                    'low'    => 'bg-gray-100 text-gray-600',
                                    'medium' => 'bg-blue-100 text-blue-700',
                                    'high'   => 'bg-orange-100 text-orange-700',
                                    'urgent' => 'bg-red-100 text-red-700',
                                ][$ticket->priority ?? 'medium'] ?? 'bg-gray-100 text-gray-600';
                                $tStatusClass = [
                                    'open'        => 'bg-blue-100 text-blue-700',
                                    'in_progress' => 'bg-yellow-100 text-yellow-700',
                                    'resolved'    => 'bg-green-100 text-green-700',
                                    'closed'      => 'bg-gray-100 text-gray-600',
                                ][$ticket->status ?? 'open'] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="hover:bg-gray-50 transition cursor-pointer"
                                onclick="window.location='{{ route('tickets.show', $ticket) }}'">
                                <td class="px-6 py-3">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                       class="text-sm font-medium text-gray-900 hover:text-indigo-600 transition-colors">
                                        {{ $ticket->title }}
                                    </a>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $ticket->reporter->name ?? '-' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tPriorityClass }}">
                                        {{ ucfirst($ticket->priority ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tStatusClass }}">
                                        {{ ucwords(str_replace('_', ' ', $ticket->status ?? '-')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <a href="{{ route('tickets.show', $ticket) }}"
                                       class="text-gray-400 hover:text-indigo-600 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                                    Belum ada tiket.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: TIM
    ============================================================ --}}
    <div x-show="tab === 'team'" x-cloak
         x-data="{ showAddMember: false }">

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- ── Member List ── --}}
            <div class="lg:col-span-2 bg-white rounded-xl border border-gray-200 overflow-hidden">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Anggota Tim</h2>
                        <p class="text-xs text-gray-400 mt-0.5">{{ $project->members->count() }} anggota aktif</p>
                    </div>
                    @if(!auth()->user()->hasRole('customer'))
                    <button @click="showAddMember = !showAddMember"
                            :class="showAddMember ? 'bg-gray-100 text-gray-700' : 'bg-indigo-600 text-white hover:bg-indigo-700'"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        <span x-text="showAddMember ? 'Tutup Form' : 'Tambah Anggota'"></span>
                    </button>
                    @endif
                </div>

                <ul class="divide-y divide-gray-100">
                    @forelse($project->members as $member)
                    <li class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition group">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-sm uppercase shrink-0">
                                {{ strtoupper(substr($member->user->name ?? '?', 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $member->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">{{ $member->user->email ?? '' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($member->role)
                            <span class="hidden sm:inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-50 text-indigo-700 border border-indigo-100">
                                {{ $member->role }}
                            </span>
                            @endif
                            @if($member->max_hours_per_day)
                            <span class="hidden sm:flex items-center gap-1 text-xs text-gray-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ $member->max_hours_per_day }}j/hari
                            </span>
                            @endif
                            @if(!auth()->user()->hasRole('customer'))
                            <form method="POST" action="{{ route('projects.members.remove', [$project, $member->user]) }}"
                                  class="opacity-0 group-hover:opacity-100 transition-opacity">
                                @csrf @method('DELETE')
                                <button type="submit"
                                        onclick="return confirm('Hapus {{ $member->user->name }} dari tim?')"
                                        class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </li>
                    @empty
                    <li class="px-6 py-12 text-center">
                        <svg class="w-10 h-10 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p class="text-sm text-gray-400">Belum ada anggota tim.</p>
                        <p class="text-xs text-gray-300 mt-1">Klik "Tambah Anggota" untuk mulai.</p>
                    </li>
                    @endforelse
                </ul>
            </div>

            {{-- ── Add Member Form (sidebar) ── --}}
            @if(!auth()->user()->hasRole('customer'))
            <div x-show="showAddMember" x-cloak
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-1"
                 x-transition:enter-end="opacity-100 translate-y-0">
                <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-4">
                    <div class="flex items-center justify-between mb-5">
                        <div>
                            <h3 class="text-sm font-semibold text-gray-900">Tambah Anggota</h3>
                            <p class="text-xs text-gray-400 mt-0.5">Tambahkan developer ke tim proyek ini</p>
                        </div>
                        <button @click="showAddMember = false" class="text-gray-400 hover:text-gray-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <form action="{{ route('projects.members.add', $project) }}" method="POST" class="space-y-4">
                        @csrf

                        {{-- Anggota --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                Anggota <span class="text-red-500">*</span>
                            </label>
                            <select name="user_id" required
                                    class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition
                                           {{ $errors->has('user_id') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                                <option value="">— Pilih Developer —</option>
                                @foreach($developers as $dev)
                                    <option value="{{ $dev->id }}" {{ old('user_id') == $dev->id ? 'selected' : '' }}>
                                        {{ $dev->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                            <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Role / Posisi --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                Posisi dalam Tim
                                <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <select name="role"
                                    class="w-full px-3 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition
                                           {{ $errors->has('role') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                                <option value="">— Pilih Posisi —</option>
                                @foreach($structuralLevels as $level)
                                    <option value="{{ $level->name }}" {{ old('role') === $level->name ? 'selected' : '' }}>
                                        {{ $level->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                            <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                            @enderror
                        </div>

                        {{-- Maks jam/hari --}}
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                Kapasitas Kerja
                                <span class="text-gray-400 font-normal">(opsional)</span>
                            </label>
                            <div class="relative">
                                <input type="number" name="max_hours_per_day"
                                       value="{{ old('max_hours_per_day') }}"
                                       min="1" max="24"
                                       placeholder="8"
                                       class="w-full pl-3 pr-16 py-2.5 border rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition
                                              {{ $errors->has('max_hours_per_day') ? 'border-red-400 bg-red-50' : 'border-gray-300' }}">
                                <span class="absolute right-3 top-1/2 -translate-y-1/2 text-xs text-gray-400 pointer-events-none">jam/hari</span>
                            </div>
                            @error('max_hours_per_day')
                            <p class="mt-1 text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-400">Rentang: 1 – 24 jam per hari</p>
                        </div>

                        <button type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2.5 rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                            Tambahkan ke Tim
                        </button>
                    </form>
                </div>
            </div>
            @endif

        </div>
    </div>

    {{-- ============================================================
         TAB: KB (Knowledge Base)
    ============================================================ --}}
    <div x-show="tab === 'kb'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Knowledge Base</h2>
            <a href="{{ route('kb.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Buka Knowledge Base &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: TIMESHEET
    ============================================================ --}}
    <div x-show="tab === 'timesheet'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Timesheet</h2>
            <div class="flex flex-wrap gap-3 mb-4">
                <a href="{{ route('projects.timesheet', $project) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Buka Timesheet &rarr;
                </a>
                <a href="{{ route('export.timesheet.excel', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('export.timesheet.pdf', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export PDF
                </a>
                <a href="{{ route('export.report.pdf', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">
                    Laporan PDF
                </a>
                <a href="{{ route('export.report.excel', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">
                    Laporan Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: SPRINTS
    ============================================================ --}}
    <div x-show="tab === 'sprints'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Sprint Planning</h2>
            <a href="{{ route('sprints.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Kelola Sprints &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: FILES
    ============================================================ --}}
    <div x-show="tab === 'files'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">File Manager</h2>
            <a href="{{ route('project.files.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Buka File Manager &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: BUDGET
    ============================================================ --}}
    <div x-show="tab === 'budget'" x-cloak>
        @php
            $budgetUsed = $project->totalExpenses();
            $budgetPct  = $project->budgetUsedPercent();
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Budget Tracking</h2>
                <a href="{{ route('budget.index', $project) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Kelola →</a>
            </div>
            @if($project->budget)
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Total Budget</p>
                    <p class="font-semibold text-gray-800 text-sm">Rp {{ number_format($project->budget, 0, ',', '.') }}</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Terpakai</p>
                    <p class="font-semibold text-red-600 text-sm">Rp {{ number_format($budgetUsed, 0, ',', '.') }}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Sisa</p>
                    <p class="font-semibold text-green-600 text-sm">Rp {{ number_format($project->budget - $budgetUsed, 0, ',', '.') }}</p>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Penggunaan anggaran</span>
                    <span class="{{ $budgetPct >= 90 ? 'text-red-600 font-bold' : '' }}">{{ $budgetPct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $budgetPct >= 90 ? 'bg-red-500' : ($budgetPct >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                         style="width:{{ min(100,$budgetPct) }}%"></div>
                </div>
            </div>
            @else
            <p class="text-sm text-gray-400">Budget belum diset. <a href="{{ route('budget.index', $project) }}" class="text-indigo-600 hover:underline">Kelola anggaran →</a></p>
            @endif
        </div>
    </div>

    {{-- ============================================================
         TAB: RISKS
    ============================================================ --}}
    <div x-show="tab === 'risks'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Risk Register</h2>
                <a href="{{ route('risks.index', $project) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Lihat Semua →</a>
            </div>
            @php
                $openRisks = $project->risks()->where('status','open')->count();
                $highRisks = $project->risks()->where('status','open')->whereRaw('probability * impact >= 8')->count();
            @endphp
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ $project->risks()->count() }}</p>
                    <p class="text-xs text-gray-400">Total Risiko</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $openRisks }}</p>
                    <p class="text-xs text-gray-400">Terbuka</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $highRisks }}</p>
                    <p class="text-xs text-gray-400">High/Critical</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: RECURRING
    ============================================================ --}}
    <div x-show="tab === 'recurring'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Recurring Tasks</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $project->recurringTasks()->where('is_active', true)->count() }} recurring task aktif.</p>
            <a href="{{ route('recurring.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Kelola Recurring Tasks &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: PORTAL
    ============================================================ --}}
    <div x-show="tab === 'portal'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Client Portal</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $project->portalTokens()->count() }} portal link dibuat. Bagikan link khusus kepada klien untuk melihat progress proyek.</p>
            <a href="{{ route('portal.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Kelola Portal Link &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: CHAT
    ============================================================ --}}
    <div x-show="tab === 'chat'" x-cloak>
        @include('projects.partials._chat')
    </div>

        </div>
        {{-- /MAIN CONTENT --}}
    </div>
    {{-- /sidebar + main flex wrapper --}}
</div>
@endsection

@push('scripts')
<script>
(function () {
    const STATUS_LABELS = {
        todo:        'To Do',
        in_progress: 'In Progress',
        review:      'Review',
        done:        'Done',
    };
    const STATUS_BADGE = {
        todo:        'bg-gray-100 text-gray-600',
        in_progress: 'bg-blue-100 text-blue-700',
        review:      'bg-purple-100 text-purple-700',
        done:        'bg-green-100 text-green-700',
    };

    // --- drag state ---
    let _card      = null;
    let _originCol = null;
    let _newStatus = null;

    window.kbDragStart = function (e) {
        _card      = e.currentTarget;
        _originCol = _card.closest('.kb-col');
        e.dataTransfer.effectAllowed = 'move';
        setTimeout(() => _card.classList.add('opacity-40', 'scale-95'), 0);
    };

    window.kbDragEnd = function (e) {
        _card && _card.classList.remove('opacity-40', 'scale-95');
        document.querySelectorAll('.kb-col').forEach(c => _clearDropStyle(c));
    };

    window.kbDragOver = function (col) {
        col.classList.add('ring-2', 'ring-indigo-400', 'bg-indigo-50');
    };

    window.kbDragLeave = function (col) {
        _clearDropStyle(col);
    };

    window.kbDrop = function (e, newStatus) {
        e.preventDefault();
        const col = document.getElementById('kb-col-' + newStatus);
        _clearDropStyle(col);

        if (!_card || _card.dataset.status === newStatus) return;

        _newStatus = newStatus;

        // Optimistic move
        _removeEmpty(col);
        col.appendChild(_card);
        _card.dataset.status = newStatus;
        _updateCounts();

        // Show modal
        const oldStatus = _originCol ? _originCol.dataset.status : '';
        document.getElementById('kb-modal-title').textContent = _card.dataset.taskTitle;

        const oldBadge = document.getElementById('kb-modal-old-status');
        oldBadge.textContent = STATUS_LABELS[oldStatus] || oldStatus;
        oldBadge.className   = 'text-xs px-2 py-0.5 rounded-full font-medium ' + (STATUS_BADGE[oldStatus] || 'bg-gray-100 text-gray-600');

        const newBadge = document.getElementById('kb-modal-new-status');
        newBadge.textContent = STATUS_LABELS[newStatus] || newStatus;
        newBadge.className   = 'text-xs px-2 py-0.5 rounded-full font-medium ' + (STATUS_BADGE[newStatus] || 'bg-gray-100 text-gray-600');

        document.getElementById('kb-modal-notes').value = '';
        document.getElementById('kb-modal').classList.remove('hidden');
        setTimeout(() => document.getElementById('kb-modal-notes').focus(), 100);
    };

    window.kbModalSubmit = function () {
        if (!_card) return;

        const btn   = document.getElementById('kb-modal-submit');
        const notes = document.getElementById('kb-modal-notes').value.trim();
        const taskId = _card.dataset.taskId;
        const projectId = '{{ $project->id }}';
        const url   = `/projects/${projectId}/tasks/${taskId}/move`;

        btn.disabled    = true;
        btn.textContent = 'Menyimpan...';

        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ status: _newStatus, notes: notes }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) {
                _closeModal(false);
                _toast('Status task berhasil diperbarui.', 'success');
            } else {
                _closeModal(true);
                _toast('Gagal memperbarui status.', 'error');
            }
        })
        .catch(() => {
            _closeModal(true);
            _toast('Terjadi kesalahan jaringan.', 'error');
        })
        .finally(() => {
            btn.disabled    = false;
            btn.textContent = 'Simpan';
        });
    };

    window.kbModalCancel = function () {
        _closeModal(true);
    };

    // --- helpers ---
    function _closeModal(revert) {
        document.getElementById('kb-modal').classList.add('hidden');
        if (revert && _card && _originCol) {
            _removeEmpty(_originCol);
            _originCol.appendChild(_card);
            _card.dataset.status = _originCol.dataset.status;
            _updateCounts();
        }
        _card = _originCol = _newStatus = null;
    }

    function _clearDropStyle(col) {
        col.classList.remove('ring-2', 'ring-indigo-400', 'bg-indigo-50');
    }

    function _removeEmpty(col) {
        col.querySelectorAll('.kb-empty').forEach(el => el.remove());
    }

    function _updateCounts() {
        document.querySelectorAll('.kb-col').forEach(col => {
            const status = col.dataset.status;
            const count  = col.querySelectorAll('.kb-card').length;
            const badge  = document.getElementById('kb-count-' + status);
            if (badge) badge.textContent = count;

            if (count === 0 && !col.querySelector('.kb-empty')) {
                const ph = document.createElement('div');
                ph.className   = 'py-4 text-center text-xs text-gray-400 kb-empty';
                ph.textContent = 'Kosong';
                col.appendChild(ph);
            }
        });
    }

    function _toast(msg, type) {
        const toast = document.getElementById('kb-toast');
        const inner = document.getElementById('kb-toast-inner');
        const msgEl = document.getElementById('kb-toast-msg');
        msgEl.textContent = msg;
        inner.className   = 'flex items-center gap-3 px-4 py-3 rounded-xl shadow-lg text-sm font-medium text-white '
                          + (type === 'success' ? 'bg-green-600' : 'bg-red-600');
        toast.classList.remove('hidden');
        clearTimeout(toast._timer);
        toast._timer = setTimeout(() => toast.classList.add('hidden'), 3000);
    }
})();
</script>
@endpush
