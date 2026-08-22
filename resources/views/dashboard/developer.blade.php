@extends('layouts.app')

@section('title', 'Developer Dashboard')

@section('page-title', 'Developer Dashboard')

@section('content')
<div class="space-y-6 pt-4">

    {{-- ============================================================
         STAT CARDS
    ============================================================ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Todo --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Todo</span>
                <div class="w-9 h-9 rounded-lg bg-gray-100 flex items-center justify-center">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-800">{{ $stats['todo'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Tasks belum dimulai</p>
        </div>

        {{-- In Progress --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">In Progress</span>
                <div class="w-9 h-9 rounded-lg bg-blue-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-blue-600">{{ $stats['in_progress'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Sedang dikerjakan</p>
        </div>

        {{-- Done Minggu Ini --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Done Minggu Ini</span>
                <div class="w-9 h-9 rounded-lg bg-green-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-green-600">{{ $stats['done_week'] }}</p>
            <p class="mt-2 text-xs text-gray-500">Diselesaikan 7 hari terakhir</p>
        </div>

        {{-- Jam Minggu Ini --}}
        <div class="bg-white rounded-xl border border-gray-200 shadow-sm p-5">
            <div class="flex items-center justify-between mb-3">
                <span class="text-xs font-medium text-gray-500 uppercase tracking-wide">Jam Minggu Ini</span>
                <div class="w-9 h-9 rounded-lg bg-purple-50 flex items-center justify-center">
                    <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-purple-600">{{ $stats['hours_week'] }}<span class="text-base font-medium text-gray-500">h</span></p>
            <p class="mt-2 text-xs text-gray-500">Total jam kerja</p>
        </div>

    </div>

    {{-- ============================================================
         MY TASKS TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl border border-gray-200 shadow-sm" x-data="{ filter: 'all' }">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between px-5 py-4 border-b border-gray-100 gap-3">
            <h2 class="font-semibold text-gray-800">My Tasks</h2>
            <div class="flex items-center gap-2">
                {{-- Filter buttons --}}
                <div class="flex bg-gray-100 rounded-lg p-1 gap-1">
                    <button @click="filter = 'all'"
                            :class="filter === 'all' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                        Semua
                    </button>
                    <button @click="filter = 'todo'"
                            :class="filter === 'todo' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                        Todo
                    </button>
                    <button @click="filter = 'in_progress'"
                            :class="filter === 'in_progress' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                        In Progress
                    </button>
                    <button @click="filter = 'done'"
                            :class="filter === 'done' ? 'bg-white shadow text-gray-800' : 'text-gray-500 hover:text-gray-700'"
                            class="px-3 py-1 text-xs font-medium rounded-md transition-all">
                        Done
                    </button>
                </div>
                <a href="{{ route('projects.index') }}"
                   class="inline-flex items-center px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-md transition-colors">
                    Lihat Semua
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50">
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Proyek</th>
                        <th class="text-left px-5 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Task</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Priority</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Due Date</th>
                        <th class="text-left px-3 py-3 text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        <th class="px-3 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($my_tasks as $task)
                        @php
                            $priorityClasses = [
                                'critical' => 'bg-red-100 text-red-700',
                                'high'     => 'bg-orange-100 text-orange-700',
                                'medium'   => 'bg-yellow-100 text-yellow-700',
                                'low'      => 'bg-green-100 text-green-700',
                            ];
                            $statusClasses = [
                                'todo'        => 'bg-gray-100 text-gray-600',
                                'in_progress' => 'bg-blue-100 text-blue-700',
                                'review'      => 'bg-purple-100 text-purple-700',
                                'done'        => 'bg-green-100 text-green-700',
                            ];
                            $statusLabels = [
                                'todo'        => 'Todo',
                                'in_progress' => 'In Progress',
                                'review'      => 'Review',
                                'done'        => 'Done',
                            ];
                            $pc = $priorityClasses[$task->priority] ?? 'bg-gray-100 text-gray-600';
                            $sc = $statusClasses[$task->status] ?? 'bg-gray-100 text-gray-600';
                            $sl = $statusLabels[$task->status] ?? ucfirst($task->status);
                            $isOverdue = $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status !== 'done';
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors"
                            x-show="filter === 'all' || filter === '{{ $task->status }}'">
                            <td class="px-5 py-3">
                                <span class="text-xs text-gray-500">{{ $task->project->name ?? '-' }}</span>
                                @if($task->milestone)
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $task->milestone->name }}</p>
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}"
                                   class="font-medium text-gray-800 hover:text-blue-600 line-clamp-1">
                                    {{ $task->title }}
                                </a>
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $pc }}">
                                    {{ ucfirst($task->priority ?? 'medium') }}
                                </span>
                            </td>
                            <td class="px-3 py-3">
                                @if($task->due_date)
                                    <span class="text-xs {{ $isOverdue ? 'text-red-600 font-medium' : 'text-gray-600' }}">
                                        @if($isOverdue)
                                            <svg class="w-3 h-3 inline mr-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                                            </svg>
                                        @endif
                                        {{ \Carbon\Carbon::parse($task->due_date)->format('d M Y') }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $sc }}">
                                    {{ $sl }}
                                </span>
                            </td>
                            <td class="px-3 py-3 text-right">
                                <a href="{{ route('tasks.show', [$task->project_id, $task->id]) }}"
                                   class="text-blue-600 hover:text-blue-800 text-xs font-medium">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-10 text-center">
                                <div class="flex flex-col items-center gap-2 text-gray-400">
                                    <svg class="w-10 h-10 text-gray-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                    </svg>
                                    <p class="text-sm">Tidak ada task yang ditugaskan</p>
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
