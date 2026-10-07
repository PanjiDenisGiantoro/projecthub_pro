@extends('layouts.app')

@section('title', 'Workspace Analytics')
@section('page-title', 'Workspace Analytics')

@section('content')
@php
    $doneRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
    $milestoneRate = $totalMilestones > 0 ? round($completedMilestones / $totalMilestones * 100) : 0;
@endphp

<div class="space-y-6 pt-5 pb-8">

    {{-- ── Hero Greeting & Period Filter ──────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    Real-time Intelligence & Telemetry
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Workspace Analytics
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                    <span>Metrik produktivitas tim, penyelesaian tugas, dan status SLA.</span>
                    <span>&bull;</span>
                    <span class="text-xs text-slate-400">Periode aktif: {{ $period }} hari terakhir</span>
                </p>
            </div>

            {{-- Period Filter Pills --}}
            <div class="flex items-center gap-2 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl p-1 shadow-xs shrink-0">
                @foreach([7 => '7 Hari', 14 => '14 Hari', 30 => '30 Hari', 90 => '3 Bulan'] as $val => $label)
                <a href="{{ route('analytics.index', ['period' => $val]) }}"
                   class="px-3 py-1.5 rounded-lg text-xs font-semibold transition-all {{ $period == $val ? 'bg-blue-600 text-white shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white hover:bg-slate-50 dark:hover:bg-gray-700' }}">
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ── 4 Primary Metric Cards ─────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4.5">

        {{-- Tasks Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tingkat Selesai Task</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $doneRate }}%</p>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">{{ $doneTasks }} selesai</span>
                </div>
                {{-- Progress bar --}}
                <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-2 mt-3 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-emerald-500 h-2 rounded-full transition-all duration-500" style="width: {{ $doneRate }}%"></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="text-slate-500 dark:text-slate-400">Total: {{ $totalTasks }} task</span>
                <span class="text-blue-600 dark:text-blue-400 font-semibold">{{ $totalTasks - $doneTasks }} aktif</span>
            </div>
        </div>

        {{-- Projects Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Portofolio Proyek</span>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $activeProjects }}</p>
                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">aktif berjalan</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ $projectsByStatus->get('completed', 0) }} selesai
                </span>
                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>{{ $totalProjects }} total
                </span>
            </div>
        </div>

        {{-- Overdue & Health Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Keterlambatan (Overdue)</span>
                    <div class="w-9 h-9 rounded-xl {{ $overdueCount > 0 ? 'bg-rose-50 dark:bg-rose-900/30 text-rose-600 dark:text-rose-400' : 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black {{ $overdueCount > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-900 dark:text-white' }} tabular-nums">{{ $overdueCount }}</p>
                    <span class="text-xs font-semibold text-slate-400">task lewat deadline</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="text-slate-500 dark:text-slate-400">Status Tenggat</span>
                <span class="font-bold {{ $overdueCount > 0 ? 'text-rose-600' : 'text-emerald-600' }}">
                    {{ $overdueCount > 0 ? 'Perlu Eskalasi' : 'Tepat Waktu' }}
                </span>
            </div>
        </div>

        {{-- Ticket & SLA Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Tiket Bug & SLA</span>
                    <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $openTickets }}</p>
                    <span class="text-xs font-semibold text-amber-600 dark:text-amber-400">tiket terbuka</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="flex items-center gap-1.5 {{ $slaBreached > 0 ? 'text-rose-600 dark:text-rose-400 font-bold' : 'text-slate-500' }}">
                    <span class="w-2 h-2 rounded-full {{ $slaBreached > 0 ? 'bg-rose-500' : 'bg-slate-400' }}"></span>{{ $slaBreached }} SLA Breach
                </span>
                <span class="text-blue-600 dark:text-blue-400 font-medium">{{ $newRequests }} request baru</span>
            </div>
        </div>

    </div>

    {{-- ── Main Charts Row (Weekly Bar + Status Doughnut) ──────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Weekly Task Output (2 Cols) --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Output Mingguan: Task Dibuat vs Selesai</h3>
                    <p class="text-xs text-slate-400 dark:text-slate-500">Performa penyelesaian sprint dalam 8 minggu terakhir</p>
                </div>
                <div class="flex items-center gap-3 text-xs font-medium">
                    <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="w-2.5 h-2.5 rounded-sm bg-blue-500"></span> Dibuat
                    </span>
                    <span class="flex items-center gap-1.5 text-slate-600 dark:text-slate-300">
                        <span class="w-2.5 h-2.5 rounded-sm bg-emerald-500"></span> Selesai
                    </span>
                </div>
            </div>
            <div class="h-64">
                <canvas id="weeklyChart"></canvas>
            </div>
        </div>

        {{-- Task by Status Doughnut (1 Col) --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Distribusi Status Task</h3>
                    <span class="text-[11px] font-semibold text-slate-400">{{ $totalTasks }} Total</span>
                </div>
                <p class="text-xs text-slate-400 dark:text-slate-500 mb-4">Komposisi backlog saat ini</p>

                <div class="relative flex justify-center py-2">
                    <div class="w-40 h-40">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800 space-y-1.5">
                @php
                $statusColors = [
                    'todo'        => ['dot' => 'bg-slate-400',   'label' => 'To Do'],
                    'in_progress' => ['dot' => 'bg-blue-500',    'label' => 'In Progress'],
                    'review'      => ['dot' => 'bg-amber-400',   'label' => 'Review'],
                    'done'        => ['dot' => 'bg-emerald-500', 'label' => 'Done'],
                    'blocked'     => ['dot' => 'bg-rose-500',    'label' => 'Blocked'],
                ];
                @endphp
                @foreach($tasksByStatus as $st => $cnt)
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $statusColors[$st]['dot'] ?? 'bg-slate-400' }}"></span>
                        <span class="text-slate-600 dark:text-slate-300">{{ $statusColors[$st]['label'] ?? ucfirst($st) }}</span>
                    </div>
                    <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ $cnt }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Second Row (3 Columns: Top Devs, Project Progress, Tickets & Milestones) ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Top Developer Log Hours --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Top Developer (Jam Kerja)</h3>
                    <p class="text-xs text-slate-400">Total waktu tercatat {{ $period }} hari terakhir</p>
                </div>
                <div class="w-7 h-7 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>

            @php $maxMinutes = $topDevs->first()?->total_minutes ?: 1; @endphp
            <div class="space-y-3">
                @forelse($topDevs as $idx => $dev)
                @php
                    $hours = round($dev->total_minutes / 60, 1);
                    $pct = min(100, round(($dev->total_minutes / $maxMinutes) * 100));
                    $ranks = [0 => '🥇', 1 => '🥈', 2 => '🥉'];
                @endphp
                <div class="group">
                    <div class="flex items-center justify-between text-xs mb-1">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-xs">{{ $ranks[$idx] ?? ($idx + 1) . '.' }}</span>
                            <span class="font-semibold text-slate-800 dark:text-slate-200 truncate">{{ $dev->user?->name ?? 'User' }}</span>
                        </div>
                        <span class="font-bold text-slate-900 dark:text-white tabular-nums shrink-0">{{ $hours }} jam</span>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                        <div class="bg-gradient-to-r from-blue-500 to-indigo-600 h-1.5 rounded-full transition-all duration-300"
                             style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400 py-6 text-center">Belum ada data time log tercatat.</p>
                @endforelse
            </div>
        </div>

        {{-- Active Projects Progress --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Progress Proyek Aktif</h3>
                    <p class="text-xs text-slate-400">Rasio penyelesaian task proyek aktif</p>
                </div>
                <div class="w-7 h-7 rounded-lg bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                </div>
            </div>

            <div class="space-y-3.5">
                @forelse($projectProgress as $proj)
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-semibold text-slate-800 dark:text-slate-200 truncate max-w-[65%]">{{ $proj['name'] }}</span>
                        <div class="text-right shrink-0">
                            <span class="font-bold text-slate-900 dark:text-white tabular-nums">{{ $proj['percent'] }}%</span>
                            <span class="text-slate-400 text-[11px]">({{ $proj['done'] }}/{{ $proj['total'] }})</span>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                        <div class="h-2 rounded-full transition-all duration-300 {{ $proj['percent'] >= 100 ? 'bg-emerald-500' : ($proj['percent'] >= 50 ? 'bg-blue-600' : 'bg-amber-500') }}"
                             style="width: {{ $proj['percent'] }}%"></div>
                    </div>
                </div>
                @empty
                <p class="text-xs text-slate-400 py-6 text-center">Tidak ada proyek aktif saat ini.</p>
                @endforelse
            </div>
        </div>

        {{-- Ticket Priority & Milestone Summary --}}
        <div class="space-y-5">
            {{-- Ticket Priority Card --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tiket per Prioritas</h3>
                    <span class="text-xs text-slate-400 font-semibold">{{ $ticketsByPriority->sum() }} Terbuka</span>
                </div>
                @php
                    $prioColors = [
                        'critical' => ['bg' => 'bg-rose-500',    'label' => 'Critical'],
                        'high'     => ['bg' => 'bg-orange-500',  'label' => 'High'],
                        'medium'   => ['bg' => 'bg-amber-400',   'label' => 'Medium'],
                        'low'      => ['bg' => 'bg-blue-400',    'label' => 'Low'],
                    ];
                    $prioSum = $ticketsByPriority->sum() ?: 1;
                @endphp
                <div class="space-y-2">
                    @foreach(['critical', 'high', 'medium', 'low'] as $p)
                    @php $cnt = $ticketsByPriority->get($p, 0); $pct = round($cnt / $prioSum * 100); @endphp
                    <div class="flex items-center gap-2 text-xs">
                        <span class="w-16 text-slate-600 dark:text-slate-300 shrink-0">{{ $prioColors[$p]['label'] }}</span>
                        <div class="flex-1 bg-slate-100 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                            <div class="h-2 rounded-full {{ $prioColors[$p]['bg'] }}" style="width: {{ $pct }}%"></div>
                        </div>
                        <span class="w-8 text-right font-bold text-slate-700 dark:text-slate-200 tabular-nums shrink-0">{{ $cnt }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Milestone Progress Card --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Milestone Keseluruhan</h3>
                    <span class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $milestoneRate }}%</span>
                </div>
                <div class="flex items-baseline gap-2 mb-2">
                    <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $completedMilestones }}</span>
                    <span class="text-xs text-slate-400">/ {{ $totalMilestones }} milestone selesai</span>
                </div>
                <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                    <div class="bg-emerald-500 h-2.5 rounded-full transition-all duration-300" style="width: {{ $milestoneRate }}%"></div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Third Row (Daily Trend Line & Project Pipeline) ──────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">

        {{-- Daily Trend Line --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Tren Task Dibuat per Hari</h3>
                    <p class="text-xs text-slate-400">Volume pembuatan task baru dalam 14 hari terakhir</p>
                </div>
                <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-50 dark:bg-indigo-900/40 text-indigo-700 dark:text-indigo-300">
                    14 Hari
                </span>
            </div>
            <div class="h-56">
                <canvas id="dailyTrendChart"></canvas>
            </div>
        </div>

        {{-- Project Pipeline by Status --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 p-5 shadow-xs">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 dark:text-white">Distribusi Proyek per Status</h3>
                    <p class="text-xs text-slate-400">Pipeline fase pengerjaan seluruh proyek</p>
                </div>
                <span class="text-xs text-slate-400 font-bold">{{ $totalProjects }} Proyek</span>
            </div>
            @php
                $projStatusColors = [
                    'planning'  => ['bg' => 'bg-slate-400',   'label' => 'Planning'],
                    'active'    => ['bg' => 'bg-blue-600',    'label' => 'Active'],
                    'on_hold'   => ['bg' => 'bg-amber-400',   'label' => 'On Hold'],
                    'completed' => ['bg' => 'bg-emerald-500', 'label' => 'Completed'],
                    'cancelled' => ['bg' => 'bg-rose-500',    'label' => 'Cancelled'],
                ];
                $projTotal = $projectsByStatus->sum() ?: 1;
            @endphp
            <div class="space-y-3 pt-2">
                @foreach($projectsByStatus as $status => $count)
                @php $pct = round($count / $projTotal * 100); @endphp
                <div>
                    <div class="flex items-center justify-between text-xs mb-1">
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $projStatusColors[$status]['label'] ?? ucfirst($status) }}</span>
                        <div class="text-right">
                            <span class="font-bold text-slate-900 dark:text-white tabular-nums">{{ $count }}</span>
                            <span class="text-slate-400 text-[11px]">({{ $pct }}%)</span>
                        </div>
                    </div>
                    <div class="w-full bg-slate-100 dark:bg-gray-700 rounded-full h-2.5 overflow-hidden">
                        <div class="h-2.5 rounded-full {{ $projStatusColors[$status]['bg'] ?? 'bg-blue-500' }}" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const weeklyData    = @json($weeks);
    const statusData    = @json($tasksByStatus);
    const dailyData     = @json($dailyCreated);

    const isDark = document.documentElement.classList.contains('dark');
    const textColor = isDark ? '#94a3b8' : '#64748b';
    const gridColor = isDark ? 'rgba(51, 65, 85, 0.4)' : 'rgba(226, 232, 240, 0.8)';

    const chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: false
            },
            tooltip: {
                backgroundColor: isDark ? '#1e293b' : '#0f172a',
                titleColor: '#ffffff',
                bodyColor: '#e2e8f0',
                padding: 10,
                cornerRadius: 8,
            }
        },
    };

    // 1. Weekly Task Output (Bar)
    const ctxWeekly = document.getElementById('weeklyChart');
    if (ctxWeekly) {
        new Chart(ctxWeekly, {
            type: 'bar',
            data: {
                labels: weeklyData.map(w => w.label),
                datasets: [
                    {
                        label: 'Dibuat',
                        data: weeklyData.map(w => w.created),
                        backgroundColor: '#3b82f6',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8,
                    },
                    {
                        label: 'Selesai',
                        data: weeklyData.map(w => w.completed),
                        backgroundColor: '#10b981',
                        borderRadius: 6,
                        barPercentage: 0.6,
                        categoryPercentage: 0.8,
                    },
                ]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor, font: { size: 11 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor, font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }

    // 2. Status Doughnut
    const ctxStatus = document.getElementById('statusChart');
    if (ctxStatus) {
        const statusLabelsMap = { todo: 'To Do', in_progress: 'In Progress', review: 'Review', done: 'Done', blocked: 'Blocked' };
        const colorPalette = {
            todo: '#94a3b8',
            in_progress: '#3b82f6',
            review: '#fbbf24',
            done: '#10b981',
            blocked: '#ef4444'
        };

        const keys = Object.keys(statusData);
        new Chart(ctxStatus, {
            type: 'doughnut',
            data: {
                labels: keys.map(k => statusLabelsMap[k] || k),
                datasets: [{
                    data: Object.values(statusData),
                    backgroundColor: keys.map(k => colorPalette[k] || '#94a3b8'),
                    borderWidth: 2,
                    borderColor: isDark ? '#1f2937' : '#ffffff',
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                cutout: '72%',
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }

    // 3. Daily Trend Line
    const ctxDaily = document.getElementById('dailyTrendChart');
    if (ctxDaily) {
        new Chart(ctxDaily, {
            type: 'line',
            data: {
                labels: dailyData.map(d => d.label),
                datasets: [{
                    label: 'Task Baru',
                    data: dailyData.map(d => d.count),
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.12)',
                    borderWidth: 2.5,
                    pointRadius: 3,
                    pointBackgroundColor: '#6366f1',
                    pointHoverRadius: 5,
                    tension: 0.35,
                    fill: true,
                }]
            },
            options: {
                ...chartDefaults,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { color: textColor, font: { size: 11 } },
                        grid: { color: gridColor }
                    },
                    x: {
                        ticks: { color: textColor, font: { size: 10 } },
                        grid: { display: false }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
