@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="space-y-6 pt-5">

    {{-- ── Hero greeting ──────────────────────────────────────────────────── --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
                Selamat datang, {{ auth()->user()->name }} 👋
            </h1>
            <p class="text-sm text-gray-500 mt-0.5">
                {{ \Carbon\Carbon::now()->locale('id')->isoFormat('dddd, D MMMM Y') }} &mdash; Pantau progress proyek Anda hari ini
            </p>
        </div>
        @can('create projects')
        <a href="{{ route('projects.create') }}"
           class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-semibold text-white rounded-xl shrink-0 transition-all duration-200 hover:-translate-y-0.5 hover:shadow-lg"
           style="background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 4px 14px rgba(79,70,229,0.35)">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
            </svg>
            Proyek Baru
        </a>
        @endcan
    </div>

    {{-- ── Glassmorphism Stat Cards ────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">

        {{-- Projects --}}
        <div class="group relative glass-card rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1"
             style="box-shadow: 0 8px 32px rgba(79,70,229,0.1)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl card-stripe-indigo"></div>
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #4f46e5, transparent)"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Projects</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center icon-bg-indigo"
                         style="box-shadow: 0 4px 12px rgba(79,70,229,0.35)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 tabular-nums">{{ $stats['projects']['total'] }}</p>
                <div class="mt-2.5 flex flex-wrap gap-3 text-xs font-medium">
                    <span class="flex items-center gap-1.5 text-emerald-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ $stats['projects']['active'] }} aktif
                    </span>
                    <span class="flex items-center gap-1.5 text-indigo-500">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        {{ $stats['projects']['completed'] }} selesai
                    </span>
                </div>
            </div>
        </div>

        {{-- Tasks Active --}}
        <div class="group relative glass-card rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1"
             style="box-shadow: 0 8px 32px rgba(37,99,235,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl card-stripe-blue"></div>
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #2563eb, transparent)"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tasks Aktif</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center icon-bg-blue"
                         style="box-shadow: 0 4px 12px rgba(37,99,235,0.3)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 tabular-nums">{{ $stats['tasks']['in_progress'] }}</p>
                <div class="mt-2.5 flex flex-wrap gap-3 text-xs font-medium">
                    <span class="text-gray-500">{{ $stats['tasks']['total'] }} total</span>
                    <span class="flex items-center gap-1.5 text-emerald-600">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                        {{ $stats['tasks']['done'] }} done
                    </span>
                </div>
            </div>
        </div>

        {{-- Tickets Open --}}
        <div class="group relative glass-card rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1"
             style="box-shadow: 0 8px 32px rgba(220,38,38,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl card-stripe-red"></div>
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #dc2626, transparent)"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Tiket Open</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center icon-bg-red"
                         style="box-shadow: 0 4px 12px rgba(220,38,38,0.3)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 tabular-nums">{{ $stats['tickets']['open'] }}</p>
                <div class="mt-2.5 text-xs font-medium">
                    @if($stats['tickets']['breached'] > 0)
                        <span class="inline-flex items-center gap-1.5 text-red-500">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                            </svg>
                            {{ $stats['tickets']['breached'] }} SLA breached
                        </span>
                    @else
                        <span class="text-emerald-600 flex items-center gap-1.5">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Semua SLA aman
                        </span>
                    @endif
                </div>
            </div>
        </div>

        {{-- Requests Pending --}}
        <div class="group relative glass-card rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1"
             style="box-shadow: 0 8px 32px rgba(217,119,6,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl card-stripe-amber"></div>
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #d97706, transparent)"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Requests</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center icon-bg-amber"
                         style="box-shadow: 0 4px 12px rgba(217,119,6,0.3)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-3xl font-extrabold text-gray-900 tabular-nums">{{ $stats['pending_requests'] }}</p>
                <p class="mt-2.5 text-xs font-medium text-amber-600">Menunggu tindak lanjut</p>
            </div>
        </div>

        {{-- Revenue --}}
        <div class="group relative glass-card rounded-2xl p-5 overflow-hidden transition-all duration-300 hover:-translate-y-1"
             style="box-shadow: 0 8px 32px rgba(5,150,105,0.08)">
            <div class="absolute inset-x-0 top-0 h-[3px] rounded-t-2xl card-stripe-emerald"></div>
            <div class="absolute -top-6 -right-6 w-28 h-28 rounded-full opacity-10"
                 style="background: radial-gradient(circle, #059669, transparent)"></div>
            <div class="relative">
                <div class="flex items-center justify-between mb-4">
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Revenue</span>
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center icon-bg-emerald"
                         style="box-shadow: 0 4px 12px rgba(5,150,105,0.3)">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
                <p class="text-2xl font-extrabold text-gray-900 tabular-nums leading-tight">
                    Rp {{ number_format($stats['revenue']['total'], 0, ',', '.') }}
                </p>
                <div class="mt-2.5 text-xs font-medium">
                    @if($stats['revenue']['overdue'] > 0)
                        <span class="text-red-500">{{ $stats['revenue']['overdue'] }} invoice overdue</span>
                    @else
                        <span class="text-emerald-600 flex items-center gap-1.5">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            Tidak ada overdue
                        </span>
                    @endif
                </div>
            </div>
        </div>

    </div>

    {{-- ── Three panels ────────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-5">

        {{-- Panel: Recent Projects --}}
        <div class="glass-card rounded-2xl overflow-hidden flex flex-col"
             style="box-shadow: 0 4px 24px rgba(79,70,229,0.07)">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center icon-bg-indigo">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                        </svg>
                    </div>
                    <h2 class="font-bold text-gray-800 text-sm">Recent Projects</h2>
                </div>
                <a href="{{ route('projects.index') }}"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Semua →
                </a>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/60 bg-white/30">
                            <th class="text-left px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nama</th>
                            <th class="text-left px-3 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Klien</th>
                            <th class="text-left px-3 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_projects as $project)
                            @php
                                $sc = ['draft'=>'bg-gray-100 text-gray-600','active'=>'bg-emerald-100 text-emerald-700','on_hold'=>'bg-amber-100 text-amber-700','completed'=>'bg-indigo-100 text-indigo-700','cancelled'=>'bg-red-100 text-red-600'][$project->status] ?? 'bg-gray-100 text-gray-600';
                                $sl = ['draft'=>'Draft','active'=>'Aktif','on_hold'=>'On Hold','completed'=>'Selesai','cancelled'=>'Batal'][$project->status] ?? ucfirst($project->status);
                            @endphp
                            <tr class="border-b border-white/40 hover:bg-indigo-50/30 transition-colors">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('projects.show', $project->id) }}"
                                       class="font-semibold text-gray-800 hover:text-indigo-600 transition-colors line-clamp-1 text-sm">
                                        {{ $project->name }}
                                    </a>
                                    @if($project->manager)
                                        <p class="text-[11px] text-gray-400 mt-0.5">{{ $project->manager->name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3.5 text-gray-500 text-xs">{{ $project->client->name ?? '—' }}</td>
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $sc }}">{{ $sl }}</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-10 h-10 rounded-full bg-indigo-50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-indigo-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                                        </div>
                                        <p class="text-sm text-gray-400">Belum ada proyek</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panel: Recent Tickets --}}
        <div class="glass-card rounded-2xl overflow-hidden flex flex-col"
             style="box-shadow: 0 4px 24px rgba(220,38,38,0.06)">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center icon-bg-red">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/>
                        </svg>
                    </div>
                    <h2 class="font-bold text-gray-800 text-sm">Recent Tickets</h2>
                </div>
                <a href="{{ route('tickets.all') }}"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Semua →
                </a>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-white/60 bg-white/30">
                            <th class="text-left px-5 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Tiket</th>
                            <th class="text-left px-3 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">Priority</th>
                            <th class="text-left px-3 py-3 text-[11px] font-bold text-gray-400 uppercase tracking-wider">SLA</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recent_tickets as $ticket)
                            @php
                                $pc = ['critical'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-amber-100 text-amber-700','low'=>'bg-emerald-100 text-emerald-700'][$ticket->priority] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="border-b border-white/40 hover:bg-red-50/20 transition-colors">
                                <td class="px-5 py-3.5">
                                    <a href="{{ route('tickets.show', $ticket->id) }}"
                                       class="font-semibold text-gray-800 hover:text-indigo-600 transition-colors line-clamp-1 text-sm">
                                        {{ $ticket->title }}
                                    </a>
                                    <p class="text-[11px] text-gray-400 mt-0.5">
                                        {{ $ticket->project->name ?? '—' }} · {{ $ticket->reporter->name ?? '—' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3.5">
                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold {{ $pc }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3.5">
                                    @if(isset($ticket->sla_breached) && $ticket->sla_breached)
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-red-100 text-red-700">
                                            <svg class="w-2.5 h-2.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                            Breach
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-semibold bg-emerald-100 text-emerald-700">OK</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-10 text-center">
                                    <div class="flex flex-col items-center gap-2">
                                        <div class="w-10 h-10 rounded-full bg-red-50 flex items-center justify-center">
                                            <svg class="w-5 h-5 text-red-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 00-2 2v3a2 2 0 110 4v3a2 2 0 002 2h14a2 2 0 002-2v-3a2 2 0 110-4V7a2 2 0 00-2-2H5z"/></svg>
                                        </div>
                                        <p class="text-sm text-gray-400">Tidak ada tiket open</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panel: Pending Requests --}}
        <div class="glass-card rounded-2xl overflow-hidden flex flex-col"
             style="box-shadow: 0 4px 24px rgba(217,119,6,0.06)">
            <div class="flex items-center justify-between px-5 py-4 border-b border-white/60">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg flex items-center justify-center icon-bg-amber">
                        <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                        </svg>
                    </div>
                    <h2 class="font-bold text-gray-800 text-sm">Pending Requests</h2>
                </div>
                <a href="{{ route('requests.index') }}"
                   class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                    Semua →
                </a>
            </div>
            <div class="flex-1">
                @forelse($recent_requests as $request)
                    <div class="flex items-start gap-3 px-5 py-4 border-b border-white/40 hover:bg-amber-50/20 transition-colors last:border-0">
                        <div class="w-8 h-8 rounded-lg bg-amber-100 flex items-center justify-center shrink-0 mt-0.5">
                            <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="font-semibold text-gray-800 text-sm line-clamp-1">
                                {{ $request->subject ?? $request->title ?? 'Request #'.$request->id }}
                            </p>
                            <p class="text-[11px] text-gray-400 mt-0.5">
                                {{ $request->customer->name ?? '—' }}
                                @if($request->project) · {{ $request->project->name }} @endif
                            </p>
                        </div>
                        <a href="{{ route('requests.show', $request->id) }}"
                           class="inline-flex items-center px-3 py-1.5 text-[11px] font-semibold text-white rounded-lg shrink-0 transition-all hover:-translate-y-0.5"
                           style="background: linear-gradient(135deg, #4f46e5, #7c3aed); box-shadow: 0 2px 8px rgba(79,70,229,0.3)">
                            Review
                        </a>
                    </div>
                @empty
                    <div class="flex flex-col items-center justify-center py-12 gap-2">
                        <div class="w-10 h-10 rounded-full bg-amber-50 flex items-center justify-center">
                            <svg class="w-5 h-5 text-amber-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/></svg>
                        </div>
                        <p class="text-sm text-gray-400">Tidak ada request pending</p>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>
@endsection
