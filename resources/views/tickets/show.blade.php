@extends('layouts.app')
@section('title', 'Tiket #' . $ticket->id)
@section('page-title', 'Detail Tiket')

@section('content')
@php
    $pc = ['critical'=>'bg-red-100 text-red-700','high'=>'bg-orange-100 text-orange-700','medium'=>'bg-yellow-100 text-yellow-700','low'=>'bg-green-100 text-green-700'];
    $sc = ['open'=>'bg-blue-100 text-blue-700','assigned'=>'bg-purple-100 text-purple-700','in_progress'=>'bg-yellow-100 text-yellow-700','pending_review'=>'bg-orange-100 text-orange-700','resolved'=>'bg-green-100 text-green-700','closed'=>'bg-gray-100 text-gray-700','reopened'=>'bg-red-100 text-red-700'];
    $user = auth()->user();
@endphp
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $ticket->project) }}" class="hover:text-blue-600">{{ $ticket->project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('tickets.index', $ticket->project) }}" class="hover:text-blue-600">Tickets</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">#{{ $ticket->id }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main --}}
        <div class="lg:col-span-2 space-y-5">
            {{-- Header --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">{{ $ticket->title }}</h2>
                    <div class="flex gap-2 flex-shrink-0">
                        <span class="badge {{ $pc[$ticket->priority] ?? '' }}">{{ ucfirst($ticket->priority) }}</span>
                        <span class="badge {{ $sc[$ticket->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$ticket->status)) }}</span>
                        @if($ticket->sla_breached)
                            <span class="badge bg-red-100 text-red-700">SLA BREACH</span>
                        @endif
                    </div>
                </div>
                <p class="text-gray-600 text-sm whitespace-pre-line">{{ $ticket->description }}</p>
            </div>

            {{-- SLA Bar --}}
            @if($ticket->sla_due_at && !in_array($ticket->status, ['resolved','closed']))
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">SLA Timer</h4>
                @php
                    $slaRemaining = max(0, now()->diffInMinutes($ticket->sla_due_at, false));
                    $slaPercent   = $ticket->sla_percent_used ?? 0;
                    $slaColor     = $slaPercent >= 100 ? 'bg-red-500' : ($slaPercent >= 75 ? 'bg-yellow-400' : 'bg-green-500');
                @endphp
                <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                    <span>Terpakai {{ $slaPercent }}%</span>
                    <span>Sisa: {{ $slaRemaining > 0 ? floor($slaRemaining/60).'j '.($slaRemaining%60).'m' : 'HABIS' }}</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-2">
                    <div class="{{ $slaColor }} h-2 rounded-full transition-all" style="width: {{ min($slaPercent,100) }}%"></div>
                </div>
                <p class="text-xs text-gray-400 mt-1">Batas resolusi: {{ $ticket->sla_due_at->format('d M Y H:i') }}</p>
            </div>
            @endif

            {{-- Comments --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-4">Komentar & Diskusi</h4>
                <div class="space-y-4 mb-5">
                    @forelse($ticket->comments as $comment)
                    <div class="flex gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
                            {{ strtoupper(substr($comment->user->name,0,2)) }}
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $comment->user->name }}</span>
                                <span class="text-xs text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $comment->body }}</p>
                        </div>
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">Belum ada komentar.</p>
                    @endforelse
                </div>
                <form method="POST" action="{{ route('tickets.comment', $ticket) }}" class="flex gap-3">
                    @csrf
                    <textarea name="body" rows="2" placeholder="Tulis komentar..." required
                              class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 resize-none"></textarea>
                    <button type="submit" class="self-end bg-violet-600 hover:bg-violet-700 text-white text-sm px-4 py-2 rounded-lg transition-colors">Kirim</button>
                </form>
            </div>

            {{-- History --}}
            @if($ticket->histories->count())
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Riwayat Perubahan</h4>
                <div class="space-y-2">
                    @foreach($ticket->histories->sortByDesc('created_at') as $h)
                    <div class="text-xs text-gray-500 flex gap-2">
                        <span class="font-medium text-gray-700">{{ $h->actor->name }}</span>
                        <span>mengubah <span class="font-mono bg-gray-100 px-1 rounded">{{ $h->field_changed }}</span></span>
                        <span>dari <span class="line-through">{{ $h->old_value ?? '—' }}</span> → <span class="font-medium text-gray-700">{{ $h->new_value }}</span></span>
                        <span class="ml-auto">{{ $h->created_at->format('d M H:i') }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            {{-- Info --}}
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3 text-sm">
                <div><span class="text-gray-500">Proyek:</span> <span class="font-medium">{{ $ticket->project->name }}</span></div>
                <div><span class="text-gray-500">Tipe:</span> <span class="font-medium">{{ ucfirst($ticket->type) }}</span></div>
                <div><span class="text-gray-500">Reporter:</span> <span class="font-medium">{{ $ticket->reporter->name }}</span></div>
                <div><span class="text-gray-500">Assignee:</span> <span class="font-medium">{{ $ticket->assignee->name ?? '—' }}</span></div>
                <div><span class="text-gray-500">Dibuat:</span> <span>{{ $ticket->created_at->format('d M Y H:i') }}</span></div>
                @if($ticket->resolved_at)
                <div><span class="text-gray-500">Resolved:</span> <span>{{ $ticket->resolved_at->format('d M Y H:i') }}</span></div>
                @endif
            </div>

            {{-- Update Status --}}
            @if($user->hasRole(['admin','manager','developer']))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Update Status</h4>
                <form method="POST" action="{{ route('tickets.status', $ticket) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <select name="status" class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        @foreach(['open','assigned','in_progress','pending_review','resolved','closed'] as $s)
                            <option value="{{ $s }}" {{ $ticket->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm px-3 py-2 rounded-lg transition-colors">Simpan</button>
                </form>
            </div>
            @endif

            {{-- Assign --}}
            @if($user->hasRole(['admin','manager']))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Assign Developer</h4>
                <form method="POST" action="{{ route('tickets.assign', $ticket) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <select name="assignee_id" class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">— Pilih Developer —</option>
                        @foreach($developers as $dev)
                            <option value="{{ $dev->id }}" {{ $ticket->assignee_id === $dev->id ? 'selected' : '' }}>{{ $dev->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-violet-600 hover:bg-violet-700 text-white text-sm px-3 py-2 rounded-lg transition-colors">Assign</button>
                </form>
            </div>
            @endif

            {{-- Reopen --}}
            @if($ticket->status === 'closed' && $user->hasRole('customer'))
            <div class="bg-white rounded-xl border border-gray-200 p-5" x-data="{open:false}">
                <button @click="open=!open" class="w-full text-sm font-medium text-red-600 hover:text-red-800">Buka Kembali Tiket</button>
                <div x-show="open" x-cloak class="mt-3">
                    <form method="POST" action="{{ route('tickets.reopen', $ticket) }}">
                        @csrf @method('PUT')
                        <textarea name="reason" rows="2" placeholder="Alasan reopen..." required
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-violet-500 mb-2"></textarea>
                        <button type="submit" class="w-full bg-red-600 hover:bg-red-700 text-white text-sm py-2 rounded-lg transition-colors">Reopen</button>
                    </form>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
