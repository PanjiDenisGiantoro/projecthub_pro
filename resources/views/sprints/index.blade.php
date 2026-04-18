@extends('layouts.app')
@section('title', 'Sprint — ' . $project->name)
@section('page-title', 'Sprint Planning')

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span><span class="text-gray-700">Sprints</span>
    </nav>

    <div class="flex justify-between items-center mb-5">
        <h2 class="font-semibold text-gray-800">Sprints ({{ $sprints->count() }})</h2>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Sprint Baru'"></span>
        </button>
        @endif
    </div>

    {{-- New Sprint Form --}}
    @if(!auth()->user()->hasRole('customer'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-5">
        <form method="POST" action="{{ route('sprints.store', $project) }}" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Nama Sprint *</label>
                <input type="text" name="name" required placeholder="e.g. Sprint 1 — Autentikasi"
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <label class="block text-xs font-medium text-gray-600 mb-1">Sprint Goal</label>
                <input type="text" name="goal" placeholder="Tujuan sprint ini..."
                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Mulai</label>
                <input type="date" name="start_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Selesai</label>
                <input type="date" name="end_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg">Buat Sprint</button>
            </div>
        </form>
    </div>
    @endif

    {{-- Sprints list --}}
    <div class="space-y-4 mb-8">
        @forelse($sprints as $sprint)
        @php
            $statusColor = match($sprint->status) {
                'active'    => 'bg-green-100 text-green-700',
                'completed' => 'bg-gray-100 text-gray-600',
                default     => 'bg-blue-100 text-blue-700',
            };
            $pct = $sprint->totalPoints() > 0 ? round($sprint->completedPoints() / $sprint->totalPoints() * 100) : 0;
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <div class="flex items-start justify-between gap-4 mb-3">
                <div class="flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('sprints.show', [$project, $sprint]) }}"
                           class="text-base font-semibold text-gray-800 hover:text-blue-600">{{ $sprint->name }}</a>
                        <span class="text-xs px-2 py-0.5 rounded-full {{ $statusColor }}">{{ ucfirst($sprint->status) }}</span>
                    </div>
                    @if($sprint->goal)
                    <p class="text-sm text-gray-500 mt-1">{{ $sprint->goal }}</p>
                    @endif
                    <p class="text-xs text-gray-400 mt-1">
                        {{ $sprint->start_date?->format('d M Y') ?? '—' }} → {{ $sprint->end_date?->format('d M Y') ?? '—' }}
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    @if($sprint->status !== 'active' && !auth()->user()->hasRole('customer'))
                    <form method="POST" action="{{ route('sprints.update', [$project, $sprint]) }}">
                        @csrf @method('PUT')
                        <input type="hidden" name="name" value="{{ $sprint->name }}">
                        <input type="hidden" name="goal" value="{{ $sprint->goal }}">
                        <input type="hidden" name="status" value="active">
                        <button type="submit" class="text-xs text-green-600 hover:text-green-800 font-medium border border-green-300 px-3 py-1.5 rounded-lg">Aktifkan</button>
                    </form>
                    @endif
                    <a href="{{ route('sprints.show', [$project, $sprint]) }}"
                       class="text-xs text-blue-600 hover:text-blue-800 font-medium">Lihat →</a>
                </div>
            </div>

            {{-- Progress --}}
            <div class="flex items-center gap-3">
                <div class="flex-1 bg-gray-100 rounded-full h-2">
                    <div class="h-2 bg-blue-500 rounded-full transition-all" style="width: {{ $pct }}%"></div>
                </div>
                <span class="text-xs text-gray-500 w-16 text-right">{{ $sprint->completedPoints() }}/{{ $sprint->totalPoints() }} pts</span>
            </div>

            {{-- Task count --}}
            <p class="text-xs text-gray-400 mt-2">{{ $sprint->tasks->count() }} task</p>
        </div>
        @empty
        <div class="text-center py-12 text-gray-400">
            <p class="text-3xl mb-3">🏃</p>
            <p class="font-medium text-gray-500">Belum ada sprint. Buat sprint pertama!</p>
        </div>
        @endforelse
    </div>

    {{-- Backlog --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-5 py-4 border-b border-gray-100 flex items-center justify-between">
            <h3 class="font-semibold text-gray-800">Backlog ({{ $backlog->count() }} task)</h3>
        </div>
        @if($backlog->isEmpty())
        <div class="text-center py-8 text-gray-400">
            <p>Tidak ada task di backlog.</p>
        </div>
        @else
        <div class="divide-y divide-gray-100">
            @foreach($backlog as $task)
            <div class="flex items-center gap-3 px-5 py-3">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-gray-800">{{ $task->title }}</p>
                    <p class="text-xs text-gray-400">{{ $task->milestone?->title ?? 'No Milestone' }} · {{ $task->assignee?->name ?? 'Unassigned' }}</p>
                </div>
                <span class="text-xs text-gray-500">{{ $task->story_points ?? '?' }} pts</span>
                @if(!auth()->user()->hasRole('customer') && $sprints->where('status','active')->first())
                <form method="POST" action="{{ route('sprints.tasks.add', [$project, $sprints->where('status','active')->first()]) }}">
                    @csrf
                    <input type="hidden" name="task_id" value="{{ $task->id }}">
                    <button type="submit" class="text-xs text-blue-600 hover:text-blue-800 border border-blue-300 px-2 py-1 rounded">+ Sprint</button>
                </form>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>
@endsection
