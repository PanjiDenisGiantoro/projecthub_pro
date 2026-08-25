@extends('layouts.app')
@section('title', 'Aktivitas Kerja — Task')
@section('page-title', 'Aktivitas Kerja')

@section('content')
@php
    $pc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
    $sc = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
@endphp
<div class="py-4">
    @include('partials.work-activity-tabs')

    {{-- Filter --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $s => $sl)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $sl }}</option>
                @endforeach
            </select>
            <select name="priority" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Prioritas</option>
                @foreach(['low','medium','high','urgent'] as $p)
                    <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- Table --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    <th class="px-4 py-3 text-left">Sprint</th>
                    <th class="px-4 py-3 text-left">Prioritas</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Assignee</th>
                    <th class="px-4 py-3 text-left">Due</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tasks as $task)
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate">{{ $task->title }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $task->project->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $task->sprint->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $pc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $sc[$task->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    </td>
                    <td class="px-4 py-3 text-gray-600">{{ $task->assignee->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs text-gray-500 {{ $task->isOverdue() ? 'text-red-500 font-medium' : '' }}">
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                    </td>
                    <td class="px-4 py-3">
                        @if($task->project)
                        <a href="{{ route('tasks.show', [$task->project, $task]) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium">Detail</a>
                        @endif
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-400">Belum ada task.</td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($tasks->hasPages())
            {{ $tasks->links() }}
            @endif
        </div>
    </div>
</div>
@endsection
