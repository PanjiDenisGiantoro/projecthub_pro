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
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                Selamat datang, {{ auth()->user()->name }} 👋
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }}
                &mdash; Pantau progress proyek Anda hari ini
            </p>
        </div>
        <div class="flex items-center gap-3 shrink-0">
            @if(isset($companies) && $companies->isNotEmpty())
            <form method="GET" action="{{ route('dashboard') }}">
                <select name="company_id" onchange="this.form.submit()"
                        class="text-sm font-semibold text-slate-700 bg-white border border-slate-200 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-500/30 cursor-pointer">
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
               class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shrink-0 transition-all duration-200 hover:-translate-y-0.5 active:translate-y-0"
               style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 14px rgba(79,70,229,0.35)">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                Proyek Baru
            </a>
            @endcan
        </div>
    </div>

    {{-- ── KPI Cards ───────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 xl:grid-cols-5 gap-4">

        {{-- Projects --}}
        <div class="relative bg-white rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1 cursor-default border border-slate-100"
             style="box-shadow:0 8px 32px rgba(79,70,229,0.10)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl" style="background:linear-gradient(90deg,#4f46e5,#7c3aed)"></div>
            <div class="absolute -top-5 -right-5 w-28 h-28 rounded-full" style="opacity:.08;background:radial-gradient(circle,#4f46e5,transparent)"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Projects</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#4f46e5,#7c3aed);box-shadow:0 4px 12px rgba(79,70,229,0.25)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-slate-900 tabular-nums leading-none">{{ $stats['projects']['total'] }}</p>
                <div class="mt-2.5 flex flex-wrap gap-3 text-xs font-medium">
                    <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $stats['projects']['active'] }} aktif</span>
                    <span class="flex items-center gap-1.5 text-indigo-500"><span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>{{ $stats['projects']['completed'] }} selesai</span>
                </div>
                @if($stats['projects']['new_month'] > 0)
                <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-600">
                    ✦ {{ $stats['projects']['new_month'] }} baru bulan ini
                </span>
                @endif
            </div>
        </div>

        {{-- Tasks --}}
        <div class="relative bg-white rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1 cursor-default border border-slate-100"
             style="box-shadow:0 8px 32px rgba(37,99,235,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl" style="background:linear-gradient(90deg,#2563eb,#06b6d4)"></div>
            <div class="absolute -top-5 -right-5 w-28 h-28 rounded-full" style="opacity:.08;background:radial-gradient(circle,#2563eb,transparent)"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tasks Aktif</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#2563eb,#06b6d4);box-shadow:0 4px 12px rgba(37,99,235,0.25)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-slate-900 tabular-nums leading-none">{{ $stats['tasks']['in_progress'] }}</p>
                <div class="mt-2.5 flex flex-wrap gap-3 text-xs font-medium">
                    <span class="text-slate-400">{{ $stats['tasks']['total'] }} total</span>
                    <span class="flex items-center gap-1.5 text-emerald-600"><span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>{{ $stats['tasks']['done'] }} done</span>
                </div>
                <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-blue-50 text-blue-600">
                    {{ $stats['tasks']['completion_rate'] }}% completion rate
                </span>
            </div>
        </div>

        {{-- Tickets --}}
        <div class="relative bg-white rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1 cursor-default border border-slate-100"
             style="box-shadow:0 8px 32px rgba(220,38,38,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl" style="background:linear-gradient(90deg,#dc2626,#ea580c)"></div>
            <div class="absolute -top-5 -right-5 w-28 h-28 rounded-full" style="opacity:.08;background:radial-gradient(circle,#dc2626,transparent)"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Tiket Open</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#dc2626,#ea580c);box-shadow:0 4px 12px rgba(220,38,38,0.25)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-slate-900 tabular-nums leading-none">{{ $stats['tickets']['open'] }}</p>
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
        </div>

        {{-- Requests --}}
        <div class="relative bg-white rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1 cursor-default border border-slate-100"
             style="box-shadow:0 8px 32px rgba(217,119,6,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl" style="background:linear-gradient(90deg,#d97706,#f59e0b)"></div>
            <div class="absolute -top-5 -right-5 w-28 h-28 rounded-full" style="opacity:.08;background:radial-gradient(circle,#d97706,transparent)"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Requests</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#d97706,#f59e0b);box-shadow:0 4px 12px rgba(217,119,6,0.25)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-slate-900 tabular-nums leading-none">{{ $stats['pending_requests'] }}</p>
                <div class="mt-2.5 text-xs font-medium text-amber-600">Menunggu tindak lanjut</div>
                <span class="mt-2 inline-flex items-center gap-1 text-[10px] font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600">
                    Perlu direview
                </span>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="relative bg-white rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1 cursor-default border border-slate-100"
             style="box-shadow:0 8px 32px rgba(5,150,105,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl" style="background:linear-gradient(90deg,#059669,#10b981)"></div>
            <div class="absolute -top-5 -right-5 w-28 h-28 rounded-full" style="opacity:.08;background:radial-gradient(circle,#059669,transparent)"></div>
            <div class="relative">
                <div class="flex items-start justify-between mb-4">
                    <span class="text-[11px] font-semibold text-slate-500 uppercase tracking-wider">Revenue</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                         style="background:linear-gradient(135deg,#059669,#10b981);box-shadow:0 4px 12px rgba(5,150,105,0.25)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-slate-900 tabular-nums leading-none">{{ $revFormatted }}</p>
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

    </div>

    {{-- ── Row 2: Revenue Chart + Task Donut ──────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">

        {{-- Revenue & Tagihan --}}
        <div class="lg:col-span-2 bg-white rounded-2xl overflow-hidden border border-slate-100"
             style="box-shadow:0 4px 24px rgba(79,70,229,0.07)">
            <div class="flex items-center justify-between px-6 pt-5 mb-1">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Revenue &amp; Tagihan</h3>
                    <p class="text-sm text-slate-400 mt-0.5">Per bulan · 6 bulan terakhir (juta Rupiah)</p>
                </div>
                <div class="flex items-center gap-4 text-xs text-slate-500">
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-indigo-500 inline-block"></span>Revenue</span>
                    <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-slate-300 inline-block"></span>Target</span>
                </div>
            </div>
            <div class="px-6 pb-6" style="height:260px">
                <canvas id="revenueChart"></canvas>
            </div>
        </div>

        {{-- Task Distribution Donut --}}
        <div class="bg-white rounded-2xl overflow-hidden border border-slate-100"
             style="box-shadow:0 4px 24px rgba(99,102,241,0.07)">
            <div class="px-6 pt-5">
                <h3 class="text-lg font-bold text-slate-900">Distribusi Task</h3>
                <p class="text-sm text-slate-400 mt-0.5">{{ $stats['tasks']['total'] }} task aktif</p>
            </div>
            <div class="px-6 pb-2" style="height:165px;position:relative">
                <canvas id="taskDonutChart"></canvas>
            </div>
            <div class="px-6 pb-6 space-y-2.5" id="taskLegend">
                @foreach([
                    ['#059669','Done',        $stats['tasks_dist']['done']],
                    ['#6366f1','In Progress', $stats['tasks_dist']['in_progress']],
                    ['#3b82f6','To Do',       $stats['tasks_dist']['todo']],
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
        <div class="lg:col-span-2 bg-white rounded-2xl overflow-hidden border border-slate-100"
             style="box-shadow:0 4px 24px rgba(79,70,229,0.06)">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Progress Proyek</h3>
                <a href="{{ route('projects.index') }}"
                   class="text-[13px] font-semibold text-indigo-600 hover:text-indigo-700 transition-colors">
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
        <div class="bg-white rounded-2xl overflow-hidden border border-slate-100"
             style="box-shadow:0 4px 24px rgba(79,70,229,0.05)">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Aktivitas Terbaru</h3>
            </div>
            <div class="divide-y divide-slate-50">
                @forelse($recent_activities as $idx => $activity)
                    @php
                        $pal = ['linear-gradient(135deg,#6366f1,#8b5cf6)','linear-gradient(135deg,#dc2626,#ec4899)','linear-gradient(135deg,#059669,#0d9488)','linear-gradient(135deg,#d97706,#ea580c)','linear-gradient(135deg,#2563eb,#0891b2)','linear-gradient(135deg,#7c3aed,#db2777)'];
                        $grad = $pal[$idx % count($pal)];
                    @endphp
                    <div class="flex items-start gap-3 px-6 py-4 hover:bg-slate-50/70 transition-colors">
                        @if(!empty($activity['user']) && $activity['user']->avatar)
                            <img src="{{ Storage::url($activity['user']->avatar) }}"
                                 alt="{{ $activity['user']->name }}"
                                 class="w-8 h-8 rounded-full object-cover shrink-0 ring-2 ring-white shadow-sm">
                        @else
                            <div class="w-8 h-8 rounded-full flex items-center justify-center text-white font-bold text-[11px] shrink-0 ring-2 ring-white shadow-sm"
                                 style="background:{{ $grad }}">
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
        <div class="lg:col-span-2 bg-white rounded-2xl overflow-hidden border border-slate-100"
             style="box-shadow:0 4px 24px rgba(220,38,38,0.06)">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Tiket Terbaru</h3>
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
        <div class="bg-white rounded-2xl overflow-hidden border border-slate-100"
             style="box-shadow:0 4px 24px rgba(79,70,229,0.06)">
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-900">Deadline Mendatang</h3>
            </div>
            <div class="px-6 py-5 space-y-3">
                @php $dlColors = ['#6366f1','#2563eb','#059669','#d97706','#dc2626']; @endphp
                @forelse($upcoming_deadlines as $idx => $ms)
                    @php $dc = $dlColors[$idx % count($dlColors)]; @endphp
                    <div class="flex items-center gap-3 p-2.5 rounded-xl" style="background:#f8fafc">
                        <div class="w-12 h-12 rounded-xl flex flex-col items-center justify-center shrink-0 text-white"
                             style="background:{{ $dc }}">
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
                        borderColor: '#6366f1',
                        backgroundColor: (c) => {
                            const g = c.chart.ctx.createLinearGradient(0, 0, 0, 230);
                            g.addColorStop(0, 'rgba(99,102,241,0.25)');
                            g.addColorStop(1, 'rgba(99,102,241,0)');
                            return g;
                        },
                        borderWidth: 3, fill: true, tension: 0.4,
                        pointRadius: 0, pointHoverRadius: 5,
                        pointBackgroundColor: '#6366f1', pointBorderColor: '#fff', pointBorderWidth: 2,
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
                    backgroundColor: ['#059669','#6366f1','#3b82f6','#d97706'],
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
