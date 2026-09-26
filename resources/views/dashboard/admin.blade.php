@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6 pt-5 pb-8">

    {{-- ── Hero Greeting ──────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-slate-200/80 dark:border-gray-700/80 bg-gradient-to-r from-blue-50/80 via-indigo-50/50 to-cyan-50/60 dark:from-gray-850 dark:via-gray-850 dark:to-gray-800 px-6 py-6 sm:px-7 shadow-xs">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-44 h-auto opacity-[0.05] dark:opacity-[0.03]">
        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-5">
            <div>
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-blue-100/70 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 text-xs font-semibold mb-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-blue-600 dark:bg-blue-400 animate-pulse"></span>
                    Agile Workspace Overview
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-slate-900 dark:text-white tracking-tight">
                    Welcome back, {{ auth()->user()->name }}
                </h1>
                <p class="text-sm text-slate-500 dark:text-slate-400 mt-1 flex items-center gap-2 flex-wrap">
                    <span>{{ \Carbon\Carbon::now()->translatedFormat('l, d F Y') }}</span>
                    <span>&bull;</span>
                    <span>{{ $stats['tasks']['in_progress'] }} tasks in progress across {{ $stats['projects']['active'] }} active projects</span>
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                @if(isset($companies) && $companies->isNotEmpty())
                <form method="GET" action="{{ route('dashboard') }}">
                    <div class="relative">
                        <select name="company_id" onchange="this.form.submit()"
                                class="text-xs sm:text-sm font-semibold text-slate-700 dark:text-slate-200 bg-white dark:bg-gray-800 border border-slate-200 dark:border-gray-700 rounded-xl pl-3.5 pr-8 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer shadow-xs">
                            <option value="" {{ empty($selected_company_id) ? 'selected' : '' }}>All Companies</option>
                            @foreach($companies as $company)
                                <option value="{{ $company->id }}" {{ (int) $selected_company_id === $company->id ? 'selected' : '' }}>
                                    {{ $company->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
                @endif

                @if(auth()->user()->is_super_admin || auth()->user()->can('create project'))
                <a href="{{ route('projects.create') }}"
                   class="inline-flex items-center gap-2 px-4.5 py-2.5 text-xs sm:text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 active:bg-blue-800 rounded-xl shrink-0 transition-colors shadow-sm shadow-blue-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    New Project
                </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ── 4 KPI Cards (Projects, Sprints, Tasks, Quality) ────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4.5">

        {{-- Projects Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Total Projects</span>
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $stats['projects']['total'] }}</p>
                    <span class="text-xs font-semibold text-slate-400">portfolios</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>{{ $stats['projects']['active'] }} active
                </span>
                <span class="flex items-center gap-1.5 text-slate-500 dark:text-slate-400">
                    <span class="w-2 h-2 rounded-full bg-blue-500"></span>{{ $stats['projects']['completed'] }} done
                </span>
            </div>
        </div>

        {{-- Sprints Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Active Sprints</span>
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $stats['sprints']['active'] }}</p>
                    <span class="text-xs font-semibold text-indigo-600 dark:text-indigo-400">in execution</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="text-slate-500 dark:text-slate-400">
                    {{ $stats['sprints']['total'] }} total cycles
                </span>
                <span class="text-indigo-600 dark:text-indigo-400 font-semibold">
                    {{ $stats['sprints']['planned'] }} planned
                </span>
            </div>
        </div>

        {{-- Tasks Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Task Completion</span>
                    <div class="w-9 h-9 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $stats['tasks']['completion_rate'] }}%</p>
                    <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">({{ $stats['tasks']['done'] }}/{{ $stats['tasks']['total'] }} done)</span>
                </div>
                <div class="mt-2.5 w-full bg-slate-100 dark:bg-gray-700/60 h-2 rounded-full overflow-hidden">
                    <div class="bg-gradient-to-r from-emerald-500 to-teal-400 h-full rounded-full transition-all duration-500" style="width: {{ $stats['tasks']['completion_rate'] }}%"></div>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                <span class="text-blue-600 dark:text-blue-400">{{ $stats['tasks']['in_progress'] }} in progress</span>
                <span class="text-amber-600 dark:text-amber-400">{{ $stats['tasks']['review'] }} in review</span>
            </div>
        </div>

        {{-- Quality / Tickets Card --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs hover:shadow-md transition-all duration-200 p-5 flex flex-col justify-between">
            <div>
                <div class="flex items-center justify-between mb-3">
                    <span class="text-[11px] font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">Quality &amp; Support</span>
                    <div class="w-9 h-9 rounded-xl {{ $stats['tickets']['breached'] > 0 ? 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400' : 'bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400' }} flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <div class="flex items-baseline gap-2">
                    <p class="text-3xl sm:text-4xl font-black text-slate-900 dark:text-white tabular-nums">{{ $stats['tickets']['open'] }}</p>
                    <span class="text-xs font-semibold text-slate-400">open tickets</span>
                </div>
            </div>
            <div class="mt-4 pt-3 border-t border-slate-100 dark:border-gray-800/80 flex items-center justify-between text-xs font-medium">
                @if($stats['tickets']['breached'] > 0)
                    <span class="flex items-center gap-1.5 text-red-600 dark:text-red-400 font-semibold">
                        <span class="w-2 h-2 rounded-full bg-red-500 animate-ping"></span>
                        {{ $stats['tickets']['breached'] }} SLA breached
                    </span>
                @else
                    <span class="flex items-center gap-1.5 text-emerald-600 dark:text-emerald-400">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        All SLAs on track
                    </span>
                @endif
                @if($stats['pending_requests'] > 0)
                <span class="text-amber-600 dark:text-amber-400 font-semibold">{{ $stats['pending_requests'] }} requests</span>
                @endif
            </div>
        </div>

    </div>

    {{-- ── Row 2: Task Velocity Chart + Task Donut ────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Task Velocity & Delivery Trend (14 Days) --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div>
                <div class="flex flex-col sm:flex-row sm:items-center justify-between px-6 pt-5 pb-2 gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Task Velocity &amp; Throughput</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Daily completion &amp; new activity over the last 14 days</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-4 text-xs font-semibold">
                        <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 inline-block"></span>Completed Tasks
                        </span>
                        <span class="flex items-center gap-1.5 text-slate-700 dark:text-slate-300">
                            <span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Created Tasks
                        </span>
                    </div>
                </div>

                <div class="px-6 py-4" style="height:250px">
                    <canvas id="taskVelocityChart"></canvas>
                </div>
            </div>

            {{-- Mini velocity summary bar --}}
            <div class="px-6 py-3.5 bg-slate-50/70 dark:bg-gray-800/40 border-t border-slate-100 dark:border-gray-800 grid grid-cols-3 gap-3 text-center">
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Delivered (14d)</span>
                    <p class="text-base font-bold text-emerald-600 dark:text-emerald-400 tabular-nums">
                        {{ collect($task_velocity)->sum('done') }} tasks
                    </p>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">New Tasks (14d)</span>
                    <p class="text-base font-bold text-blue-600 dark:text-blue-400 tabular-nums">
                        {{ collect($task_velocity)->sum('created') }} tasks
                    </p>
                </div>
                <div>
                    <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Net Velocity</span>
                    @php
                        $net = collect($task_velocity)->sum('done') - collect($task_velocity)->sum('created');
                    @endphp
                    <p class="text-base font-bold {{ $net >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400' }} tabular-nums">
                        {{ $net >= 0 ? '+' : '' }}{{ $net }}
                    </p>
                </div>
            </div>
        </div>

        {{-- Task Distribution Donut --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="flex items-center gap-3 px-6 pt-5">
                <div class="w-9 h-9 rounded-xl bg-purple-50 dark:bg-purple-900/30 text-purple-600 dark:text-purple-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Task Distribution</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $stats['tasks']['total'] }} total active tasks</p>
                </div>
            </div>

            <div class="px-6 py-2 relative flex items-center justify-center" style="height:175px">
                <canvas id="taskDonutChart"></canvas>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none pb-1">
                    <span class="text-2xl font-black text-slate-900 dark:text-white tabular-nums">{{ $stats['tasks']['total'] }}</span>
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Total Tasks</span>
                </div>
            </div>

            <div class="px-6 pb-5 space-y-2 border-t border-slate-100 dark:border-gray-800/80 pt-3">
                @php
                    $tTotal = max(1, $stats['tasks']['total']);
                    $distItems = [
                        ['#10b981', 'Done',        $stats['tasks_dist']['done'],        round($stats['tasks_dist']['done'] / $tTotal * 100)],
                        ['#3b82f6', 'In Progress', $stats['tasks_dist']['in_progress'], round($stats['tasks_dist']['in_progress'] / $tTotal * 100)],
                        ['#f59e0b', 'Review',      $stats['tasks_dist']['review'],      round($stats['tasks_dist']['review'] / $tTotal * 100)],
                        ['#64748b', 'To Do',       $stats['tasks_dist']['todo'],        round($stats['tasks_dist']['todo'] / $tTotal * 100)],
                    ];
                @endphp
                @foreach($distItems as [$col, $label, $val, $pct])
                <div class="flex items-center justify-between text-xs">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $col }}"></span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $label }}</span>
                    </div>
                    <div class="flex items-center gap-2 font-semibold">
                        <span class="text-slate-400 text-[11px]">{{ $pct }}%</span>
                        <span class="text-slate-900 dark:text-white tabular-nums">{{ $val }}</span>
                    </div>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Row 3: Active Sprints Spotlight + Project Progress ────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Active Sprints Spotlight --}}
        <div class="lg:col-span-2 bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Active Sprints</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Sprints currently running in agile cycles</p>
                    </div>
                </div>
                <a href="{{ route('projects.index') }}"
                   class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-colors">
                    View all projects &rarr;
                </a>
            </div>

            <div class="p-6">
                @if($active_sprints_list->isNotEmpty())
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($active_sprints_list as $sprint)
                            @php
                                $sTotal = $sprint->tasks_count;
                                $sDone  = $sprint->done_tasks_count;
                                $sProg  = $sTotal > 0 ? (int) round($sDone / $sTotal * 100) : 0;
                                $endDate = $sprint->end_date ? \Carbon\Carbon::parse($sprint->end_date) : null;
                                $daysRemaining = $endDate ? now()->startOfDay()->diffInDays($endDate->copy()->startOfDay(), false) : null;
                            @endphp
                            <div class="p-4 rounded-xl border border-slate-200/90 dark:border-gray-700/80 bg-slate-50/50 dark:bg-gray-800/40 hover:border-blue-400 dark:hover:border-blue-500/50 transition-all duration-200 flex flex-col justify-between gap-3">
                                <div>
                                    <div class="flex items-center justify-between gap-2 mb-1.5">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-mono text-[10px] font-bold px-2 py-0.5 rounded-md bg-blue-100/70 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                                {{ $sprint->code ?: 'SP-' . $sprint->id }}
                                            </span>
                                            <span class="text-[11px] font-medium text-slate-400 truncate max-w-[130px]">
                                                {{ $sprint->project?->name }}
                                            </span>
                                        </div>
                                        @if($daysRemaining !== null)
                                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $daysRemaining < 0 ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : ($daysRemaining <= 3 ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-slate-300') }}">
                                                {{ $daysRemaining < 0 ? 'Overdue' : ($daysRemaining == 0 ? 'Ends today' : $daysRemaining . 'd left') }}
                                            </span>
                                        @endif
                                    </div>
                                    <a href="{{ route('projects.show', ['project' => $sprint->project_id, 'tab' => 'sprint']) }}"
                                       class="text-sm font-bold text-slate-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors line-clamp-1">
                                        {{ $sprint->name }}
                                    </a>
                                </div>

                                <div>
                                    <div class="flex items-center justify-between text-xs font-medium mb-1.5">
                                        <span class="text-slate-500 dark:text-slate-400">{{ $sDone }}/{{ $sTotal }} tasks done</span>
                                        <span class="font-bold text-slate-800 dark:text-slate-200 tabular-nums">{{ $sProg }}%</span>
                                    </div>
                                    <div class="w-full bg-slate-200/80 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                        <div class="bg-gradient-to-r from-blue-600 to-indigo-500 h-full rounded-full transition-all duration-500" style="width:{{ $sProg }}%"></div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-12 flex flex-col items-center justify-center text-center">
                        <div class="w-12 h-12 rounded-full bg-slate-100 dark:bg-gray-800 flex items-center justify-center text-slate-400 mb-3">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700 dark:text-slate-300">No Active Sprints</p>
                        <p class="text-xs text-slate-400 mt-1 max-w-sm">All sprints are either completed or planned. Activate a sprint from the Project Sprint Backlog.</p>
                    </div>
                @endif
            </div>
        </div>

        {{-- Project Progress --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Project Progress</h3>
                </div>
                <a href="{{ route('projects.index') }}"
                   class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-colors">
                    View all
                </a>
            </div>

            <div class="p-6 space-y-5">
                @forelse($top_projects as $project)
                    @php
                        $pTotal = $project->tasks_count ?? 0;
                        $pDone  = $project->done_tasks_count ?? 0;
                        $prog   = $pTotal > 0 ? (int) round($pDone / $pTotal * 100) : (int) ($project->progress ?? 0);
                        $col    = $prog >= 75 ? '#10b981' : ($prog >= 40 ? '#3b82f6' : ($prog >= 20 ? '#6366f1' : '#f59e0b'));
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-1.5">
                            <div class="flex items-center gap-2 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $col }}"></span>
                                <a href="{{ route('projects.show', $project->id) }}"
                                   class="text-xs sm:text-sm font-semibold text-slate-800 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors truncate">
                                    {{ $project->name }}
                                </a>
                                @if($project->client)
                                <span class="text-[11px] text-slate-400 truncate hidden sm:inline">&bull; {{ Str::limit($project->client->name, 14) }}</span>
                                @endif
                            </div>
                            <span class="text-xs font-bold tabular-nums ml-3 shrink-0" style="color:{{ $col }}">{{ $prog }}%</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden bg-slate-100 dark:bg-gray-700">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="width:{{ $prog }}%;background:linear-gradient(90deg,{{ $col }},{{ $col }}cc)"></div>
                        </div>
                        <div class="flex items-center justify-between text-[11px] text-slate-400 mt-1">
                            <span>{{ $pDone }}/{{ $pTotal }} tasks completed</span>
                            <span>{{ $project->sprints_count ?? 0 }} sprints</span>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-slate-400">No active projects yet</div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Row 4: Team Activity + Recent Tickets + Upcoming Deadlines ─────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Col 1: Team Activity Feed --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100 dark:border-gray-800">
                <div class="w-9 h-9 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-teal-600 dark:text-teal-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Team Activity</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Recent task &amp; ticket updates</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-gray-800/80 flex-1">
                @forelse($recent_activities as $activity)
                    @php
                        $userAvatar = $activity['user']?->avatar ? Storage::url($activity['user']->avatar) : null;
                        $userName   = $activity['user']?->name ?? 'User';
                        $initials   = strtoupper(substr($userName, 0, 2));
                        $isTask     = ($activity['type'] ?? '') === 'task';
                    @endphp
                    <div class="flex items-start gap-3 px-6 py-3.5 hover:bg-slate-50/70 dark:hover:bg-gray-800/50 transition-colors">
                        @if($userAvatar)
                            <img src="{{ $userAvatar }}" alt="{{ $userName }}" class="w-8 h-8 rounded-full object-cover shrink-0 mt-0.5 border border-slate-200 dark:border-gray-700">
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/50 text-blue-700 dark:text-blue-300 font-bold text-[11px] flex items-center justify-center shrink-0 mt-0.5">
                                {{ $initials }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-xs text-slate-700 dark:text-slate-300 leading-snug">
                                <span class="font-bold text-slate-900 dark:text-white">{{ $userName }}</span>
                                <span class="text-slate-500 dark:text-slate-400">{{ $activity['message'] }}</span>
                                <span class="font-semibold block truncate mt-0.5" style="color:{{ $isTask ? '#10b981' : '#dc2626' }}">
                                    {{ $activity['subject'] }}
                                </span>
                            </p>
                            <span class="text-[10px] text-slate-400 mt-0.5 block">{{ $activity['time']->diffForHumans() }}</span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 text-center text-xs text-slate-400">No recent team activity</div>
                @endforelse
            </div>
        </div>

        {{-- Col 2: Recent Tickets & Support --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 dark:border-gray-800">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-xl bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Recent Tickets</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Issues &amp; support queue</p>
                    </div>
                </div>
                <a href="{{ route('tickets.all') }}"
                   class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 transition-colors">
                    View all
                </a>
            </div>

            <div class="divide-y divide-slate-100 dark:divide-gray-800/80 flex-1">
                @forelse($recent_tickets as $ticket)
                    @php
                        $priColors = [
                            'low'      => 'bg-slate-100 text-slate-600 dark:bg-gray-700 dark:text-slate-300',
                            'medium'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                            'high'     => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300',
                            'critical' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                        ];
                        $priBadge = $priColors[$ticket->priority ?? 'low'] ?? $priColors['low'];

                        if ($ticket->sla_breached) {
                            $slaBadge = 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
                            $slaText  = 'SLA Breached';
                        } elseif ($ticket->sla_remaining_minutes !== null && $ticket->sla_remaining_minutes < 240) {
                            $h = floor($ticket->sla_remaining_minutes / 60);
                            $slaBadge = 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300';
                            $slaText  = $h . 'h left';
                        } else {
                            $slaBadge = 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300';
                            $slaText  = 'On track';
                        }
                    @endphp
                    <div class="px-6 py-3.5 hover:bg-slate-50/70 dark:hover:bg-gray-800/50 transition-colors flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <a href="{{ route('tickets.show', $ticket->id) }}"
                               class="text-xs font-bold text-slate-800 dark:text-slate-200 hover:text-blue-600 dark:hover:text-blue-400 transition-colors truncate block">
                                {{ $ticket->title }}
                            </a>
                            <div class="flex items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                                <span class="font-mono">#{{ $ticket->id }}</span>
                                <span>&bull;</span>
                                <span class="truncate">{{ $ticket->project?->name ?? 'General' }}</span>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0">
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $priBadge }}">
                                {{ ucfirst($ticket->priority ?? 'normal') }}
                            </span>
                            <span class="text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $slaBadge }}">
                                {{ $slaText }}
                            </span>
                        </div>
                    </div>
                @empty
                    <div class="py-12 flex flex-col items-center justify-center text-center">
                        <div class="w-10 h-10 rounded-full bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">All Clear</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">No open tickets at this time</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Col 3: Upcoming Deadlines & Milestones --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl border border-slate-200/80 dark:border-gray-700/80 shadow-xs overflow-hidden flex flex-col justify-between">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100 dark:border-gray-800">
                <div class="w-9 h-9 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Upcoming Deadlines</h3>
                    <p class="text-xs text-slate-400 mt-0.5">Milestone target schedules</p>
                </div>
            </div>

            <div class="p-6 space-y-3 flex-1">
                @forelse($upcoming_deadlines as $ms)
                    @php
                        $daysLeft = now()->startOfDay()->diffInDays($ms->due_date->copy()->startOfDay(), false);
                        $dateBadge = $daysLeft <= 3 ? 'bg-red-50 text-red-600 dark:bg-red-900/30 dark:text-red-400' : ($daysLeft <= 7 ? 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400');
                    @endphp
                    <div class="flex items-center gap-3.5 p-3 rounded-xl bg-slate-50/70 dark:bg-gray-800/40 border border-slate-100 dark:border-gray-750">
                        <div class="w-11 h-11 rounded-lg flex flex-col items-center justify-center shrink-0 {{ $dateBadge }} font-mono">
                            <span class="text-sm font-extrabold leading-none">{{ $ms->due_date->format('d') }}</span>
                            <span class="text-[9px] font-bold uppercase mt-0.5">{{ $ms->due_date->format('M') }}</span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800 dark:text-slate-200 truncate">{{ $ms->title }}</p>
                            <div class="flex items-center gap-1.5 text-[10px] text-slate-400 mt-0.5">
                                <span class="truncate">{{ $ms->project?->name }}</span>
                                <span>&bull;</span>
                                <span class="font-medium {{ $daysLeft <= 3 ? 'text-red-500 font-semibold' : '' }}">
                                    {{ $daysLeft < 0 ? 'Overdue' : ($daysLeft == 0 ? 'Today' : $daysLeft . ' days left') }}
                                </span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="py-12 flex flex-col items-center justify-center text-center">
                        <div class="w-10 h-10 rounded-full bg-slate-100 dark:bg-gray-800 text-slate-400 flex items-center justify-center mb-2">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <p class="text-xs font-semibold text-slate-700 dark:text-slate-300">No Approaching Deadlines</p>
                        <p class="text-[11px] text-slate-400 mt-0.5">All milestone deadlines are on schedule</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";

    // ── 1. Task Velocity & Throughput Line Chart ──────────────────────────
    (function () {
        const ctx = document.getElementById('taskVelocityChart');
        if (!ctx) return;
        const velocityData = @json($task_velocity);

        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: velocityData.map(d => d.date),
                datasets: [
                    {
                        label: 'Completed Tasks',
                        data: velocityData.map(d => d.done),
                        borderColor: '#10b981',
                        backgroundColor: (c) => {
                            const g = c.chart.ctx.createLinearGradient(0, 0, 0, 220);
                            g.addColorStop(0, 'rgba(16, 185, 129, 0.25)');
                            g.addColorStop(1, 'rgba(16, 185, 129, 0.00)');
                            return g;
                        },
                        borderWidth: 2.5,
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2.5,
                        pointHoverRadius: 6,
                        pointBackgroundColor: '#10b981',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                    },
                    {
                        label: 'Created Tasks',
                        data: velocityData.map(d => d.created),
                        borderColor: '#3b82f6',
                        backgroundColor: (c) => {
                            const g = c.chart.ctx.createLinearGradient(0, 0, 0, 220);
                            g.addColorStop(0, 'rgba(59, 130, 246, 0.15)');
                            g.addColorStop(1, 'rgba(59, 130, 246, 0.00)');
                            return g;
                        },
                        borderWidth: 2,
                        borderDash: [4, 4],
                        fill: true,
                        tension: 0.35,
                        pointRadius: 2,
                        pointHoverRadius: 5,
                        pointBackgroundColor: '#3b82f6',
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: { mode: 'index', intersect: false },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        cornerRadius: 10,
                        padding: 12,
                        titleFont: { size: 12, weight: '700' },
                        bodyFont: { size: 12 },
                        callbacks: {
                            label: c => ` ${c.dataset.label}: ${c.parsed.y} task${c.parsed.y === 1 ? '' : 's'}`
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(226, 232, 240, 0.5)' },
                        border: { display: false },
                        ticks: {
                            precision: 0,
                            font: { size: 11 },
                            color: '#94a3b8'
                        }
                    },
                    x: {
                        grid: { display: false },
                        border: { display: false },
                        ticks: {
                            font: { size: 11 },
                            color: '#94a3b8'
                        }
                    }
                }
            }
        });
    })();

    // ── 2. Task Distribution Donut Chart ──────────────────────────────────
    (function () {
        const ctx = document.getElementById('taskDonutChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Done', 'In Progress', 'Review', 'To Do'],
                datasets: [{
                    data: [
                        {{ $stats['tasks_dist']['done'] }},
                        {{ $stats['tasks_dist']['in_progress'] }},
                        {{ $stats['tasks_dist']['review'] }},
                        {{ $stats['tasks_dist']['todo'] }}
                    ],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#64748b'],
                    borderWidth: 0,
                    cutout: '72%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.92)',
                        cornerRadius: 10,
                        padding: 10,
                        callbacks: {
                            label: c => ` ${c.label}: ${c.parsed} tasks`
                        }
                    }
                }
            }
        });
    })();

});
</script>
@endpush
@endsection
