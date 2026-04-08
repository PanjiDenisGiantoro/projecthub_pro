@extends('layouts.app')

@section('title', 'Dashboard Saya')

@section('page-title', 'Dashboard Saya')

@section('content')
<div class="space-y-6 pt-4">

    {{-- ============================================================
         STAT CARDS
    ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">

        {{-- Pending Requests --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Request Pending</span>
                <div class="w-9 h-9 rounded-lg bg-yellow-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['pending_requests'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Menunggu respon tim</p>
        </div>

        {{-- Open Tickets --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Tiket Terbuka</span>
                <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['open_tickets'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Bug / laporan aktif</p>
        </div>

        {{-- Unpaid Invoices --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Invoice Belum Dibayar</span>
                <div class="w-9 h-9 rounded-lg bg-orange-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2zM10 8.5a.5.5 0 11-1 0 .5.5 0 011 0zm5 5a.5.5 0 11-1 0 .5.5 0 011 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-orange-600">{{ $stats['unpaid_invoices'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Perlu segera dibayar</p>
        </div>

    </div>

    {{-- ============================================================
         PROJECT CARDS GRID
    ============================================================ --}}
    <div>
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-semibold text-gray-800">Proyek Saya</h2>
            <a href="{{ route('projects.index') }}"
               class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                Lihat semua →
            </a>
        </div>

        @if($projects->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-10 text-center">
                <svg class="w-12 h-12 text-gray-200 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                </svg>
                <p class="text-gray-500 text-sm">Belum ada proyek yang terdaftar</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($projects as $project)
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
                        $progressBarColors = [
                            'draft'     => 'bg-gray-400',
                            'active'    => 'bg-green-500',
                            'on_hold'   => 'bg-yellow-400',
                            'completed' => 'bg-blue-500',
                            'cancelled' => 'bg-red-400',
                        ];
                        $sc  = $statusClasses[$project->status] ?? 'bg-gray-100 text-gray-600';
                        $sl  = $statusLabels[$project->status] ?? ucfirst($project->status);
                        $pbc = $progressBarColors[$project->status] ?? 'bg-blue-500';
                        $progress = min(100, max(0, (int) ($project->progress ?? 0)));

                        $totalMilestones     = $project->milestones->count();
                        $completedMilestones = $project->milestones->where('status', 'completed')->count();
                    @endphp
                    <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5 flex flex-col gap-3 hover:shadow-md transition-shadow">
                        {{-- Header --}}
                        <div class="flex items-start justify-between gap-2">
                            <a href="{{ route('projects.show', $project->id) }}"
                               class="font-semibold text-gray-800 hover:text-blue-600 leading-tight line-clamp-2 flex-1">
                                {{ $project->name }}
                            </a>
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }} flex-shrink-0">
                                {{ $sl }}
                            </span>
                        </div>

                        {{-- Progress bar --}}
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs text-gray-500">Progress</span>
                                <span class="text-xs font-semibold text-gray-700">{{ $progress }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-2">
                                <div class="h-2 rounded-full {{ $pbc }} transition-all duration-500"
                                     style="width: {{ $progress }}%"></div>
                            </div>
                        </div>

                        {{-- Milestones info --}}
                        @if($totalMilestones > 0)
                            <div class="flex items-center gap-1.5 text-xs text-gray-500">
                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                                </svg>
                                <span>{{ $completedMilestones }}/{{ $totalMilestones }} milestone selesai</span>
                            </div>
                        @endif

                        {{-- Footer dates --}}
                        <div class="flex items-center justify-between pt-1 border-t border-gray-50 text-xs text-gray-400">
                            @if($project->start_date)
                                <span>{{ \Carbon\Carbon::parse($project->start_date)->format('d M Y') }}</span>
                            @else
                                <span>—</span>
                            @endif
                            @if($project->end_date)
                                <span class="{{ \Carbon\Carbon::parse($project->end_date)->isPast() && $project->status !== 'completed' ? 'text-red-500 font-medium' : '' }}">
                                    {{ \Carbon\Carbon::parse($project->end_date)->format('d M Y') }}
                                </span>
                            @else
                                <span>—</span>
                            @endif
                        </div>

                        {{-- CTA --}}
                        <a href="{{ route('projects.show', $project->id) }}"
                           class="w-full text-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg transition-colors">
                            Lihat Detail
                        </a>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ============================================================
         RECENT REQUESTS TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between px-5 py-4 border-b border-gray-100 gap-3">
            <h2 class="font-semibold text-gray-800">Request Terbaru</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('requests.index') }}"
                   class="text-xs text-blue-600 hover:text-blue-800 font-medium">
                    Lihat semua →
                </a>
                <a href="{{ route('requests.create') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors">
                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Buat Request
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Subjek</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Proyek</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Tanggal</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($recent_requests as $request)
                        @php
                            $reqStatusClasses = [
                                'pending'     => 'bg-yellow-100 text-yellow-700',
                                'in_review'   => 'bg-blue-100 text-blue-700',
                                'approved'    => 'bg-green-100 text-green-700',
                                'rejected'    => 'bg-red-100 text-red-700',
                                'completed'   => 'bg-gray-100 text-gray-600',
                            ];
                            $reqStatusLabels = [
                                'pending'     => 'Pending',
                                'in_review'   => 'Direview',
                                'approved'    => 'Disetujui',
                                'rejected'    => 'Ditolak',
                                'completed'   => 'Selesai',
                            ];
                            $rsc = $reqStatusClasses[$request->status] ?? 'bg-gray-100 text-gray-600';
                            $rsl = $reqStatusLabels[$request->status] ?? ucfirst($request->status ?? 'pending');
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-5 py-3">
                                <a href="{{ route('requests.show', $request->id) }}"
                                   class="font-medium text-gray-800 hover:text-blue-600 line-clamp-1">
                                    {{ $request->subject ?? $request->title ?? 'Request #'.$request->id }}
                                </a>
                            </td>
                            <td class="px-5 py-3 text-xs text-gray-500">
                                {{ $request->project->name ?? '-' }}
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $rsc }}">
                                    {{ $rsl }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-xs text-gray-500">
                                {{ \Carbon\Carbon::parse($request->created_at)->format('d M Y') }}
                            </td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('requests.show', $request->id) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-5 py-10 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3v-3z"/>
                                    </svg>
                                    <p class="text-sm">Belum ada request</p>
                                    <a href="{{ route('requests.create') }}"
                                       class="mt-2 inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors">
                                        Buat Request Pertama
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
