@extends('layouts.app')

@section('title', 'Team Workload & Capacity')
@section('page-title', 'Team Workload')

@section('content')
@php
    $maxCapacity = 8;
    $totalMembers = $developers->count();
    $totalActiveTasks = $developers->sum(fn($d) => $d->assignedTasks->count());
    $overloadedCount = $developers->filter(fn($d) => $d->assignedTasks->count() > $maxCapacity)->count();
    $balancedCount = $developers->filter(fn($d) => $d->assignedTasks->count() >= 4 && $d->assignedTasks->count() <= $maxCapacity)->count();
    $availableCount = $developers->filter(fn($d) => $d->assignedTasks->count() < 4)->count();
@endphp

<div class="space-y-6 pt-5 pb-8" x-data="{ viewMode: 'grid', filterStatus: 'all', searchQuery: '{{ request('search', '') }}' }">

    {{-- ── Hero Banner ──────────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    Resource & Capacity Planning
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Team Workload
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                    <span>Pantau kapasitas dan beban kerja aktif (Todo & In Progress) setiap anggota tim.</span>
                </p>
            </div>

            {{-- Top Controls (Search & View Toggle) --}}
            <div class="flex items-center gap-3 shrink-0 flex-wrap">
                <form method="GET" action="{{ route('workload') }}" class="relative">
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Cari anggota tim..."
                           class="w-56 sm:w-64 pl-9 pr-3.5 py-2 text-xs sm:text-sm bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl text-slate-800 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 shadow-xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </form>

                <div class="flex items-center bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl p-1 shadow-xs">
                    <button type="button" @click="viewMode = 'grid'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5"
                            :class="viewMode === 'grid' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                        </svg>
                        Grid
                    </button>
                    <button type="button" @click="viewMode = 'table'"
                            class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all flex items-center gap-1.5"
                            :class="viewMode === 'table' ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                        </svg>
                        Table
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── 4 KPI Metric Cards ──────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4.5">
        {{-- Card 1: Total Anggota --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Anggota</span>
                <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $totalMembers }}</p>
                <span class="text-xs font-semibold text-slate-400">orang</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Rata-rata beban</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">{{ $totalMembers > 0 ? round($totalActiveTasks / $totalMembers, 1) : 0 }} task/orang</span>
            </div>
        </div>

        {{-- Card 2: Total Task Aktif --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Task Aktif</span>
                <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black text-slate-900 dark:text-white tabular-nums">{{ $totalActiveTasks }}</p>
                <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">in progress & todo</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Standar kuota</span>
                <span class="font-bold text-slate-800 dark:text-slate-200">Max {{ $maxCapacity }} task/orang</span>
            </div>
        </div>

        {{-- Card 3: Overloaded --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Overloaded</span>
                <div class="w-9 h-9 rounded-xl bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black {{ $overloadedCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tabular-nums">{{ $overloadedCount }}</p>
                <span class="text-xs font-semibold text-rose-500">&gt; {{ $maxCapacity }} task</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span>Perlu re-distribusi</span>
                <span class="font-bold {{ $overloadedCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                    {{ $overloadedCount > 0 ? 'Perhatian!' : 'Aman' }}
                </span>
            </div>
        </div>

        {{-- Card 4: Balanced & Available --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between mb-3">
                <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Optimal / Tersedia</span>
                <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <div class="flex items-baseline gap-2">
                <p class="text-3xl font-black text-emerald-600 dark:text-emerald-400 tabular-nums">{{ $balancedCount + $availableCount }}</p>
                <span class="text-xs font-semibold text-slate-400">siap ambil task</span>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium text-slate-500 dark:text-slate-400">
                <span class="text-emerald-600 dark:text-emerald-400 font-medium">{{ $balancedCount }} optimal</span>
                <span class="text-blue-600 dark:text-blue-400 font-medium">{{ $availableCount }} low-load</span>
            </div>
        </div>
    </div>

    {{-- ── Filter Tabs ─────────────────────────────────────────────────────── --}}
    <div class="flex items-center gap-2 border-b border-slate-200 dark:border-gray-700 pb-2 overflow-x-auto text-xs sm:text-sm">
        <button type="button" @click="filterStatus = 'all'"
                class="px-3.5 py-1.5 rounded-lg font-semibold transition-colors shrink-0"
                :class="filterStatus === 'all' ? 'bg-slate-900 text-white dark:bg-white dark:text-slate-900' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-gray-800'">
            Semua ({{ $totalMembers }})
        </button>
        <button type="button" @click="filterStatus = 'overloaded'"
                class="px-3.5 py-1.5 rounded-lg font-semibold transition-colors shrink-0 flex items-center gap-1.5"
                :class="filterStatus === 'overloaded' ? 'bg-rose-600 text-white' : 'text-rose-600 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/30'">
            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
            Overloaded ({{ $overloadedCount }})
        </button>
        <button type="button" @click="filterStatus = 'balanced'"
                class="px-3.5 py-1.5 rounded-lg font-semibold transition-colors shrink-0 flex items-center gap-1.5"
                :class="filterStatus === 'balanced' ? 'bg-emerald-600 text-white' : 'text-emerald-600 dark:text-emerald-400 hover:bg-emerald-50 dark:hover:bg-emerald-950/30'">
            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
            Optimal ({{ $balancedCount }})
        </button>
        <button type="button" @click="filterStatus = 'available'"
                class="px-3.5 py-1.5 rounded-lg font-semibold transition-colors shrink-0 flex items-center gap-1.5"
                :class="filterStatus === 'available' ? 'bg-blue-600 text-white' : 'text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-950/30'">
            <span class="w-2 h-2 rounded-full bg-blue-500"></span>
            Available ({{ $availableCount }})
        </button>
    </div>

    {{-- ── Grid View ───────────────────────────────────────────────────────── --}}
    <div x-show="viewMode === 'grid'" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @forelse($developers as $dev)
        @php
            $taskCount = $dev->assignedTasks->count();
            $pct = $maxCapacity > 0 ? min(100, round($taskCount / $maxCapacity * 100)) : 0;
            $statusKey = $taskCount > $maxCapacity ? 'overloaded' : ($taskCount >= 4 ? 'balanced' : 'available');

            if ($taskCount > $maxCapacity) {
                $statusBadge = 'bg-rose-50 text-rose-700 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900';
                $statusDot = 'bg-rose-500';
                $statusLabel = 'Over Capacity';
                $barGradient = 'from-rose-500 to-red-600';
            } elseif ($taskCount >= 6) {
                $statusBadge = 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900';
                $statusDot = 'bg-amber-500';
                $statusLabel = 'Heavy Load';
                $barGradient = 'from-amber-400 to-orange-500';
            } elseif ($taskCount >= 4) {
                $statusBadge = 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900';
                $statusDot = 'bg-emerald-500';
                $statusLabel = 'Optimal';
                $barGradient = 'from-emerald-400 to-teal-500';
            } else {
                $statusBadge = 'bg-blue-50 text-blue-700 border-blue-200 dark:bg-blue-950/40 dark:text-blue-300 dark:border-blue-900';
                $statusDot = 'bg-blue-500';
                $statusLabel = 'Available';
                $barGradient = 'from-blue-400 to-indigo-500';
            }
        @endphp

        <div x-show="filterStatus === 'all' || filterStatus === '{{ $statusKey }}'"
             class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between"
             x-data="{ expanded: false }">

            <div>
                {{-- Member Header --}}
                <div class="flex items-start justify-between gap-3 mb-4">
                    <div class="flex items-center gap-3 min-w-0">
                        @if($dev->avatar)
                            <img src="{{ Storage::url($dev->avatar) }}" alt="{{ $dev->name }}"
                                 class="w-11 h-11 rounded-xl object-cover ring-2 ring-slate-100 dark:ring-gray-700 shrink-0">
                        @else
                            <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shadow-xs shrink-0"
                                 style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                {{ strtoupper(substr($dev->name, 0, 2)) }}
                            </div>
                        @endif
                        <div class="min-w-0">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-white truncate">{{ $dev->name }}</h3>
                            <p class="text-xs text-slate-400 dark:text-slate-500 truncate">{{ $dev->email }}</p>
                            <div class="mt-1 flex items-center gap-1.5 flex-wrap">
                                @foreach($dev->roles->take(2) as $role)
                                    <span class="inline-block px-2 py-0.5 rounded-md text-[10px] font-semibold bg-slate-100 dark:bg-gray-700 text-slate-600 dark:text-slate-300">
                                        {{ \App\Support\RoleLabel::for($role->name) }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Capacity Badge --}}
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold border shrink-0 {{ $statusBadge }}">
                        <span class="w-1.5 h-1.5 rounded-full {{ $statusDot }}"></span>
                        {{ $statusLabel }}
                    </span>
                </div>

                {{-- Capacity Metric & Progress Bar --}}
                <div class="bg-slate-50 dark:bg-gray-800/70 rounded-xl p-3 mb-4">
                    <div class="flex items-center justify-between text-xs font-medium mb-1.5">
                        <span class="text-slate-500 dark:text-slate-400">Kapasitas Kerja</span>
                        <div class="text-right">
                            <span class="font-bold text-slate-900 dark:text-white text-sm tabular-nums">{{ $taskCount }}</span>
                            <span class="text-slate-400 text-xs">/ {{ $maxCapacity }} task ({{ $pct }}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-slate-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="bg-gradient-to-r {{ $barGradient }} h-2 rounded-full transition-all duration-300"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>

                {{-- Task List --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between text-xs font-semibold text-slate-500 dark:text-slate-400 px-0.5">
                        <span>Task Aktif ({{ $taskCount }})</span>
                        @if($taskCount > 3)
                        <button type="button" @click="expanded = !expanded"
                                class="text-blue-600 dark:text-blue-400 hover:underline text-[11px] font-medium">
                            <span x-text="expanded ? 'Tampilkan lebih sedikit' : 'Lihat semua (' + {{ $taskCount }} + ')'"></span>
                        </button>
                        @endif
                    </div>

                    @if($dev->assignedTasks->isNotEmpty())
                        @php
                            $displayedTasks = $dev->assignedTasks;
                        @endphp
                        <div class="space-y-1.5">
                            @foreach($displayedTasks as $index => $task)
                            @php
                                $isTodo = $task->status === 'todo';
                                $prioColors = [
                                    'urgent' => 'text-rose-600 bg-rose-50 border-rose-200 dark:bg-rose-950/40 dark:text-rose-300 dark:border-rose-900',
                                    'high'   => 'text-orange-600 bg-orange-50 border-orange-200 dark:bg-orange-950/40 dark:text-orange-300 dark:border-orange-900',
                                    'medium' => 'text-amber-600 bg-amber-50 border-amber-200 dark:bg-amber-950/40 dark:text-amber-300 dark:border-amber-900',
                                    'low'    => 'text-emerald-600 bg-emerald-50 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:border-emerald-900',
                                ];
                            @endphp
                            <div class="group flex items-center justify-between gap-2 p-2.5 rounded-xl border border-slate-100 dark:border-gray-800 bg-white dark:bg-gray-800 hover:border-blue-200 dark:hover:border-blue-800 hover:shadow-xs transition-all text-xs"
                                 @if($index >= 3) x-show="expanded" x-cloak @endif>
                                <div class="flex items-center gap-2 min-w-0">
                                    <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $isTodo ? 'bg-slate-400' : 'bg-blue-500 animate-pulse' }}"></span>
                                    <a href="{{ $task->project ? route('tasks.show', [$task->project, $task]) : '#' }}"
                                       class="font-medium text-slate-800 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 truncate">
                                        {{ $task->title }}
                                    </a>
                                </div>
                                <div class="flex items-center gap-1.5 shrink-0">
                                    @if($task->project)
                                    <span class="hidden sm:inline-block px-1.5 py-0.5 rounded text-[10px] bg-slate-100 dark:bg-gray-700 text-slate-500 dark:text-slate-400 max-w-[90px] truncate">
                                        {{ $task->project->name }}
                                    </span>
                                    @endif
                                    <span class="inline-block px-1.5 py-0.5 rounded text-[10px] font-semibold border {{ $prioColors[$task->priority] ?? 'bg-slate-50 text-slate-600' }}">
                                        {{ ucfirst($task->priority) }}
                                    </span>
                                    @if($task->due_date)
                                    <span class="text-[11px] font-medium {{ $task->due_date->isPast() ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-400 dark:text-slate-500' }}">
                                        {{ $task->due_date->format('d M') }}
                                    </span>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="p-4 rounded-xl border border-dashed border-slate-200 dark:border-gray-700 text-center text-xs text-slate-400 dark:text-slate-500">
                            Tidak ada task aktif saat ini. Siap menerima penugasan baru.
                        </div>
                    @endif
                </div>
            </div>

            {{-- Footer info --}}
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 flex items-center justify-between text-[11px] text-slate-400">
                <span>{{ $dev->assignedTasks->where('status', 'in_progress')->count() }} In Progress</span>
                <span>{{ $dev->assignedTasks->where('status', 'todo')->count() }} To Do</span>
            </div>
        </div>
        @empty
        <div class="col-span-full py-16 text-center">
            <div class="w-16 h-16 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </div>
            <h3 class="text-base font-bold text-slate-900 dark:text-white">Tidak ada anggota tim ditemukan</h3>
            <p class="text-xs text-slate-400 mt-1">Coba sesuaikan kata kunci pencarian Anda.</p>
        </div>
        @endforelse
    </div>

    {{-- ── Table View (Executive Table) ────────────────────────────────────── --}}
    <div x-show="viewMode === 'table'" x-cloak class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 dark:bg-gray-800/80 border-b border-slate-100 dark:border-gray-700 text-[11px] font-bold text-slate-400 dark:text-slate-400 uppercase tracking-wider">
                        <th class="px-5 py-3.5">Anggota Tim</th>
                        <th class="px-5 py-3.5">Beban & Kapasitas</th>
                        <th class="px-5 py-3.5 text-center">Task Aktif</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Task Mendesak / Proyek</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-gray-800 text-xs text-slate-700 dark:text-slate-200">
                    @forelse($developers as $dev)
                    @php
                        $taskCount = $dev->assignedTasks->count();
                        $pct = $maxCapacity > 0 ? min(100, round($taskCount / $maxCapacity * 100)) : 0;
                        $statusKey = $taskCount > $maxCapacity ? 'overloaded' : ($taskCount >= 4 ? 'balanced' : 'available');
                    @endphp
                    <tr x-show="filterStatus === 'all' || filterStatus === '{{ $statusKey }}'" class="hover:bg-slate-50/80 dark:hover:bg-gray-800/50 transition-colors">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-3">
                                @if($dev->avatar)
                                    <img src="{{ Storage::url($dev->avatar) }}" alt="{{ $dev->name }}" class="w-9 h-9 rounded-xl object-cover ring-2 ring-slate-100 dark:ring-gray-700">
                                @else
                                    <div class="w-9 h-9 rounded-xl flex items-center justify-center text-white font-bold text-xs"
                                         style="background: linear-gradient(135deg, #3b82f6, #6366f1)">
                                        {{ strtoupper(substr($dev->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-900 dark:text-white">{{ $dev->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $dev->email }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 w-64">
                            <div class="flex items-center justify-between text-[11px] font-medium mb-1">
                                <span class="text-slate-500">{{ $taskCount }}/{{ $maxCapacity }} task</span>
                                <span class="font-bold {{ $taskCount > $maxCapacity ? 'text-rose-600' : 'text-slate-700 dark:text-slate-300' }}">{{ $pct }}%</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full {{ $taskCount > $maxCapacity ? 'bg-rose-500' : ($taskCount >= 6 ? 'bg-amber-500' : ($taskCount >= 4 ? 'bg-emerald-500' : 'bg-blue-500')) }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-bold tabular-nums {{ $taskCount > $maxCapacity ? 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300' : 'bg-slate-100 text-slate-800 dark:bg-gray-700 dark:text-slate-200' }}">
                                {{ $taskCount }} task
                            </span>
                        </td>
                        <td class="px-5 py-3.5">
                            @if($taskCount > $maxCapacity)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-rose-50 text-rose-700 border border-rose-200 dark:bg-rose-950/40 dark:text-rose-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> Overloaded
                                </span>
                            @elseif($taskCount >= 4)
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Optimal
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200 dark:bg-blue-950/40 dark:text-blue-300">
                                    <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span> Available
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-3.5">
                            @if($dev->assignedTasks->isNotEmpty())
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @foreach($dev->assignedTasks->take(3) as $task)
                                    <a href="{{ $task->project ? route('tasks.show', [$task->project, $task]) : '#' }}"
                                       class="inline-block px-2 py-0.5 rounded-md text-[11px] bg-slate-100 dark:bg-gray-800 text-slate-700 dark:text-slate-300 hover:bg-blue-50 hover:text-blue-700 transition max-w-[140px] truncate">
                                        {{ $task->title }}
                                    </a>
                                    @endforeach
                                    @if($dev->assignedTasks->count() > 3)
                                    <span class="text-[11px] text-slate-400">+{{ $dev->assignedTasks->count() - 3 }} lagi</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-slate-400 text-xs">—</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-5 py-8 text-center text-slate-400">Tidak ada anggota tim terdaftar.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
