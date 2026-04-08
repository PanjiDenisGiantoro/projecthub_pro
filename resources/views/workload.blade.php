@extends('layouts.app')
@section('title', 'Team Workload')
@section('page-title', 'Team Workload')

@section('content')
<div class="py-4">
    <p class="text-sm text-gray-500 mb-6">Distribusi task aktif per developer (status: Todo & In Progress)</p>

    @forelse($developers as $dev)
    @php
        $taskCount = $dev->assignedTasks->count();
        $capacity = 8; // max tasks per developer
        $pct = $capacity > 0 ? min(100, round($taskCount / $capacity * 100)) : 0;
        $barColor = $pct >= 100 ? 'bg-red-500' : ($pct >= 75 ? 'bg-orange-400' : ($pct >= 50 ? 'bg-yellow-400' : 'bg-green-500'));
        $labelColor = $pct >= 100 ? 'text-red-600' : ($pct >= 75 ? 'text-orange-500' : ($pct >= 50 ? 'text-yellow-600' : 'text-green-600'));
    @endphp
    <div class="bg-white rounded-xl border border-gray-200 p-5 mb-4">
        <div class="flex items-center justify-between mb-3">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-sm">
                    {{ strtoupper(substr($dev->name, 0, 1)) }}
                </div>
                <div>
                    <p class="text-sm font-semibold text-gray-800">{{ $dev->name }}</p>
                    <p class="text-xs text-gray-400">{{ $dev->email }}</p>
                </div>
            </div>
            <div class="text-right">
                <span class="text-2xl font-bold {{ $labelColor }}">{{ $taskCount }}</span>
                <span class="text-xs text-gray-400 ml-1">/ {{ $capacity }} task</span>
            </div>
        </div>

        {{-- Progress bar --}}
        <div class="w-full bg-gray-100 rounded-full h-2.5 mb-4">
            <div class="{{ $barColor }} h-2.5 rounded-full transition-all" style="width: {{ $pct }}%"></div>
        </div>

        {{-- Tasks --}}
        @if($dev->assignedTasks->count())
        <div class="space-y-1.5">
            @foreach($dev->assignedTasks as $task)
            @php
                $sc = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700'];
                $pc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
            @endphp
            <div class="flex items-center justify-between text-sm bg-gray-50 rounded-lg px-3 py-2">
                <div class="flex items-center gap-2 min-w-0">
                    <span class="badge {{ $sc[$task->status] ?? 'bg-gray-100 text-gray-600' }} text-xs flex-shrink-0">
                        {{ ucfirst(str_replace('_', ' ', $task->status)) }}
                    </span>
                    <a href="{{ route('tasks.show', [$task->project, $task]) }}" class="text-gray-700 hover:text-blue-600 truncate font-medium">
                        {{ $task->title }}
                    </a>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0 ml-3">
                    <span class="badge {{ $pc[$task->priority] ?? '' }} text-xs">{{ ucfirst($task->priority) }}</span>
                    <span class="text-xs text-gray-400">{{ $task->project->name }}</span>
                    @if($task->due_date)
                        <span class="text-xs {{ $task->due_date->isPast() ? 'text-red-500 font-medium' : 'text-gray-400' }}">
                            {{ $task->due_date->format('d M') }}
                        </span>
                    @endif
                </div>
            </div>
            @endforeach
        </div>
        @else
        <p class="text-sm text-gray-400 text-center py-2">Tidak ada task aktif.</p>
        @endif
    </div>
    @empty
    <div class="text-center py-16 text-gray-400">
        <p class="text-4xl mb-3">👥</p>
        <p class="text-lg font-medium">Belum ada developer terdaftar.</p>
    </div>
    @endforelse
</div>
@endsection
