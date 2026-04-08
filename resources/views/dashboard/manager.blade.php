@extends('layouts.app')

@section('title', 'Manager Dashboard')

@section('page-title', 'Manager Dashboard')

@section('content')
<div class="space-y-6 pt-4">

    {{-- ============================================================
         STAT CARDS
    ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-5 gap-4">

        {{-- Projects --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Projects</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['projects']['total'] }}</p>
            <div class="mt-2 flex gap-3 text-xs text-gray-500">
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                    {{ $stats['projects']['active'] }} aktif
                </span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-blue-400 inline-block"></span>
                    {{ $stats['projects']['completed'] }} selesai
                </span>
            </div>
        </div>

        {{-- Tasks Aktif --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tasks Aktif</span>
                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['tasks']['in_progress'] }}</p>
            <div class="mt-2 flex gap-3 text-xs text-gray-500">
                <span>{{ $stats['tasks']['total'] }} total</span>
                <span class="flex items-center gap-1">
                    <span class="w-2 h-2 rounded-full bg-green-500 inline-block"></span>
                    {{ $stats['tasks']['done'] }} done
                </span>
            </div>
        </div>

        {{-- Tiket Open --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tiket Open</span>
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['tickets']['open'] }}</p>
            <div class="mt-2 text-xs text-red-500 font-medium">
                @if($stats['tickets']['breached'] > 0)
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        {{ $stats['tickets']['breached'] }} SLA breached
                    </span>
                @else
                    <span class="text-gray-400">Tidak ada SLA breach</span>
                @endif
            </div>
        </div>

        {{-- Requests Pending --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Requests Pending</span>
                <div class="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['pending_requests'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Menunggu tindak lanjut</p>
        </div>

        {{-- Revenue --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Revenue</span>
                <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-2xl font-bold text-gray-800">
                Rp {{ number_format($stats['revenue']['total'], 0, ',', '.') }}
            </p>
            @if($stats['revenue']['overdue'] > 0)
                <p class="mt-2 text-xs text-red-500 font-medium">
                    Overdue: Rp {{ number_format($stats['revenue']['overdue'], 0, ',', '.') }}
                </p>
            @else
                <p class="mt-2 text-xs text-gray-500">Tidak ada overdue</p>
            @endif
        </div>

    </div>

    {{-- ============================================================
         THREE PANELS
    ============================================================ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">

        {{-- Panel 1: Recent Projects --}}
        <div class="xl:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Recent Projects</h2>
                <a href="{{ route('projects.index') }}"
                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                    Lihat semua →
                </a>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Nama</th>
                            <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Klien</th>
                            <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recent_projects as $project)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('projects.show', $project->id) }}"
                                       class="font-medium text-gray-800 hover:text-blue-600 line-clamp-1">
                                        {{ $project->name }}
                                    </a>
                                    @if($project->manager)
                                        <p class="text-xs text-gray-400 mt-0.5">{{ $project->manager->name }}</p>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-gray-600 text-xs">
                                    {{ $project->client->name ?? '-' }}
                                </td>
                                <td class="px-3 py-3">
                                    @php
                                        $statusClasses = [
                                            'draft'     => 'bg-gray-100 text-gray-600',
                                            'active'    => 'bg-green-100 text-green-700',
                                            'on_hold'   => 'bg-yellow-100 text-yellow-700',
                                            'completed' => 'bg-blue-100 text-blue-700',
                                            'cancelled' => 'bg-red-100 text-red-700',
                                        ];
                                        $statusLabels = [
                                            'draft'     => 'Draft',
                                            'active'    => 'Aktif',
                                            'on_hold'   => 'On Hold',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Batal',
                                        ];
                                        $sc = $statusClasses[$project->status] ?? 'bg-gray-100 text-gray-600';
                                        $sl = $statusLabels[$project->status] ?? ucfirst($project->status);
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                                        {{ $sl }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-6 text-center text-gray-400 text-sm">
                                    Belum ada proyek
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panel 2: Recent Tickets --}}
        <div class="xl:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Recent Tickets</h2>
                <a href="{{ route('tickets.all') }}"
                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                    Lihat semua →
                </a>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Tiket</th>
                            <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Priority</th>
                            <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">SLA</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recent_tickets as $ticket)
                            @php
                                $priorityClasses = [
                                    'critical' => 'bg-red-100 text-red-700',
                                    'high'     => 'bg-orange-100 text-orange-700',
                                    'medium'   => 'bg-yellow-100 text-yellow-700',
                                    'low'      => 'bg-green-100 text-green-700',
                                ];
                                $pc = $priorityClasses[$ticket->priority] ?? 'bg-gray-100 text-gray-600';
                                $isBreached = isset($ticket->sla_breached) && $ticket->sla_breached;
                            @endphp
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <a href="{{ route('tickets.show', $ticket->id) }}"
                                       class="font-medium text-gray-800 hover:text-blue-600 line-clamp-1">
                                        {{ $ticket->title }}
                                    </a>
                                    <p class="text-xs text-gray-400 mt-0.5">
                                        {{ $ticket->project->name ?? '-' }} · {{ $ticket->reporter->name ?? '-' }}
                                    </p>
                                </td>
                                <td class="px-3 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pc }}">
                                        {{ ucfirst($ticket->priority) }}
                                    </span>
                                </td>
                                <td class="px-3 py-3">
                                    @if($isBreached)
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                            </svg>
                                            Breached
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-700">
                                            OK
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-6 text-center text-gray-400 text-sm">
                                    Belum ada tiket
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Panel 3: Pending Requests --}}
        <div class="xl:col-span-1 bg-white rounded-xl border border-gray-200 shadow-sm flex flex-col">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="font-semibold text-gray-800">Pending Requests</h2>
                <a href="{{ route('requests.index') }}"
                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                    Lihat semua →
                </a>
            </div>
            <div class="overflow-x-auto flex-1">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 bg-gray-50">
                            <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Request</th>
                            <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Klien</th>
                            <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-50">
                        @forelse($recent_requests as $request)
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="px-5 py-3">
                                    <p class="font-medium text-gray-800 line-clamp-1">{{ $request->subject ?? $request->title ?? 'Request #'.$request->id }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $request->project->name ?? '-' }}</p>
                                </td>
                                <td class="px-3 py-3 text-gray-600 text-xs">
                                    {{ $request->customer->name ?? '-' }}
                                </td>
                                <td class="px-3 py-3">
                                    <a href="{{ route('requests.show', $request->id) }}"
                                       class="inline-flex items-center px-2.5 py-1 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors">
                                        Review
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-6 text-center text-gray-400 text-sm">
                                    Tidak ada request pending
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
@endsection
