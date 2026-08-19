@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
@php
    $r = $stats['revenue']['total'];
    $revFormatted = $r >= 1_000_000_000
        ? 'Rp ' . number_format($r / 1_000_000_000, 1) . 'M'
        : ($r >= 1_000_000
            ? 'Rp ' . number_format($r / 1_000_000, 1) . ' jt'
            : 'Rp ' . number_format($r, 0, ',', '.'));
@endphp

<div class="space-y-6 pt-5">

    {{-- ── Hero Greeting ──────────────────────────────────────────────────── --}}
    <div class="relative overflow-hidden rounded-2xl border border-blue-100 px-6 py-6 sm:px-7"
         style="background:linear-gradient(120deg,#eff6ff 0%,#ecfeff 100%)">
        <img src="{{ asset('flovig_icon.png') }}" alt=""
             class="pointer-events-none select-none absolute -right-6 -bottom-8 w-40 h-auto opacity-[0.07]">
        <div class="relative flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                    Selamat datang, {{ auth()->user()->name }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                    &mdash; Pantau progress proyek Anda hari ini
                </p>
            </div>
            <div class="flex items-center gap-3 shrink-0">
                @if(isset($companies) && $companies->isNotEmpty())
                <form method="GET" action="{{ route('dashboard') }}">
                    <select name="company_id" onchange="this.form.submit()"
                            class="text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/30 cursor-pointer">
                        <option value="" {{ empty($selected_company_id) ? 'selected' : '' }}>Semua Company</option>
                        @foreach($companies as $company)
                            <option value="{{ $company->id }}" {{ (int) $selected_company_id === $company->id ? 'selected' : '' }}>
                                {{ $company->name }}
                            </option>
                        @endforeach
                    </select>
                </form>
                @endif
                @can('create projects')
                <a href="{{ route('projects.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shrink-0 transition-colors shadow-sm shadow-blue-600/20">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                    </svg>
                    Proyek Baru
                </a>
                @endcan
            </div>
        </div>
    </div>

    {{-- ── KPI Cards ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">

        {{-- Projects --}}
        <div class="bg-white rounded-2xl border border-gray-200 border-l-4 border-l-indigo-500 shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Projects</span>
                <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-slate-900 leading-none">{{ $stats['projects']['total'] }}</p>
            <div class="mt-2.5 flex flex-wrap gap-3 text-xs font-medium">
                <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $stats['projects']['active'] }} aktif</span>
                <span class="flex items-center gap-1.5 text-indigo-500"><span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>{{ $stats['projects']['completed'] }} selesai</span>
            </div>
            @if($stats['projects']['new_month'] > 0)
            <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
                {{ $stats['projects']['new_month'] }} baru bulan ini
            </span>
            @endif
        </div>

        {{-- Tasks --}}
        <div class="bg-white rounded-2xl border border-gray-200 border-l-4 border-l-blue-500 shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tasks Aktif</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-slate-900 leading-none">{{ $stats['tasks']['in_progress'] }}</p>
            <div class="mt-2.5 flex flex-wrap gap-3 text-xs font-medium">
                <span class="text-slate-400">{{ $stats['tasks']['total'] }} total</span>
                <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $stats['tasks']['done'] }} done</span>
            </div>
            <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
                {{ $stats['tasks']['completion_rate'] }}% completion rate
            </span>
        </div>

        {{-- Tickets --}}
        <div class="bg-white rounded-2xl border border-gray-200 border-l-4 border-l-red-500 shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tiket Open</span>
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                    </svg>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-slate-900 leading-none">{{ $stats['tickets']['open'] }}</p>
            <div class="mt-2.5 text-xs font-medium">
                @if($stats['tickets']['breached'] > 0)
                    <span class="flex items-center gap-1.5 text-red-500">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $stats['tickets']['breached'] }} SLA breached
                    </span>
                @else
                    <span class="flex items-center gap-1.5 text-emerald-600">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Semua SLA aman
                    </span>
                @endif
            </div>
            @if($stats['tickets']['week_change'] !== null)
            <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $stats['tickets']['week_change'] <= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                {{ $stats['tickets']['week_change'] <= 0 ? '↓' : '↑' }} {{ abs($stats['tickets']['week_change']) }}% vs minggu lalu
            </span>
            @endif
        </div>

        {{-- Requests --}}
        <div class="bg-white rounded-2xl border border-gray-200 border-l-4 border-l-amber-500 shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Requests</span>
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                </div>
            </div>
            <p class="text-4xl font-extrabold text-slate-900 leading-none">{{ $stats['pending_requests'] }}</p>
            <div class="mt-2.5 text-xs font-medium text-amber-600">Menunggu tindak lanjut</div>
            <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">
                Perlu direview
            </span>
        </div>

        {{-- Revenue --}}
        <div class="bg-white rounded-2xl border border-gray-200 border-l-4 border-l-emerald-500 shadow-sm hover:shadow-md transition-shadow duration-200 p-5">
            <div class="flex items-start justify-between mb-4">
                <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Revenue</span>
                <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-slate-900 leading-none">{{ $revFormatted }}</p>
            <div class="mt-2.5 text-xs font-medium">
                @if($stats['revenue']['overdue'] > 0)
                    <span class="text-red-500">{{ $stats['revenue']['overdue'] }} invoice overdue</span>
                @else
                    <span class="flex items-center gap-1.5 text-emerald-600">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Tidak ada overdue
                    </span>
                @endif
            </div>
            @if($stats['revenue']['change'] !== null)
            <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full {{ $stats['revenue']['change'] >= 0 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600' }}">
                {{ $stats['revenue']['change'] >= 0 ? '↑' : '↓' }} {{ abs($stats['revenue']['change']) }}% vs bln lalu
            </span>
            @endif
        </div>

    </div>

    {{-- ── Row 2: Revenue Chart + Task Donut ──────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Revenue & Tagihan --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 pt-5 mb-1">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Revenue &amp; Tagihan</h3>
                        <p class="text-sm text-slate-400 mt-0.5">Per bulan · 6 bulan terakhir (juta Rupiah)</p>
                    </div>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500 inline-block"></span>Revenue</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-300 inline-block"></span>Target</span>
                </div>
            </div>
            <div class="px-6 pb-6" style="height:260px">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Task Distribution Donut --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 pt-5">
                <div class="w-9 h-9 rounded-lg bg-violet-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Distribusi Task</h3>
                    <p class="text-sm text-slate-400 mt-0.5">{{ $stats['tasks']['total'] }} task aktif</p>
                </div>
            </div>
            <div class="px-6 pb-2" style="height:165px;position:relative">
                <canvas id="taskDonutChart"></canvas>
            </div>
            <div class="px-6 pb-6 space-y-2.5" id="taskLegend">
                @foreach([
                    ['#059669','Done',        $stats['tasks_dist']['done']],
                    ['#1e73f0','In Progress', $stats['tasks_dist']['in_progress']],
                    ['#38bdf8','To Do',       $stats['tasks_dist']['todo']],
                    ['#d97706','Review',      $stats['tasks_dist']['review']],
                ] as [$col,$label,$val])
                <div class="flex items-center gap-2 text-[13px]">
                    <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background:{{ $col }}"></span>
                    <span class="text-slate-600 flex-1">{{ $label }}</span>
                    <span class="font-bold text-slate-900 tabular-nums">{{ $val }}</span>
                </div>
                @endforeach
            </div>
        </div>

    </div>

    {{-- ── Row 3: Progress Proyek + Aktivitas Terbaru ──────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Progress Proyek --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Progress Proyek</h3>
                </div>
                <a href="{{ route('projects.index') }}"
                   class="text-[13px] font-semibold text-blue-600 hover:text-blue-700 transition-colors">
                    Lihat semua
                </a>
            </div>
            <div class="px-6 py-5 space-y-5">
                @forelse($top_projects as $project)
                    @php
                        $prog = (int) ($project->progress ?? 0);
                        $col  = $prog >= 75 ? '#059669'
                              : ($prog >= 50 ? '#6366f1'
                              : ($prog >= 25 ? '#2563eb' : '#d97706'));
                    @endphp
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <div class="flex items-center gap-2.5 min-w-0">
                                <span class="w-2 h-2 rounded-full shrink-0" style="background:{{ $col }}"></span>
                                <a href="{{ route('projects.show', $project->id) }}"
                                   class="text-sm font-semibold text-slate-700 hover:text-indigo-600 transition-colors whitespace-nowrap truncate">
                                    {{ $project->name }}
                                </a>
                                @if($project->client)
                                <span class="text-xs text-slate-400 truncate">· {{ Str::limit($project->client->name, 12) }}</span>
                                @endif
                            </div>
                            <span class="text-sm font-bold text-slate-900 tabular-nums ml-3 shrink-0" style="color:{{ $col }}">{{ $prog }}%</span>
                        </div>
                        <div class="h-2 rounded-full overflow-hidden" style="background:#e2e8f0">
                            <div class="h-full rounded-full transition-all duration-700"
                                 style="width:{{ $prog }}%;background:linear-gradient(90deg,{{ $col }},{{ $col }}cc)"></div>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-slate-400">Belum ada proyek aktif</div>
                @endforelse
            </div>
        </div>

        {{-- Aktivitas Terbaru --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
                <div class="w-9 h-9 rounded-lg bg-teal-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Aktivitas Terbaru</h3>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($recent_activities as $idx => $activity)
                    <div class="flex items-start gap-3 px-6 py-4 hover:bg-slate-50/70 transition-colors">
                        @if(!empty($activity['user']) && $activity['user']->avatar)
                            <img src="{{ Storage::url($activity['user']->avatar) }}"
                                 alt="{{ $activity['user']->name }}"
                                 class="w-8 h-8 rounded-full object-cover shrink-0">
                        @else
                            <div class="w-8 h-8 rounded-full bg-blue-50 flex items-center justify-center text-blue-600 font-bold text-[11px] shrink-0">
                                {{ strtoupper(substr($activity['user']?->name ?? '?', 0, 2)) }}
                            </div>
                        @endif
                        <div class="flex-1 min-w-0">
                            <p class="text-[13px] text-slate-600 leading-snug">
                                <span class="font-semibold text-slate-900">{{ $activity['user']?->name ?? 'User' }}</span>
                                {{ $activity['message'] }}
                                <span class="font-semibold" style="color:{{ $activity['type'] === 'task' ? '#059669' : '#dc2626' }}">
                                    {{ Str::limit($activity['subject'], 28) }}
                                </span>
                            </p>
                            <p class="text-[11px] text-slate-400 mt-0.5">{{ $activity['time']->diffForHumans() }}</p>
                        </div>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-14 gap-2 text-sm text-slate-400">
                        Belum ada aktivitas
                    </div>
                @endforelse
            </div>
        </div>

    </div>

    {{-- ── Row 4: Tiket Terbaru + Deadline Mendatang ──────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Tiket Terbaru --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">Tiket Terbaru</h3>
                </div>
                <a href="{{ route('tickets.all') }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 text-[13px] font-semibold text-slate-600 bg-white rounded-xl hover:bg-slate-50 transition border border-slate-200">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L14 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 018 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/>
                    </svg>
                    Lihat semua
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left" style="background:#f8fafc">
                            <th class="px-5 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Tiket</th>
                            <th class="px-5 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Klien</th>
                            <th class="px-5 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">Prioritas</th>
                            <th class="px-5 py-2.5 text-[11px] font-bold text-slate-500 uppercase tracking-wider">SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_tickets as $i => $ticket)
                            @php
                                $priMap = [
                                    'low'      => ['Low',    '#f1f5f9','#64748b'],
                                    'medium'   => ['Medium', '#fef3c7','#92400e'],
                                    'high'     => ['High',   '#fee2e2','#991b1b'],
                                    'critical' => ['Critical','#fee2e2','#7f1d1d'],
                                ];
                                [$priLabel,$priBg,$priColor] = $priMap[$ticket->priority ?? 'low'] ?? ['Low','#f1f5f9','#64748b'];

                                if ($ticket->sla_breached) {
                                    $slaLabel = 'Lewat SLA'; $slaBg = '#fee2e2'; $slaColor = '#991b1b';
                                } elseif ($ticket->sla_remaining_minutes !== null && $ticket->sla_remaining_minutes < 240) {
                                    $h = floor($ticket->sla_remaining_minutes / 60);
                                    $slaLabel = $h . 'j tersisa'; $slaBg = '#fef3c7'; $slaColor = '#92400e';
                                } else {
                                    $slaLabel = 'SLA aman'; $slaBg = '#d1fae5'; $slaColor = '#065f46';
                                }
                            @endphp
                            <tr class="{{ $i > 0 ? 'border-t border-slate-50' : '' }}">
                                <td class="px-5 py-3.5">
                                    <div class="font-semibold text-slate-800">{{ Str::limit($ticket->title, 40) }}</div>
                                    <div class="text-xs text-slate-400 font-mono mt-0.5">#{{ $ticket->id }}</div>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 text-[13px]">
                                    {{ $ticket->project?->client?->name ?? $ticket->project?->name ?? '—' }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full"
                                          style="background:{{ $priBg }};color:{{ $priColor }}">{{ $priLabel }}</span>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="text-[11px] font-semibold px-2.5 py-0.5 rounded-full"
                                          style="background:{{ $slaBg }};color:{{ $slaColor }}">{{ $slaLabel }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm text-slate-400">
                                    Tidak ada tiket open saat ini
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Deadline Mendatang --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-sm overflow-hidden">
            <div class="flex items-center gap-3 px-6 py-5 border-b border-slate-100">
                <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-900">Deadline Mendatang</h3>
            </div>
            <div class="px-6 py-5 space-y-3">
                @forelse($upcoming_deadlines as $idx => $ms)
                    @php
                        $daysLeft = now()->startOfDay()->diffInDays($ms->due_date->copy()->startOfDay(), false);
                        $dateBadge = $daysLeft <= 3 ? 'bg-red-50 text-red-600' : ($daysLeft <= 7 ? 'bg-amber-50 text-amber-600' : 'bg-blue-50 text-blue-600');
                    @endphp
                    <div class="flex items-center gap-3 p-2.5 rounded-lg bg-gray-50">
                        <div class="w-12 h-12 rounded-lg flex flex-col items-center justify-center shrink-0 {{ $dateBadge }}">
                            <span class="text-sm font-extrabold leading-none">{{ $ms->due_date->format('d') }}</span>
                            <span class="text-[9px] font-semibold uppercase mt-0.5">{{ $ms->due_date->locale('id')->isoFormat('MMM') }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-[13px] font-semibold text-slate-700 truncate">{{ $ms->title }}</p>
                            <p class="text-[11px] text-slate-400 truncate mt-0.5">{{ $ms->project?->name }}</p>
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-slate-400">
                        Tidak ada deadline mendatang
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

    // ── 1. Revenue & Tagihan ──────────────────────────────────────────────
    (function () {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        const data = @json($revenue_monthly);
        new Chart(ctx.getContext('2d'), {
            type: 'line',
            data: {
                labels: data.map(d => d.month),
                datasets: [
                    {
                        label: 'Revenue',
                        data: data.map(d => +(d.revenue / 1e6).toFixed(2)),
                        borderColor: (c) => {
                            const g = c.chart.ctx.createLinearGradient(0, 0, c.chart.width, 0);
                            g.addColorStop(0, '#1e73f0');
                            g.addColorStop(1, '#12c2b4');
                            return g;
                        },
                        backgroundColor: (c) => {
                            const g = c.chart.ctx.createLinearGradient(0, 0, 0, 230);
                            g.addColorStop(0, 'rgba(30,115,240,0.22)');
                            g.addColorStop(1, 'rgba(18,194,180,0)');
                            return g;
                        },
                        borderWidth: 3, fill: true, tension: 0.4,
                        pointRadius: 0, pointHoverRadius: 5,
                        pointBackgroundColor: '#1e73f0', pointBorderColor: '#fff', pointBorderWidth: 2,
                    },
                    {
                        label: 'Target',
                        data: data.map(d => +(d.target / 1e6).toFixed(2)),
                        borderColor: '#cbd5e1', backgroundColor: 'transparent',
                        borderWidth: 2, borderDash: [5, 5], fill: false, tension: 0.4,
                        pointRadius: 0,
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
                        backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, padding: 12,
                        titleFont: { size: 12, weight: '600' }, bodyFont: { size: 12 },
                        callbacks: { label: c => ` ${c.dataset.label}: Rp ${c.parsed.y.toFixed(1)}M` }
                    }
                },
                scales: {
                    y: { beginAtZero: true, grid: { color: '#f1f5f9' }, border: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8', callback: v => v + 'M' } },
                    x: { grid: { display: false }, border: { display: false }, ticks: { font: { size: 11 }, color: '#94a3b8' } }
                }
            }
        });
    })();

    // ── 2. Task Distribution Donut ────────────────────────────────────────
    (function () {
        const ctx = document.getElementById('taskDonutChart');
        if (!ctx) return;
        new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Done', 'In Progress', 'To Do', 'Review'],
                datasets: [{
                    data: [{{ $stats['tasks_dist']['done'] }},{{ $stats['tasks_dist']['in_progress'] }},{{ $stats['tasks_dist']['todo'] }},{{ $stats['tasks_dist']['review'] }}],
                    backgroundColor: ['#059669','#1e73f0','#38bdf8','#d97706'],
                    borderWidth: 0,
                    cutout: '70%',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(15,23,42,0.9)', cornerRadius: 8, callbacks: { label: c => ` ${c.label}: ${c.parsed}` } }
                }
            }
        });
    })();

});
</script>
@endpush
@endsection
