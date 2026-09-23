@extends('layouts.app')
@section('title', 'Aktivitas Kerja — Task')
@section('page-title', 'Aktivitas Kerja')

@section('content')
@php
    $pc  = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
    $pdot = ['low'=>'bg-green-400','medium'=>'bg-yellow-400','high'=>'bg-orange-500','urgent'=>'bg-red-500'];
    $sc  = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
    $sdot = ['todo'=>'bg-gray-400','in_progress'=>'bg-blue-500','review'=>'bg-purple-500','done'=>'bg-green-500'];
    $plb = ['low'=>'border-l-green-400','medium'=>'border-l-yellow-400','high'=>'border-l-orange-400','urgent'=>'border-l-red-500'];
    $columns = ['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'];
    $columnHeader = [
        'todo'        => 'bg-gray-50 border-gray-200',
        'in_progress' => 'bg-blue-50 border-blue-200',
        'review'      => 'bg-purple-50 border-purple-200',
        'done'        => 'bg-green-50 border-green-200',
    ];
    $columnDot = [
        'todo'        => 'bg-gray-400',
        'in_progress' => 'bg-blue-500',
        'review'      => 'bg-purple-500',
        'done'        => 'bg-green-500',
    ];
    $taskStoreUrls = $projects->mapWithKeys(fn ($p) => [$p->id => route('tasks.store', $p)]);
