@extends('layouts.app')
@section('title', $task->title)
@section('page-title', 'Detail Task')

@section('content')
@php
    $pc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
    $sc = ['todo'=>'bg-gray-100 text-gray-700','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
    $user = auth()->user();
@endphp
<div class="py-4">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <a href="{{ route('tasks.index', $project) }}" class="hover:text-blue-600">Tasks</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">{{ $task->title }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {{-- Main --}}
        <div class="lg:col-span-2 space-y-5">
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-start justify-between gap-4 mb-4">
                    <h2 class="text-xl font-semibold text-gray-800">{{ $task->title }}</h2>
                    <div class="flex gap-2">
                        <span class="badge {{ $pc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                        <span class="badge {{ $sc[$task->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    </div>
                </div>
                @if($task->description)
                    <p class="text-sm text-gray-600 whitespace-pre-line">{{ $task->description }}</p>
                @endif
            </div>

            {{-- Time Logs --}}
            <div class="bg-white rounded-xl border border-gray-200 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h4 class="font-semibold text-gray-800 text-sm">Time Tracking</h4>
                    <span class="text-sm text-gray-500">
                        Total: <strong>{{ round($task->totalMinutes() / 60, 1) }} jam</strong>
                        @if($task->estimated_hours)
                            / {{ $task->estimated_hours }}j estimasi
                        @endif
                    </span>
                </div>

                @if($user->hasRole(['developer','admin','manager']))
                <div class="flex gap-2 mb-4">
                    @if($runningLog)
                    <form method="POST" action="{{ route('tasks.timelog.store', $task) }}">
                        @csrf
                        <input type="hidden" name="action" value="stop">
                        <button type="submit" class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            ⏹ Stop Timer
                        </button>
                    </form>
                    @else
                    <form method="POST" action="{{ route('tasks.timelog.store', $task) }}">
                        @csrf
                        <input type="hidden" name="action" value="start">
                        <button type="submit" class="inline-flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
                            ▶ Start Timer
                        </button>
                    </form>
                    @endif

                    <form method="POST" action="{{ route('tasks.timelog.store', $task) }}" class="flex gap-2">
                        @csrf
                        <input type="hidden" name="action" value="manual">
                        <input type="number" name="minutes" min="1" placeholder="Menit..." class="w-24 px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white text-sm px-3 py-2 rounded-lg transition-colors">Log Manual</button>
                    </form>
                </div>
                @endif

                <div class="space-y-2">
                    @forelse($task->timeLogs->sortByDesc('started_at') as $log)
                    <div class="flex items-center gap-3 text-sm p-2 rounded-lg {{ $log->is_running ? 'bg-green-50 border border-green-200' : 'bg-gray-50' }}">
                        @if($log->is_running)
                            <span class="w-2 h-2 bg-green-500 rounded-full animate-pulse"></span>
                        @endif
                        <span class="font-medium text-gray-700 w-28">{{ $log->user->name }}</span>
                        <span class="text-gray-500">{{ $log->started_at->format('d M H:i') }}</span>
                        @if($log->ended_at)
                            <span class="text-gray-400">→ {{ $log->ended_at->format('H:i') }}</span>
                            <span class="font-medium text-gray-700 ml-auto">{{ round($log->minutes/60,1) }}j</span>
                        @else
                            <span class="text-green-600 font-medium ml-auto">Running…</span>
                        @endif
                        @if($log->notes)
                            <span class="text-gray-400 text-xs truncate max-w-xs">{{ $log->notes }}</span>
                        @endif
                    </div>
                    @empty
                    <p class="text-sm text-gray-400">Belum ada log waktu.</p>
                    @endforelse
                </div>
            </div>
        </div>

        {{-- Sidebar --}}
        <div class="space-y-4">
            <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-3 text-sm">
                <div><span class="text-gray-500">Proyek:</span> <span class="font-medium">{{ $project->name }}</span></div>
                <div><span class="text-gray-500">Milestone:</span> <span class="font-medium">{{ $task->milestone->title ?? '—' }}</span></div>
                <div><span class="text-gray-500">Assignee:</span> <span class="font-medium">{{ $task->assignee->name ?? '—' }}</span></div>
                <div><span class="text-gray-500">Dibuat oleh:</span> <span class="font-medium">{{ $task->creator->name ?? '—' }}</span></div>
                <div><span class="text-gray-500">Due Date:</span>
                    <span class="{{ $task->due_date && $task->due_date->isPast() && $task->status !== 'done' ? 'text-red-600 font-bold' : 'font-medium' }}">
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                    </span>
                </div>
                <div><span class="text-gray-500">Estimasi:</span> <span class="font-medium">{{ $task->estimated_hours ? $task->estimated_hours.' jam' : '—' }}</span></div>
            </div>

            @if(!$user->hasRole('customer'))
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Update Status</h4>
                <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <select name="status" class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['todo','in_progress','review','done'] as $s)
                            <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-2 rounded-lg transition-colors">OK</button>
                </form>
            </div>
            @endif

            @if($user->hasRole(['admin','manager']) && $task->status !== 'done')
            <div class="bg-white rounded-xl border border-gray-200 p-5">
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Re-assign</h4>
                <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}" class="flex gap-2">
                    @csrf @method('PUT')
                    <select name="assigned_to" class="flex-1 text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tidak ada —</option>
                        @foreach(\App\Models\User::role('developer')->get() as $dev)
                            <option value="{{ $dev->id }}" {{ $task->assigned_to === $dev->id ? 'selected' : '' }}>{{ $dev->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm px-3 py-2 rounded-lg transition-colors">OK</button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
