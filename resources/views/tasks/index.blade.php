@extends('layouts.app')
@section('title', 'Tasks — ' . $project->name)
@section('page-title', 'Tasks: ' . $project->name)

@section('content')
<div class="py-4" x-data="{showForm:false}">
    <nav class="text-sm text-gray-500 mb-4">
        <a href="{{ route('projects.index') }}" class="hover:text-blue-600">Proyek</a>
        <span class="mx-2">/</span>
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700">Tasks</span>
    </nav>

    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach(['todo','in_progress','review','done'] as $s)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                @endforeach
            </select>
        </form>
        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Tambah Task'"></span>
        </button>
        @endif
    </div>

    {{-- Add Task Form --}}
    @if(!auth()->user()->hasRole('customer'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-4">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Task Baru</h4>
        <form method="POST" action="{{ route('tasks.store', $project) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Judul *</label>
                    <input type="text" name="title" required placeholder="Judul task..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Assignee</label>
                    <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tidak ditugaskan —</option>
                        @foreach($developers as $dev)
                            <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Milestone</label>
                    <select name="milestone_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">— Tanpa milestone —</option>
                        @foreach($milestones as $m)
                            <option value="{{ $m->id }}">{{ $m->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Prioritas</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['low','medium','high','urgent'] as $p)
                            <option value="{{ $p }}" {{ $p === 'medium' ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Due Date</label>
                    <input type="date" name="due_date" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Estimasi Jam</label>
                    <input type="number" name="estimated_hours" min="1" placeholder="Jam..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" placeholder="Deskripsi task..." class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan Task</button>
        </form>
    </div>
    @endif

    {{-- Kanban / Table toggle --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                <tr>
                    <th class="px-4 py-3 text-left">Task</th>
                    <th class="px-4 py-3 text-left">Prioritas</th>
                    <th class="px-4 py-3 text-left">Assignee</th>
                    <th class="px-4 py-3 text-left">Milestone</th>
                    <th class="px-4 py-3 text-left">Due Date</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse($tasks as $task)
                @php
                    $pc = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
                    $sc = ['todo'=>'bg-gray-100 text-gray-700','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
                    $overdue = $task->due_date && $task->due_date->isPast() && $task->status !== 'done';
                @endphp
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-3">
                        <a href="{{ route('tasks.show', [$project, $task]) }}" class="font-medium text-gray-800 hover:text-blue-600">{{ $task->title }}</a>
                    </td>
                    <td class="px-4 py-3"><span class="badge {{ $pc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span></td>
                    <td class="px-4 py-3 text-gray-600">{{ $task->assignee->name ?? '—' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $task->milestone->title ?? '—' }}</td>
                    <td class="px-4 py-3 text-xs {{ $overdue ? 'text-red-600 font-medium' : 'text-gray-500' }}">
                        {{ $task->due_date?->format('d M Y') ?? '—' }}
                        @if($overdue) <span class="badge bg-red-100 text-red-600">Overdue</span> @endif
                    </td>
                    <td class="px-4 py-3">
                        @if(!auth()->user()->hasRole('customer'))
                        <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}">
                            @csrf @method('PUT')
                            <select name="status" onchange="this.form.submit()" class="text-xs border border-gray-200 rounded px-2 py-1 focus:outline-none {{ $sc[$task->status] ?? '' }}">
                                @foreach(['todo','in_progress','review','done'] as $s)
                                    <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$s)) }}</option>
                                @endforeach
                            </select>
                        </form>
                        @else
                        <span class="badge {{ $sc[$task->status] ?? '' }}">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <a href="{{ route('tasks.show', [$project, $task]) }}" class="text-blue-600 hover:text-blue-800 text-sm">Detail</a>
                    </td>
                </tr>
                @empty
                <tr><td colspan="7" class="px-4 py-8 text-center text-gray-400">Belum ada task.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if($tasks->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">{{ $tasks->links() }}</div>
        @endif
    </div>
</div>
@endsection