@endphp
<div class="py-4" x-data="{ view: 'list', showAddTask: false, addTaskProject: '' }">
    @include('partials.work-activity-tabs', ['headerStats' => [
        ['label' => 'Total Tugas', 'value' => $totalTasks, 'dot' => 'bg-blue-500'],
        ['label' => 'Selesai Minggu Ini', 'value' => $completedThisWeek, 'dot' => 'bg-green-500'],
    ]])

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Status</option>
                @foreach($columns as $s => $sl)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $sl }}</option>
                @endforeach
            </select>
            <select name="priority" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Prioritas</option>
                @foreach(['low','medium','high','urgent'] as $p)
                    <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ ucfirst($p) }}</option>
                @endforeach
            </select>
            <select name="assignee" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Semua Assignee</option>
                @foreach($assignees as $a)
                    <option value="{{ $a->id }}" {{ (string) request('assignee') === (string) $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                @endforeach
            </select>
            <select name="project" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Filter Proyek</option>
                @foreach($projects as $p)
                    <option value="{{ $p->id }}" {{ (string) request('project') === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                @endforeach
            </select>
            @if(request('status') || request('priority') || request('assignee') || request('project'))
                <a href="{{ route('tasks.all') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2">✕ Reset</a>
            @endif
        </form>

        <div class="flex items-center gap-2 shrink-0">
            {{-- View toggle --}}
            <div class="flex items-center gap-1 bg-gray-100 rounded-lg p-1">
                <button @click="view='list'" :class="view==='list' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                        class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                    List
                </button>
                <button @click="view='kanban'" :class="view==='kanban' ? 'bg-white shadow-sm text-gray-800' : 'text-gray-500'"
                        class="flex items-center gap-1.5 text-xs font-medium px-3 py-1.5 rounded-md transition-all">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    Kanban
                </button>
            </div>

            @if($projects->isNotEmpty())
            <button @click="showAddTask = true; addTaskProject = '{{ $projects->first()->id }}'"
                    class="flex items-center gap-1.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 px-3.5 py-2 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Tambah Task
            </button>
            @endif
        </div>
    </div>

    {{-- ===== LIST VIEW ===== --}}
    <div x-show="view==='list'" x-cloak class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
                    <th class="px-4 py-3 text-left">Milestone</th>
                    <th class="px-4 py-3 text-left">Sprint</th>
                    <th class="px-4 py-3 text-left">Tipe</th>
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
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs">
                        <span class="flex items-center gap-2">
                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ $pdot[$task->priority] ?? 'bg-gray-300' }}"></span>
                            <span class="truncate">{{ $task->title }}</span>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($task->project)
                        <span class="badge bg-gray-100 text-gray-600 text-xs">{{ $task->project->name }}</span>
                        @else — @endif
                    </td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $task->milestone->title ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($task->sprint)
                        <span class="badge bg-teal-50 text-teal-700 text-xs">{{ $task->sprint->name }}</span>
                        @else <span class="text-gray-300 text-xs">—</span> @endif
                    </td>
                    <td class="px-4 py-3">
                        @if($task->recurring_definition_id)
                        <span class="badge bg-amber-100 text-amber-700">Recurring</span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
                    <td class="px-4 py-3">
                        <span class="badge {{ $pc[$task->priority] ?? '' }}">{{ ucfirst($task->priority) }}</span>
                    </td>
                    <td class="px-4 py-3">
                        <span class="inline-flex items-center gap-1.5 text-xs font-medium {{ $sc[$task->status] ?? '' }} px-2 py-1 rounded-full">
                            <span class="w-1.5 h-1.5 rounded-full {{ $sdot[$task->status] ?? 'bg-gray-400' }}"></span>
                            {{ ucfirst(str_replace('_',' ',$task->status)) }}
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        @if($task->assignee)
                        <span class="flex items-center gap-1.5">
                            <span class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-[10px] font-bold flex items-center justify-center shrink-0">
                                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                            </span>
                            <span class="text-gray-600 text-xs truncate">{{ $task->assignee->name }}</span>
                        </span>
                        @else <span class="text-gray-300 text-xs">—</span> @endif
                    </td>
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
                <tr><td colspan="10" class="px-4 py-8 text-center text-gray-400">
                    {{ request('status') || request('priority') || request('assignee') || request('project') ? 'Tidak ada task sesuai filter.' : 'Belum ada task.' }}
                </td></tr>
                @endforelse
            </tbody>
        </table>
        <div class="px-4 py-3 border-t border-gray-100 flex items-center justify-between gap-3 flex-wrap">
            <p class="text-xs text-gray-500">
                @if($tasks->total() > 0)
                    Showing {{ $tasks->firstItem() }} to {{ $tasks->lastItem() }} of {{ $tasks->total() }} results
                @else
                    No results
                @endif
            </p>
            <div class="flex items-center gap-3 flex-wrap">
                <x-per-page />
                @if($tasks->hasPages())
                {{ $tasks->links() }}
                @endif
            </div>
        </div>
    </div>

    {{-- ===== KANBAN VIEW ===== --}}
    <div x-show="view==='kanban'" x-cloak>
        @if($kanbanTruncated)
        <p class="text-xs text-amber-600 bg-amber-50 border border-amber-200 rounded-lg px-3 py-2 mb-3">
            Menampilkan {{ $kanbanTasks->count() }} task terbaru sesuai filter. Persempit dengan filter status/prioritas untuk melihat task lain.
        </p>
        @endif
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" id="kanban-board">
            @foreach($columns as $status => $label)
            @php $colTasks = $kanbanTasks->where('status', $status); @endphp
            <div class="flex flex-col min-h-64" data-status="{{ $status }}">
                <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl border border-b-0 {{ $columnHeader[$status] }}">
                    <span class="w-2.5 h-2.5 rounded-full {{ $columnDot[$status] }}"></span>
                    <span class="text-sm font-semibold text-gray-700">{{ $label }}</span>
                    <span class="ml-auto bg-white text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full border border-gray-200 kanban-count"
                          id="count-{{ $status }}">{{ $colTasks->count() }}</span>
                </div>

                <div class="flex-1 border border-t-0 border-gray-200 rounded-b-xl bg-gray-50 p-2 space-y-2 min-h-24 kanban-col"
                     data-status="{{ $status }}"
                     data-status-label="{{ $label }}"
                     ondragover="event.preventDefault(); this.classList.add('ring-2','ring-blue-400','ring-inset')"
                     ondragleave="this.classList.remove('ring-2','ring-blue-400','ring-inset')"
                     ondrop="handleDrop(event, '{{ $status }}', '{{ $label }}')">

                    @forelse($colTasks as $task)
                    @php
                        $overdue = $task->isOverdue();
                        $days    = $task->daysRemaining();
                        $pl      = $plb[$task->priority] ?? 'border-l-gray-300';
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200 border-l-4 {{ $pl }} p-3 hover:shadow-sm transition-shadow cursor-grab active:cursor-grabbing select-none kanban-card"
                         draggable="true"
                         data-task-id="{{ $task->id }}"
                         data-status="{{ $status }}"
                         data-move-url="{{ $task->project ? route('tasks.update', [$task->project, $task]) : '' }}"
                         ondragstart="handleDragStart(event)"
                         ondragend="handleDragEnd(event)">

                        @if($task->project)
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-blue-500 block mb-1 truncate">{{ $task->project->name }}</span>
                        @endif

                        <a href="{{ $task->project ? route('tasks.show', [$task->project, $task]) : '#' }}"
                           class="text-sm font-medium text-gray-800 hover:text-blue-600 leading-snug block mb-2"
                           draggable="false">{{ $task->title }}</a>

                        <div class="flex items-center gap-1.5 flex-wrap mb-2">
                            <span class="text-xs px-1.5 py-0.5 rounded {{ $pc[$task->priority] ?? 'bg-gray-100 text-gray-600' }}">{{ ucfirst($task->priority) }}</span>
                            @if($overdue)
                                <span class="text-xs px-1.5 py-0.5 rounded bg-red-100 text-red-600">Overdue</span>
                            @endif
                        </div>

                        @if($task->assignee)
                        <div class="flex items-center gap-1.5 mb-1.5">
                            <div class="w-5 h-5 rounded-full bg-blue-100 text-blue-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                {{ strtoupper(substr($task->assignee->name, 0, 1)) }}
                            </div>
                            <span class="text-xs text-gray-500">{{ $task->assignee->name }}</span>
                        </div>
                        @endif

                        @if($task->due_date)
                        <div class="flex items-center gap-1 text-xs {{ $overdue ? 'text-red-500' : 'text-gray-400' }}">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $task->due_date->format('d M Y') }}
                            @if($days !== null)
                                · @if($overdue) {{ abs($days) }}h lalu @elseif($days === 0) Hari ini @else {{ $days }}h lagi @endif
                            @endif
                        </div>
                        @endif

                        @if($task->estimated_hours)
                        @php $pct = $task->timeProgressPercent(); @endphp
                        <div class="mt-2">
                            <div class="w-full bg-gray-100 rounded-full h-1 overflow-hidden">
                                <div class="h-1 rounded-full {{ $pct >= 100 ? 'bg-red-400' : 'bg-blue-400' }}"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                        @endif
                    </div>
                    @empty
                    <div class="py-6 text-center text-xs text-gray-400 kanban-empty">Tidak ada task</div>
                    @endforelse
                </div>
            </div>
            @endforeach
        </div>

        <div id="kanban-toast" class="fixed bottom-4 right-4 bg-gray-800 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg hidden transition-all z-50"></div>
    </div>

    {{-- ===== SUMMARY CARDS ===== --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-5">
        {{-- Sprint progress --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            @if($nearestSprint)
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide">Progres {{ $nearestSprint->name }}</p>
                    <span class="text-xs font-semibold text-blue-600">{{ $nearestSprint->progress_percent }}% Selesai</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden mb-2.5">
                    <div class="h-2 rounded-full bg-blue-500" style="width: {{ $nearestSprint->progress_percent }}%"></div>
                </div>
                <div class="flex items-center justify-between text-xs text-gray-500">
                    <span>{{ $nearestSprint->tasks_done }} dari {{ $nearestSprint->tasks_total }} task selesai</span>
                    @if($nearestSprint->days_left !== null)
                        <span>{{ $nearestSprint->days_left > 0 ? 'Sisa '.$nearestSprint->days_left.' hari' : ($nearestSprint->days_left === 0 ? 'Berakhir hari ini' : 'Lewat tenggat') }}</span>
                    @endif
                </div>
            @else
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Sprint Aktif</p>
                <p class="text-sm text-gray-400">Tidak ada sprint aktif saat ini.</p>
            @endif
        </div>

        {{-- Team workload --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Total Beban Tim</p>
                <p class="text-2xl font-semibold text-gray-800">{{ $activePicCount }} <span class="text-sm font-medium text-gray-400">PIC Aktif</span></p>
                <p class="text-xs text-gray-500 mt-1">Rata-rata {{ $avgTasksPerPic }} tugas / orang</p>
            </div>
            <div class="w-11 h-11 rounded-xl bg-teal-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
        </div>

        {{-- Next milestone --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4 flex items-center justify-between">
            <div class="min-w-0">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-2">Milestone Berikutnya</p>
                @if($nextMilestone)
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ $nextMilestone->title }}</p>
                    <p class="text-xs text-gray-500 mt-1 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Target: {{ $nextMilestone->due_date->format('d M Y') }}
                    </p>
                @else
                    <p class="text-sm text-gray-400">Tidak ada milestone mendatang.</p>
                @endif
            </div>
            <div class="w-11 h-11 rounded-xl bg-amber-50 flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3v18h18M7 15l4-4 3 3 5-6"/></svg>
            </div>
        </div>
    </div>

    {{-- ===== ADD TASK MODAL ===== --}}
    @if($projects->isNotEmpty())
    <div x-show="showAddTask" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" style="background:rgba(15,23,42,0.5)"
         @keydown.escape.window="showAddTask=false">
        <div @click.outside="showAddTask=false" x-show="showAddTask"
             x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
             class="bg-white rounded-xl shadow-xl w-full max-w-md">
            <form method="POST" :action="@js($taskStoreUrls)[addTaskProject]" class="p-5">
                @csrf
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-base font-semibold text-gray-800">Tambah Task Baru</h3>
                    <button type="button" @click="showAddTask=false" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Proyek</label>
                        <select x-model="addTaskProject" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" required>
                            @foreach($projects as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Judul Task</label>
                        <input type="text" name="title" required maxlength="255"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="Misal: Perbaikan tombol submit">
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Assignee</label>
                            <select name="assigned_to" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">Belum ditugaskan</option>
                                @foreach($assignees as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1">Prioritas</label>
                            <select name="priority" class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500 mb-1">Due Date</label>
                        <input type="date" name="due_date"
                               class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div class="flex items-center justify-end gap-2 mt-5">
                    <button type="button" @click="showAddTask=false" class="text-sm font-medium text-gray-500 hover:text-gray-700 px-3 py-2">Batal</button>
                    <button type="submit" class="text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 px-4 py-2 rounded-lg">Simpan Task</button>
                </div>
            </form>
        </div>
    </div>
    @endif
</div>

@push('scripts')
<script>
(function() {
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;
    let dragging = null;

    window.handleDragStart = function(e) {
        dragging = e.currentTarget;
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', dragging.dataset.taskId);
        setTimeout(() => dragging.classList.add('opacity-40'), 0);
    };

    window.handleDragEnd = function(e) {
        if (dragging) dragging.classList.remove('opacity-40');
        dragging = null;
        document.querySelectorAll('.kanban-col').forEach(c =>
            c.classList.remove('ring-2','ring-blue-400','ring-inset'));
    };

    window.handleDrop = function(e, newStatus, newStatusLabel) {
        e.preventDefault();
        const col = e.currentTarget;
        col.classList.remove('ring-2','ring-blue-400','ring-inset');

        const card = dragging || document.querySelector(`.kanban-card[data-task-id="${e.dataTransfer.getData('text/plain')}"]`);
        if (!card) return;

        const oldStatus = card.dataset.status;
        if (oldStatus === newStatus) return;

        const moveUrl = card.dataset.moveUrl;
        if (!moveUrl) return;

        if (newStatus === 'done') {
            Swal.fire({
                title: 'Tandai selesai?',
                input: 'textarea',
                inputLabel: 'Deskripsi penyelesaian',
                inputPlaceholder: 'Deskripsikan apa yang sudah dikerjakan, hambatan, atau catatan penting...',
                inputValidator: (value) => !value ? 'Deskripsi penyelesaian wajib diisi.' : undefined,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
            }).then((result) => {
                if (result.isConfirmed) {
                    moveCard(card, col, oldStatus, newStatus, newStatusLabel, result.value);
                }
            });
            return;
        }

        moveCard(card, col, oldStatus, newStatus, newStatusLabel, null);
    };

    function moveCard(card, col, oldStatus, newStatus, newStatusLabel, completionNotes) {
        // Optimistic UI
        const emptyEl = col.querySelector('.kanban-empty');
        if (emptyEl) emptyEl.remove();
        col.appendChild(card);
        card.dataset.status = newStatus;

        const oldCol = document.querySelector(`.kanban-col[data-status="${oldStatus}"]`);
        if (oldCol && oldCol.querySelectorAll('.kanban-card').length === 0) {
            oldCol.innerHTML = '<div class="py-6 text-center text-xs text-gray-400 kanban-empty">Tidak ada task</div>';
        }

        document.querySelectorAll('.kanban-col').forEach(c => {
            const cnt = c.querySelectorAll('.kanban-card').length;
            const badge = document.getElementById('count-' + c.dataset.status);
            if (badge) badge.textContent = cnt;
        });

        const body = { status: newStatus };
        if (completionNotes !== null) body.completion_notes = completionNotes;

        fetch(card.dataset.moveUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body)
        })
        .then(r => { if (!r.ok) throw new Error(); return r; })
        .then(() => showToast('✓ Status diperbarui ke ' + newStatusLabel))
        .catch(() => showToast('✕ Gagal update status', true));
    }

    function showToast(msg, err = false) {
        const t = document.getElementById('kanban-toast');
        if (!t) return;
        t.textContent = msg;
        t.className = `fixed bottom-4 right-4 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50 transition-all ${err ? 'bg-red-600' : 'bg-gray-800'}`;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 3000);
    }
})();
</script>
@endpush
@endsection
