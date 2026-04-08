@extends('layouts.app')

@section('title', $project->name)

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8"
     x-data="{ tab: 'overview' }">

    {{-- ============================================================
         PROJECT HEADER
    ============================================================ --}}
    @php
        $statusConfig = [
            'draft'     => ['label' => 'Draft',      'class' => 'bg-gray-100 text-gray-700'],
            'active'    => ['label' => 'Aktif',      'class' => 'bg-green-100 text-green-700'],
            'on_hold'   => ['label' => 'On Hold',    'class' => 'bg-yellow-100 text-yellow-700'],
            'completed' => ['label' => 'Selesai',    'class' => 'bg-blue-100 text-blue-700'],
            'cancelled' => ['label' => 'Dibatalkan', 'class' => 'bg-red-100 text-red-700'],
        ];
        $sc = $statusConfig[$project->status] ?? ['label' => ucfirst($project->status), 'class' => 'bg-gray-100 text-gray-700'];
        $progress = $project->progress ?? 0;
    @endphp

    <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div class="flex-1">
                <div class="flex items-center gap-3 flex-wrap mb-2">
                    <a href="{{ route('projects.index') }}"
                       class="text-sm text-gray-400 hover:text-gray-600 transition">Proyek</a>
                    <span class="text-gray-300">/</span>
                    <h1 class="text-xl font-bold text-gray-900">{{ $project->name }}</h1>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $sc['class'] }}">
                        {{ $sc['label'] }}
                    </span>
                </div>
                <p class="text-sm text-gray-500 mb-4">
                    Client: <span class="font-medium text-gray-700">{{ $project->client->name ?? '-' }}</span>
                    &bull;
                    Manager: <span class="font-medium text-gray-700">{{ $project->manager->name ?? '-' }}</span>
                </p>
                {{-- Progress Bar --}}
                <div>
                    <div class="flex justify-between text-xs text-gray-500 mb-1">
                        <span>Progress</span>
                        <span class="font-medium">{{ $progress }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5">
                        <div class="h-2.5 rounded-full bg-indigo-500 transition-all"
                             style="width: {{ min($progress, 100) }}%"></div>
                    </div>
                </div>
            </div>
            @if(auth()->user()->role !== 'customer')
                <a href="{{ route('projects.edit', $project) }}"
                   class="shrink-0 inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg shadow hover:bg-indigo-700 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                    </svg>
                    Edit Proyek
                </a>
            @endif
        </div>
    </div>

    {{-- ============================================================
         TAB NAVIGATION
    ============================================================ --}}
    <div class="border-b border-gray-200 mb-6 overflow-x-auto">
        <nav class="-mb-px flex gap-1 min-w-max">
            @foreach([
                ['key' => 'overview',    'label' => 'Overview'],
                ['key' => 'tasks',       'label' => 'Tasks'],
                ['key' => 'milestones',  'label' => 'Milestones'],
                ['key' => 'tickets',     'label' => 'Tickets'],
                ['key' => 'team',        'label' => 'Tim'],
                ['key' => 'kb',          'label' => 'KB'],
                ['key' => 'timesheet',   'label' => 'Timesheet'],
            ] as $t)
                <button
                    @click="tab = '{{ $t['key'] }}'"
                    :class="tab === '{{ $t['key'] }}'
                        ? 'border-indigo-600 text-indigo-600'
                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="px-4 py-3 text-sm font-medium border-b-2 transition whitespace-nowrap">
                    {{ $t['label'] }}
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ============================================================
         TAB: OVERVIEW
    ============================================================ --}}
    <div x-show="tab === 'overview'" x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Description --}}
            <div class="lg:col-span-2 bg-white rounded-xl shadow-sm border border-gray-200 p-6">
                <h2 class="text-base font-semibold text-gray-900 mb-3">Deskripsi</h2>
                <p class="text-sm text-gray-600 leading-relaxed whitespace-pre-line">
                    {{ $project->description ?: 'Tidak ada deskripsi.' }}
                </p>
            </div>
            {{-- Info Sidebar --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-gray-900">Informasi</h2>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Budget</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $project->budget ? 'Rp ' . number_format($project->budget, 0, ',', '.') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Tanggal Mulai</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $project->start_date ? \Carbon\Carbon::parse($project->start_date)->format('d M Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Tanggal Selesai</p>
                    <p class="text-sm font-medium text-gray-800">
                        {{ $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : '-' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs text-gray-400 uppercase tracking-wide mb-1">Total Anggota</p>
                    <p class="text-sm font-medium text-gray-800">{{ $project->members->count() }} orang</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: TASKS
    ============================================================ --}}
    <div x-show="tab === 'tasks'" x-cloak
         x-data="{ showAddTask: false }">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Tasks</h2>
                @if(auth()->user()->role !== 'customer')
                    <button @click="showAddTask = !showAddTask"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Task
                    </button>
                @endif
            </div>

            {{-- Add Task Form --}}
            @if(auth()->user()->role !== 'customer')
                <div x-show="showAddTask" x-cloak class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
                    <form action="{{ route('tasks.store', $project) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                            <div class="lg:col-span-2">
                                <input type="text" name="title" placeholder="Judul task *" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="low">Prioritas: Rendah</option>
                                    <option value="medium" selected>Prioritas: Sedang</option>
                                    <option value="high">Prioritas: Tinggi</option>
                                    <option value="urgent">Prioritas: Urgent</option>
                                </select>
                            </div>
                            <div>
                                <input type="date" name="due_date" placeholder="Due date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                                Simpan Task
                            </button>
                            <button type="button" @click="showAddTask = false"
                                    class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Tasks Table --}}
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Judul</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Assignee</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Due Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($project->tasks as $task)
                            @php
                                $priorityClass = [
                                    'low'    => 'bg-gray-100 text-gray-600',
                                    'medium' => 'bg-blue-100 text-blue-700',
                                    'high'   => 'bg-orange-100 text-orange-700',
                                    'urgent' => 'bg-red-100 text-red-700',
                                ][$task->priority ?? 'medium'] ?? 'bg-gray-100 text-gray-600';
                                $taskStatusClass = [
                                    'todo'        => 'bg-gray-100 text-gray-600',
                                    'in_progress' => 'bg-blue-100 text-blue-700',
                                    'review'      => 'bg-yellow-100 text-yellow-700',
                                    'done'        => 'bg-green-100 text-green-700',
                                ][$task->status ?? 'todo'] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $task->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $task->assignee->name ?? '-' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $priorityClass }}">
                                        {{ ucfirst($task->priority ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $taskStatusClass }}">
                                        {{ ucwords(str_replace('_', ' ', $task->status ?? '-')) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-sm text-gray-500">
                                    {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d M Y') : '-' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-8 text-center text-sm text-gray-400">
                                    Belum ada task.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: MILESTONES
    ============================================================ --}}
    <div x-show="tab === 'milestones'" x-cloak
         x-data="{ showAddMilestone: false }">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Milestones</h2>
                @if(auth()->user()->role !== 'customer')
                    <button @click="showAddMilestone = !showAddMilestone"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Milestone
                    </button>
                @endif
            </div>

            {{-- Add Milestone Form --}}
            @if(auth()->user()->role !== 'customer')
                <div x-show="showAddMilestone" x-cloak class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
                    <form action="{{ route('milestones.store', $project) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div class="sm:col-span-2">
                                <input type="text" name="title" placeholder="Nama milestone *" required
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <input type="date" name="due_date"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                                Simpan Milestone
                            </button>
                            <button type="button" @click="showAddMilestone = false"
                                    class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Milestones List --}}
            <ul class="divide-y divide-gray-100">
                @forelse($project->milestones as $milestone)
                    @php
                        $mStatusClass = [
                            'pending'     => 'bg-gray-100 text-gray-600',
                            'in_progress' => 'bg-blue-100 text-blue-700',
                            'completed'   => 'bg-green-100 text-green-700',
                        ][$milestone->status ?? 'pending'] ?? 'bg-gray-100 text-gray-600';
                    @endphp
                    <li class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $mStatusClass }}">
                                {{ ucwords(str_replace('_', ' ', $milestone->status ?? 'pending')) }}
                            </span>
                            <span class="text-sm font-medium text-gray-900">{{ $milestone->title }}</span>
                        </div>
                        <span class="text-xs text-gray-400">
                            {{ $milestone->due_date ? \Carbon\Carbon::parse($milestone->due_date)->format('d M Y') : '-' }}
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-gray-400">Belum ada milestone.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ============================================================
         TAB: TICKETS
    ============================================================ --}}
    <div x-show="tab === 'tickets'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Tiket Terkini</h2>
                <div class="flex gap-2">
                    <a href="{{ route('tickets.create', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Buat Tiket
                    </a>
                    <a href="{{ route('tickets.index', $project) }}"
                       class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-50 transition">
                        Lihat Semua
                    </a>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Judul</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Reporter</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Prioritas</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @forelse($recentTickets as $ticket)
                            @php
                                $tPriorityClass = [
                                    'low'    => 'bg-gray-100 text-gray-600',
                                    'medium' => 'bg-blue-100 text-blue-700',
                                    'high'   => 'bg-orange-100 text-orange-700',
                                    'urgent' => 'bg-red-100 text-red-700',
                                ][$ticket->priority ?? 'medium'] ?? 'bg-gray-100 text-gray-600';
                                $tStatusClass = [
                                    'open'        => 'bg-blue-100 text-blue-700',
                                    'in_progress' => 'bg-yellow-100 text-yellow-700',
                                    'resolved'    => 'bg-green-100 text-green-700',
                                    'closed'      => 'bg-gray-100 text-gray-600',
                                ][$ticket->status ?? 'open'] ?? 'bg-gray-100 text-gray-600';
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $ticket->title }}</td>
                                <td class="px-6 py-3 text-sm text-gray-600">{{ $ticket->reporter->name ?? '-' }}</td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tPriorityClass }}">
                                        {{ ucfirst($ticket->priority ?? '-') }}
                                    </span>
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $tStatusClass }}">
                                        {{ ucwords(str_replace('_', ' ', $ticket->status ?? '-')) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-sm text-gray-400">
                                    Belum ada tiket.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: TIM
    ============================================================ --}}
    <div x-show="tab === 'team'" x-cloak
         x-data="{ showAddMember: false }">

        <div class="bg-white rounded-xl shadow-sm border border-gray-200">
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                <h2 class="text-base font-semibold text-gray-900">Anggota Tim</h2>
                @if(auth()->user()->role !== 'customer')
                    <button @click="showAddMember = !showAddMember"
                            class="inline-flex items-center px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Anggota
                    </button>
                @endif
            </div>

            {{-- Add Member Form --}}
            @if(auth()->user()->role !== 'customer')
                <div x-show="showAddMember" x-cloak class="px-6 py-4 bg-indigo-50 border-b border-indigo-100">
                    <form action="{{ route('projects.members.add', $project) }}" method="POST">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-3">
                            <div>
                                <select name="user_id" required
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                    <option value="">-- Pilih Developer --</option>
                                    @foreach($developers as $dev)
                                        <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <input type="text" name="role" placeholder="Role (e.g. Frontend Dev)"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                            <div>
                                <input type="number" name="max_hours" min="0" placeholder="Maks jam/minggu"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                                Tambahkan
                            </button>
                            <button type="button" @click="showAddMember = false"
                                    class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">
                                Batal
                            </button>
                        </div>
                    </form>
                </div>
            @endif

            {{-- Members List --}}
            <ul class="divide-y divide-gray-100">
                @forelse($project->members as $member)
                    <li class="flex items-center justify-between px-6 py-4 hover:bg-gray-50 transition">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs uppercase">
                                {{ substr($member->user->name ?? '?', 0, 2) }}
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $member->user->name ?? '-' }}</p>
                                <p class="text-xs text-gray-500">{{ $member->role ?? '-' }}</p>
                            </div>
                        </div>
                        <span class="text-xs text-gray-400">
                            {{ $member->max_hours ? $member->max_hours . ' jam/minggu' : '-' }}
                        </span>
                    </li>
                @empty
                    <li class="px-6 py-8 text-center text-sm text-gray-400">Belum ada anggota tim.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- ============================================================
         TAB: KB (Knowledge Base)
    ============================================================ --}}
    <div x-show="tab === 'kb'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-3">Knowledge Base</h2>
            <p class="text-sm text-gray-400">Fitur Knowledge Base untuk proyek ini akan segera tersedia.</p>
        </div>
    </div>

    {{-- ============================================================
         TAB: TIMESHEET
    ============================================================ --}}
    <div x-show="tab === 'timesheet'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Timesheet</h2>
            <p class="text-sm text-gray-500 mb-4">
                Lihat detail log waktu seluruh developer pada proyek ini.
            </p>
            <a href="{{ route('projects.timesheet', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Buka Timesheet Lengkap &rarr;
            </a>
        </div>
    </div>

</div>
@endsection
