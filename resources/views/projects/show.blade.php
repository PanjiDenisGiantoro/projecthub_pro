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
                ['key' => 'sprints',     'label' => 'Sprints'],
                ['key' => 'files',       'label' => 'Files'],
                ['key' => 'budget',      'label' => 'Budget'],
                ['key' => 'risks',       'label' => 'Risiko'],
                ['key' => 'recurring',   'label' => 'Recurring'],
                ['key' => 'portal',      'label' => 'Portal'],
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
         x-data="{ showAddTask: false, taskView: 'list' }">
        @php
            $tPc  = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
            $tSc  = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
            $tPlb = ['low'=>'border-l-green-400','medium'=>'border-l-yellow-400','high'=>'border-l-orange-400','urgent'=>'border-l-red-500'];
        @endphp

        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button @click="taskView='list'" :class="taskView==='list' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                        class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    List
                </button>
                <button @click="taskView='kanban'" :class="taskView==='kanban' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                        class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Kanban
                </button>
            </div>
            @if(!auth()->user()->hasRole('customer'))
            <button @click="showAddTask = !showAddTask"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Task
            </button>
            @endif
        </div>

        {{-- Add Task Form --}}
        @if(!auth()->user()->hasRole('customer'))
        <div x-show="showAddTask" x-cloak class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-4">
            <form action="{{ route('tasks.store', $project) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    <div class="lg:col-span-2">
                        <input type="text" name="title" placeholder="Judul task *" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="low">Rendah</option>
                            <option value="medium" selected>Sedang</option>
                            <option value="high">Tinggi</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div>
                        <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Assignee —</option>
                            @foreach($developers as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="date" name="start_date" placeholder="Start date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <input type="date" name="due_date" placeholder="Due date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <select name="milestone_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Milestone —</option>
                            @foreach($project->milestones as $ms)
                                <option value="{{ $ms->id }}">{{ $ms->title }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <input type="number" name="estimated_hours" min="1" placeholder="Estimasi (jam)"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">Simpan Task</button>
                    <button type="button" @click="showAddTask = false"
                            class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                </div>
            </form>
        </div>
        @endif

        {{-- LIST VIEW --}}
        <div x-show="taskView==='list'" x-cloak class="space-y-2">
            @forelse($project->tasks as $task)
            @php
                $taskOverdue = $task->isOverdue();
                $taskDays    = $task->daysRemaining();
                $tPl         = $tPlb[$task->priority] ?? 'border-l-gray-300';
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 border-l-4 {{ $tPl }} hover:shadow-sm transition-shadow">
                <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('tasks.show', [$project, $task]) }}"
                               class="font-medium text-gray-800 hover:text-indigo-600 text-sm truncate">{{ $task->title }}</a>
                            <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium {{ $tPc[$task->priority] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($task->priority) }}</span>
                            @if($taskOverdue)
                                <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">Overdue</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-3 mt-1 text-xs text-gray-400 flex-wrap">
                            @if($task->assignee)
                                <span>{{ $task->assignee->name }}</span>
                            @endif
                            @if($task->start_date || $task->due_date)
                                <span class="{{ $taskOverdue ? 'text-red-500' : '' }}">
                                    {{ $task->start_date?->format('d M') ?? '?' }} → {{ $task->due_date?->format('d M Y') ?? '?' }}
                                </span>
                            @endif
                            @if($task->milestone)
                                <span>{{ $task->milestone->title }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="flex items-center gap-2 shrink-0">
                        @if($taskDays !== null)
                            @if($taskOverdue)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ abs($taskDays) }}h lalu</span>
                            @elseif($taskDays <= 3)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">{{ $taskDays === 0 ? 'Hari ini' : $taskDays.'h lagi' }}</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ $taskDays }}h lagi</span>
                            @endif
                        @endif
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $tSc[$task->status] ?? 'bg-gray-100 text-gray-600' }}">
                            {{ ucwords(str_replace('_',' ',$task->status)) }}
                        </span>
                        <a href="{{ route('tasks.show', [$project, $task]) }}" class="text-gray-400 hover:text-indigo-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </a>
                    </div>
                </div>
                @if($task->estimated_hours)
                @php $tPct = $task->timeProgressPercent(); @endphp
                <div class="px-4 pb-3">
                    <div class="flex items-center gap-2">
                        <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                            <div class="h-1.5 rounded-full {{ $tPct >= 100 ? 'bg-red-400' : 'bg-indigo-400' }}" style="width: {{ $tPct }}%"></div>
                        </div>
                        <span class="text-xs text-gray-400">{{ round($task->totalMinutes()/60,1) }}j / {{ $task->estimated_hours }}j</span>
                    </div>
                </div>
                @endif
            </div>
            @empty
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-sm text-gray-400">Belum ada task.</div>
            @endforelse
        </div>

        {{-- KANBAN VIEW --}}
        <div x-show="taskView==='kanban'" x-cloak>
            @php
                $kCols = [
                    'todo'        => ['label' => 'To Do',       'dot' => 'bg-gray-400',   'hdr' => 'bg-gray-50 border-gray-200'],
                    'in_progress' => ['label' => 'In Progress', 'dot' => 'bg-blue-500',   'hdr' => 'bg-blue-50 border-blue-100'],
                    'review'      => ['label' => 'Review',      'dot' => 'bg-purple-500', 'hdr' => 'bg-purple-50 border-purple-100'],
                    'done'        => ['label' => 'Done',        'dot' => 'bg-green-500',  'hdr' => 'bg-green-50 border-green-100'],
                ];
            @endphp
            <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                @foreach($kCols as $kSt => $kCol)
                @php $kTasks = $project->tasks->where('status', $kSt); @endphp
                <div class="flex flex-col min-h-48">
                    <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl border border-b-0 {{ $kCol['hdr'] }}">
                        <span class="w-2.5 h-2.5 rounded-full {{ $kCol['dot'] }}"></span>
                        <span class="text-sm font-semibold text-gray-700">{{ $kCol['label'] }}</span>
                        <span class="ml-auto bg-white text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full border border-gray-200">{{ $kTasks->count() }}</span>
                    </div>
                    <div class="flex-1 border border-t-0 border-gray-200 rounded-b-xl bg-gray-50/80 p-2 space-y-2 min-h-12">
                        @forelse($kTasks as $task)
                        @php
                            $kOver = $task->isOverdue();
                            $kPl   = $tPlb[$task->priority] ?? 'border-l-gray-300';
                        @endphp
                        <div class="bg-white rounded-lg border border-gray-200 border-l-4 {{ $kPl }} p-3 hover:shadow-sm transition-shadow">
                            <a href="{{ route('tasks.show', [$project, $task]) }}"
                               class="text-sm font-medium text-gray-800 hover:text-indigo-600 leading-snug block mb-1.5">{{ $task->title }}</a>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs px-1.5 py-0.5 rounded {{ $tPc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                                @if($kOver)
                                    <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-600">Overdue</span>
                                @endif
                            </div>
                            @if($task->assignee)
                            <div class="flex items-center gap-1.5 mt-2">
                                <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center shrink-0">
                                    {{ strtoupper(substr($task->assignee->name,0,1)) }}
                                </div>
                                <span class="text-xs text-gray-500">{{ $task->assignee->name }}</span>
                            </div>
                            @endif
                            @if($task->due_date)
                            <div class="mt-1.5 text-xs {{ $kOver ? 'text-red-500' : 'text-gray-400' }}">
                                {{ $task->due_date->format('d M Y') }}
                            </div>
                            @endif
                        </div>
                        @empty
                        <div class="py-4 text-center text-xs text-gray-400">Kosong</div>
                        @endforelse
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: MILESTONES
    ============================================================ --}}
    <div x-show="tab === 'milestones'" x-cloak
         x-data="{ showAddMilestone: false }">

        <div class="flex items-center justify-between mb-4">
            <h2 class="text-base font-semibold text-gray-900">Milestones</h2>
            @if(!auth()->user()->hasRole('customer'))
            <button @click="showAddMilestone = !showAddMilestone"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Milestone
            </button>
            @endif
        </div>

        {{-- Add Milestone Form --}}
        @if(!auth()->user()->hasRole('customer'))
        <div x-show="showAddMilestone" x-cloak class="bg-indigo-50 border border-indigo-200 rounded-xl p-4 mb-5">
            <form action="{{ route('milestones.store', $project) }}" method="POST">
                @csrf
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-3">
                    <div class="lg:col-span-2">
                        <input type="text" name="title" placeholder="Nama milestone *" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <input type="date" name="start_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                    <div>
                        <input type="date" name="due_date"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Assignee (PIC)</label>
                        <select name="assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="">— Tidak ditugaskan —</option>
                            @foreach($developers as $dev)
                                <option value="{{ $dev->id }}">{{ $dev->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                            <option value="pending">Pending</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <textarea name="description" rows="2" placeholder="Deskripsi (opsional)"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-xs font-medium rounded-lg hover:bg-indigo-700 transition">Simpan Milestone</button>
                    <button type="button" @click="showAddMilestone = false"
                            class="px-4 py-2 bg-white text-gray-600 text-xs font-medium border border-gray-300 rounded-lg hover:bg-gray-50 transition">Batal</button>
                </div>
            </form>
        </div>
        @endif

        {{-- Milestones Grid --}}
        @php
            $mSc = ['pending'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','completed'=>'bg-green-100 text-green-700'];
        @endphp
        @if($project->milestones->isEmpty())
            <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-sm text-gray-400">Belum ada milestone.</div>
        @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($project->milestones as $ms)
            @php
                $mPct     = $ms->taskProgressPercent();
                $mDays    = $ms->daysRemaining();
                $mOverdue = $ms->isOverdue();
                $mTotal   = $ms->tasks->count();
                $mDone    = $ms->tasks->where('status','done')->count();
                $mInProg  = $ms->tasks->where('status','in_progress')->count();
                $mTodo    = $ms->tasks->where('status','todo')->count();

                // SVG ring
                $r = 28; $circ = round(2 * M_PI * $r, 2);
                $dash = round($mPct / 100 * $circ, 2);

                // Timeline bar
                if ($ms->start_date && $ms->due_date) {
                    $mTotalDays   = max(1, $ms->start_date->diffInDays($ms->due_date));
                    $mElapsed     = min($mTotalDays, max(0, $ms->start_date->diffInDays(now())));
                    $mTimelinePct = round($mElapsed / $mTotalDays * 100);
                } else {
                    $mTimelinePct = 0;
                }
            @endphp
            <div class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition-shadow">
                {{-- Header --}}
                <div class="flex items-start gap-4 mb-4">
                    {{-- SVG progress ring --}}
                    <div class="shrink-0">
                        <svg width="68" height="68" viewBox="0 0 68 68">
                            <circle cx="34" cy="34" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="6"/>
                            <circle cx="34" cy="34" r="{{ $r }}" fill="none"
                                    stroke="{{ $mPct >= 100 ? '#22c55e' : ($mOverdue ? '#ef4444' : '#6366f1') }}"
                                    stroke-width="6"
                                    stroke-dasharray="{{ $dash }} {{ $circ }}"
                                    stroke-dashoffset="{{ round($circ / 4, 2) }}"
                                    stroke-linecap="round"/>
                            <text x="34" y="34" text-anchor="middle" dy="0.35em"
                                  font-size="13" font-weight="700"
                                  fill="{{ $mPct >= 100 ? '#16a34a' : ($mOverdue ? '#dc2626' : '#4f46e5') }}">{{ $mPct }}%</text>
                        </svg>
                    </div>

                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 flex-wrap mb-1">
                            <h3 class="text-sm font-semibold text-gray-800">{{ $ms->title }}</h3>
                            <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium {{ $mSc[$ms->status ?? 'pending'] ?? 'bg-gray-100 text-gray-600' }}">
                                {{ ucwords(str_replace('_', ' ', $ms->status ?? 'pending')) }}
                            </span>
                            @if($mOverdue)
                                <span class="inline-flex px-1.5 py-0.5 rounded text-xs font-medium bg-red-100 text-red-600">Overdue</span>
                            @endif
                        </div>

                        {{-- Task breakdown --}}
                        <div class="flex items-center gap-2 text-xs flex-wrap">
                            @if($mTotal > 0)
                            <span class="flex items-center gap-1 text-gray-500">
                                <span class="w-2 h-2 rounded-full bg-green-500"></span> {{ $mDone }} selesai
                            </span>
                            <span class="flex items-center gap-1 text-gray-500">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span> {{ $mInProg }} progress
                            </span>
                            <span class="flex items-center gap-1 text-gray-500">
                                <span class="w-2 h-2 rounded-full bg-gray-300"></span> {{ $mTodo }} todo
                            </span>
                            @else
                            <span class="text-gray-400">Belum ada task</span>
                            @endif
                        </div>

                        {{-- Assignee --}}
                        @if($ms->assignee)
                        <div class="flex items-center gap-1.5 mt-1.5">
                            <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                {{ strtoupper(substr($ms->assignee->name, 0, 1)) }}
                            </div>
                            <span class="text-xs text-gray-500">{{ $ms->assignee->name }}</span>
                        </div>
                        @endif

                        {{-- Days remaining --}}
                        @if($mDays !== null)
                        <div class="mt-1.5">
                            @if($mOverdue)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-red-100 text-red-600">{{ abs($mDays) }} hari terlambat</span>
                            @elseif($mDays === 0)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">Deadline hari ini!</span>
                            @elseif($mDays <= 7)
                                <span class="text-xs font-semibold px-2 py-0.5 rounded-full bg-orange-100 text-orange-600">{{ $mDays }} hari lagi</span>
                            @else
                                <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-500">{{ $mDays }} hari lagi</span>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Task mini progress bar --}}
                @if($mTotal > 0)
                <div class="mb-3">
                    <div class="w-full h-2 bg-gray-100 rounded-full overflow-hidden flex">
                        @if($mDone > 0)
                        <div class="h-full bg-green-500 transition-all" style="width: {{ round($mDone/$mTotal*100) }}%"></div>
                        @endif
                        @if($mInProg > 0)
                        <div class="h-full bg-blue-400 transition-all" style="width: {{ round($mInProg/$mTotal*100) }}%"></div>
                        @endif
                    </div>
                    <div class="text-xs text-gray-400 mt-1">{{ $mDone }}/{{ $mTotal }} task selesai</div>
                </div>
                @endif

                {{-- Timeline --}}
                @if($ms->start_date && $ms->due_date)
                <div class="pt-3 border-t border-gray-100">
                    <div class="flex justify-between text-xs text-gray-400 mb-1">
                        <span>{{ $ms->start_date->format('d M') }}</span>
                        <span class="{{ $mOverdue ? 'text-red-500 font-medium' : '' }}">{{ $ms->due_date->format('d M Y') }}</span>
                    </div>
                    <div class="w-full h-2.5 bg-gray-100 rounded-full overflow-hidden relative">
                        <div class="h-2.5 rounded-full {{ $mOverdue ? 'bg-red-400' : 'bg-indigo-400' }} transition-all"
                             style="width: {{ $mTimelinePct }}%"></div>
                    </div>
                </div>
                @elseif($ms->due_date)
                <div class="pt-3 border-t border-gray-100 text-xs text-gray-400">
                    Due: {{ $ms->due_date->format('d M Y') }}
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
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
            <a href="{{ route('kb.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Buka Knowledge Base &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: TIMESHEET
    ============================================================ --}}
    <div x-show="tab === 'timesheet'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Timesheet</h2>
            <div class="flex flex-wrap gap-3 mb-4">
                <a href="{{ route('projects.timesheet', $project) }}"
                   class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                    Buka Timesheet &rarr;
                </a>
                <a href="{{ route('export.timesheet.excel', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-lg hover:bg-green-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export Excel
                </a>
                <a href="{{ route('export.timesheet.pdf', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-lg hover:bg-red-700 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Export PDF
                </a>
                <a href="{{ route('export.report.pdf', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">
                    Laporan PDF
                </a>
                <a href="{{ route('export.report.excel', $project) }}"
                   class="inline-flex items-center gap-1.5 px-4 py-2 bg-gray-700 text-white text-sm font-medium rounded-lg hover:bg-gray-800 transition">
                    Laporan Excel
                </a>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: SPRINTS
    ============================================================ --}}
    <div x-show="tab === 'sprints'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Sprint Planning</h2>
            <a href="{{ route('sprints.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Kelola Sprints &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: FILES
    ============================================================ --}}
    <div x-show="tab === 'files'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">File Manager</h2>
            <a href="{{ route('project.files.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Buka File Manager &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: BUDGET
    ============================================================ --}}
    <div x-show="tab === 'budget'" x-cloak>
        @php
            $budgetUsed = $project->totalExpenses();
            $budgetPct  = $project->budgetUsedPercent();
        @endphp
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Budget Tracking</h2>
                <a href="{{ route('budget.index', $project) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Kelola →</a>
            </div>
            @if($project->budget)
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="bg-gray-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Total Budget</p>
                    <p class="font-semibold text-gray-800 text-sm">Rp {{ number_format($project->budget, 0, ',', '.') }}</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Terpakai</p>
                    <p class="font-semibold text-red-600 text-sm">Rp {{ number_format($budgetUsed, 0, ',', '.') }}</p>
                </div>
                <div class="bg-green-50 rounded-lg p-3">
                    <p class="text-xs text-gray-400 mb-0.5">Sisa</p>
                    <p class="font-semibold text-green-600 text-sm">Rp {{ number_format($project->budget - $budgetUsed, 0, ',', '.') }}</p>
                </div>
            </div>
            <div>
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span>Penggunaan anggaran</span>
                    <span class="{{ $budgetPct >= 90 ? 'text-red-600 font-bold' : '' }}">{{ $budgetPct }}%</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2">
                    <div class="h-2 rounded-full {{ $budgetPct >= 90 ? 'bg-red-500' : ($budgetPct >= 70 ? 'bg-yellow-500' : 'bg-blue-500') }}"
                         style="width:{{ min(100,$budgetPct) }}%"></div>
                </div>
            </div>
            @else
            <p class="text-sm text-gray-400">Budget belum diset. <a href="{{ route('budget.index', $project) }}" class="text-indigo-600 hover:underline">Kelola anggaran →</a></p>
            @endif
        </div>
    </div>

    {{-- ============================================================
         TAB: RISKS
    ============================================================ --}}
    <div x-show="tab === 'risks'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <div class="flex items-start justify-between mb-4">
                <h2 class="text-base font-semibold text-gray-900">Risk Register</h2>
                <a href="{{ route('risks.index', $project) }}"
                   class="text-sm text-indigo-600 hover:text-indigo-800 font-medium">Lihat Semua →</a>
            </div>
            @php
                $openRisks = $project->risks()->where('status','open')->count();
                $highRisks = $project->risks()->where('status','open')->whereRaw('probability * impact >= 8')->count();
            @endphp
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-gray-800">{{ $project->risks()->count() }}</p>
                    <p class="text-xs text-gray-400">Total Risiko</p>
                </div>
                <div class="bg-yellow-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-yellow-600">{{ $openRisks }}</p>
                    <p class="text-xs text-gray-400">Terbuka</p>
                </div>
                <div class="bg-red-50 rounded-lg p-3 text-center">
                    <p class="text-2xl font-bold text-red-600">{{ $highRisks }}</p>
                    <p class="text-xs text-gray-400">High/Critical</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         TAB: RECURRING
    ============================================================ --}}
    <div x-show="tab === 'recurring'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Recurring Tasks</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $project->recurringTasks()->where('is_active', true)->count() }} recurring task aktif.</p>
            <a href="{{ route('recurring.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Kelola Recurring Tasks &rarr;
            </a>
        </div>
    </div>

    {{-- ============================================================
         TAB: PORTAL
    ============================================================ --}}
    <div x-show="tab === 'portal'" x-cloak>
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-6">
            <h2 class="text-base font-semibold text-gray-900 mb-4">Client Portal</h2>
            <p class="text-sm text-gray-500 mb-4">{{ $project->portalTokens()->count() }} portal link dibuat. Bagikan link khusus kepada klien untuk melihat progress proyek.</p>
            <a href="{{ route('portal.index', $project) }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-lg hover:bg-indigo-700 transition">
                Kelola Portal Link &rarr;
            </a>
        </div>
    </div>

</div>
@endsection
