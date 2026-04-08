@extends('layouts.app')
@section('title', $project ? 'Bug Tickets — ' . $project->name : 'Semua Bug Tickets')
@section('page-title', $project ? 'Bug Tickets: ' . $project->name : 'Semua Bug Tickets')

@section('content')
<div class="py-4">
    {{-- Breadcrumb --}}
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.index') }}" class="hover:text-blue-600">Proyek</a>
        @if($project)
        <span class="mx-2">/</span>
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        @endif
        <span class="mx-2">/</span>
        <span class="text-gray-700">Tickets</span>
    </nav>

    {{-- SLA Summary --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-gray-800">{{ $slaReport['total'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Total Tickets</div>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4 text-center">
            <div class="text-2xl font-bold text-orange-600">{{ $slaReport['open'] }}</div>
            <div class="text-xs text-gray-500 mt-1">Tiket Terbuka</div>
        </div>
        <div class="bg-white rounded-xl border border-red-200 p-4 text-center">
            <div class="text-2xl font-bold text-red-600">{{ $slaReport['breached'] }}</div>
            <div class="text-xs text-gray-500 mt-1">SLA Breach</div>
        </div>
    </div>

    {{-- Header + Filter --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['open','assigned','in_progress','pending_review','resolved','closed','reopened'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
            <select name="priority" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Prioritas</option>
                @foreach(['critical','high','medium','low'] as $p)
                    <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </form>
        @if($project)
        <a href="{{ route('tickets.create', $project) }}"
           class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Buat Tiket
        </a>
        @endif
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    @if(!$project)<th class="px-4 py-3 text-left">Proyek</th>@endif
                    <th class="px-4 py-3 text-left">Tipe</th>
                    <th class="px-4 py-3 text-left">Prioritas</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Reporter</th>
                    <th class="px-4 py-3 text-left">Assignee</th>
                    <th class="px-4 py-3 text-left">SLA</th>
                    <th class="px-4 py-3 text-left">Dibuat</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tickets as $ticket)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate">
                        {{ $ticket->title }}
                        @if($ticket->sla_breached)
                            <span class="ml-1 badge bg-red-100 text-red-700">BREACH</span>
                        @endif
                    </td>
                    @if(!$project)
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $ticket->project->name ?? '-' }}</td>
                    @endif
                    <td class="px-4 py-3 text-gray-600">{{ ucfirst($ticket->type) }}</td>
                    <td class="px-4 py-3">
                        @php
                            $pc = ['critical'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-yellow-100 text-yellow-700','low'=>'bg-green-100 text-green-700'];
                        @endphp
                        <span class="badge {{ $pc[$ticket->priority] ?? '' }}">{{ ucfirst($ticket->priority) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        @php
                            $sc = ['open'=>'bg-blue-100 text-blue-700','assigned'=>'bg-purple-100 text-purple-700','in_progress'=>'bg-yellow-100 text-yellow-700','pending_review'=>'bg-orange-100 text-orange-700','resolved'=>'bg-green-100 text-green-700','closed'=>'bg-gray-100 text-gray-700','reopened'=>'bg-red-100 text-red-700'];
                        @endphp
                        <span class="badge {{ $sc[$ticket->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $ticket->reporter->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-600">{{ $ticket->assignee->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500">
                        @if($ticket->sla_due_at)
                            {{ $ticket->sla_due_at->diffForHumans() }}
                        @else — @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500">{{ $ticket->created_at->format('d M Y') }}</td>
                    <td class="px-4 py-3">
                        <a href="{{ route('tickets.show', $ticket) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">Belum ada tiket.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($tickets->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $tickets->links() }}</div>
        @endif
    </div>
</div>
@endsection
