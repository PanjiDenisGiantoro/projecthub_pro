@extends('layouts.app')
@section('title', 'Tasks — ' . $project->name)
@section('page-title', 'Tasks: ' . $project->name)

@push('head')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@section('content')
@php
    $pc  = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
    $sc  = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
    $pbl = ['todo'=>'bg-gray-400','in_progress'=>'bg-blue-500','review'=>'bg-purple-500','done'=>'bg-green-500'];
    $plb = ['low'=>'border-l-green-400','medium'=>'border-l-yellow-400','high'=>'border-l-orange-400','urgent'=>'border-l-red-500'];
@endphp
<div class="py-4" x-data="{ showForm: false, view: 'list' }">

    <nav class="text-sm text-gray-500 mb-4 flex items-center gap-2">
        <a href="{{ route('projects.index') }}" class="hover:text-blue-600">Proyek</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600">{{ $project->name }}</a>
        <svg class="w-3.5 h-3.5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-gray-700 font-medium">Tasks</span>
    </nav>

    {{-- Toolbar --}}
    <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-5">
        <form method="GET" class="flex gap-2 flex-1 flex-wrap">
            <select name="status" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                <option value="">Semua Status</option>
                @foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $s => $sl)
                    <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $sl }}</option>
                @endforeach
            </select>
            @if(request('status'))
                <a href="{{ route('tasks.index', $project) }}" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2">✕ Reset</a>
            @endif
        </form>

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

        @if(!auth()->user()->hasRole('client'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Tambah Task'"></span>
        </button>
        @endif
    </div>

    {{-- Add Task Form --}}
    @if(!auth()->user()->hasRole('client'))
    <div x-show="showForm" x-cloak class="bg-white rounded-xl border border-blue-200 p-5 mb-5">
        <h4 class="text-sm font-semibold text-gray-700 mb-4">Task Baru</h4>
        <form method="POST" action="{{ route('tasks.store', $project) }}" class="space-y-4">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div class="sm:col-span-2 lg:col-span-2">
                    <label class="block text-xs font-medium text-gray-600 mb-1">Judul *</label>
                    <input type="text" name="title" required placeholder="Judul task..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Prioritas</label>
                    <select name="priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(['low'=>'Low','medium'=>'Medium','high'=>'High','urgent'=>'Urgent'] as $p => $pl)
                            <option value="{{ $p }}" {{ $p === 'medium' ? 'selected' : '' }}>{{ $pl }}</option>
                        @endforeach
                    </select>
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
                    <label class="block text-xs font-medium text-gray-600 mb-1">Estimasi Jam</label>
                    <input type="number" name="estimated_hours" min="1" placeholder="Jam..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Start Date</label>
                    <input type="date" name="start_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1">Due Date</label>
                    <input type="date" name="due_date"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <div>
                <label class="block text-xs font-medium text-gray-600 mb-1">Deskripsi</label>
                <textarea name="description" rows="2" placeholder="Deskripsi task..."
                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-5 py-2 rounded-lg transition-colors">Simpan Task</button>
        </form>
    </div>
    @endif

    {{-- ===== LIST VIEW ===== --}}
    <div x-show="view==='list'" x-cloak class="space-y-2">
        @forelse($tasks as $task)
        @php
            $days     = $task->daysRemaining();
            $overdue  = $task->isOverdue();
            $pl       = $plb[$task->priority] ?? 'border-l-gray-300';
        @endphp
        <div class="bg-white rounded-xl border border-gray-200 border-l-4 {{ $pl }} hover:shadow-sm transition-shadow">
            <div class="px-4 py-3 flex flex-col sm:flex-row sm:items-center gap-3">
                {{-- Title + badges --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <a href="{{ route('tasks.show', [$project, $task]) }}"
                           class="font-medium text-gray-800 hover:text-blue-600 text-sm truncate">{{ $task->title }}</a>
                        <span class="badge {{ $pc[$task->priority] ?? 'bg-gray-100 text-gray-600' }} text-xs">{{ ucfirst($task->priority) }}</span>
                        @if($overdue)
                            <span class="badge bg-red-100 text-red-600 text-xs">Overdue</span>
                        @endif
                    </div>
                    <div class="flex items-center gap-3 mt-1 flex-wrap text-xs text-gray-400">
                        @if($task->milestone)
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21l1.9-5.7a8.5 8.5 0 1 1 3.8 3.8z"/></svg>
                                {{ $task->milestone->title }}
                            </span>
                        @endif
                        @if($task->assignee)
                            <span class="flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $task->assignee->name }}
                            </span>
                        @endif
                        @if($task->start_date || $task->due_date)
                            <span class="flex items-center gap-1 {{ $overdue ? 'text-red-500' : '' }}">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                {{ $task->start_date?->format('d M') ?? '?' }} → {{ $task->due_date?->format('d M Y') ?? '?' }}
                                @if($task->durationDays()) · {{ $task->durationDays() }} hari @endif
                            </span>
                        @endif
                    </div>
                </div>

                {{-- Days remaining badge --}}
                <div class="flex items-center gap-3 shrink-0">
                    @if($days !== null)
                        @if($overdue)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-red-100 text-red-600">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ abs($days) }}h lalu
                            </span>
                        @elseif($days === 0)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-orange-100 text-orange-600">Hari ini</span>
                        @elseif($days <= 3)
                            <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-orange-100 text-orange-600">{{ $days }}h lagi</span>
                        @else
                            <span class="inline-flex items-center gap-1 text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-500">{{ $days }}h lagi</span>
                        @endif
                    @endif

                    {{-- Status select --}}
                    @if(!auth()->user()->hasRole('client'))
                    <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}" class="inline quick-status-form">
                        @csrf @method('PUT')
                        <input type="hidden" name="completion_notes">
                        <select name="status" onchange="handleQuickStatusChange(this)"
                                class="text-xs border-0 rounded-full px-3 py-1.5 font-medium focus:outline-none focus:ring-2 focus:ring-blue-300 cursor-pointer {{ $sc[$task->status] ?? 'bg-gray-100 text-gray-600' }}">
                            @foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $s => $sl)
                                <option value="{{ $s }}" {{ $task->status === $s ? 'selected' : '' }}>{{ $sl }}</option>
                            @endforeach
                        </select>
                    </form>
                    @else
                    <span class="badge {{ $sc[$task->status] ?? '' }} text-xs">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span>
                    @endif

                    <a href="{{ route('tasks.show', [$project, $task]) }}"
                       class="text-gray-400 hover:text-blue-600 transition-colors" title="Detail">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>

            {{-- Time progress bar (if estimated) --}}
            @if($task->estimated_hours)
            @php $pct = $task->timeProgressPercent(); @endphp
            <div class="px-4 pb-3">
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full transition-all {{ $pct >= 100 ? 'bg-red-400' : 'bg-blue-400' }}"
                             style="width: {{ $pct }}%"></div>
                    </div>
                    <span class="text-xs text-gray-400 whitespace-nowrap">
                        {{ round($task->totalMinutes() / 60, 1) }}j / {{ $task->estimated_hours }}j
                    </span>
                </div>
            </div>
            @endif
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-200 px-4 py-10 text-center text-gray-400">
            Belum ada task di proyek ini.
        </div>
        @endforelse

        <div class="mt-4 flex items-center justify-between gap-3 flex-wrap">
            <x-per-page />
            @if($tasks->hasPages())
            {{ $tasks->links() }}
            @endif
        </div>
    </div>

    {{-- ===== KANBAN VIEW ===== --}}
    <div x-show="view==='kanban'" x-cloak class="pt-2">
        @php
            $allTasks = $project->tasks()->with([
                'assignee',
                'members',
                'labels',
                'checklists.items',
                'attachments',
                'milestone',
                'boardColumn',
            ])->withCount(['comments', 'attachments'])->orderBy('sort_order')->get();
        @endphp

        <div id="project-kanban-columns-container"
             class="flex gap-4 overflow-x-auto pb-6 pt-1 items-start min-h-[calc(100vh-320px)] scrollbar-thin">

            @forelse($columns as $col)
            @php
                $colTasks = $allTasks->where('board_column_id', $col->id)->sortBy('sort_order');
            @endphp
            <div class="kanban-column w-80 shrink-0 bg-gray-50/90 dark:bg-gray-850/80 rounded-2xl border border-gray-200/80 dark:border-gray-750 flex flex-col max-h-[calc(100vh-250px)] shadow-xs transition-shadow"
                 data-column-id="{{ $col->id }}"
                 data-column-slug="{{ $col->slug }}">

                {{-- Column Header --}}
                <div class="p-3.5 border-b border-gray-200/70 dark:border-gray-750 flex items-center justify-between gap-2 shrink-0">
                    <div class="flex items-center gap-2 min-w-0">
                        {{-- Drag handle for bucket --}}
                        <span class="bucket-drag-handle cursor-grab active:cursor-grabbing text-gray-300 hover:text-gray-500 dark:text-gray-600 dark:hover:text-gray-400 p-0.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                        </span>

                        {{-- Column dot indicator --}}
                        <span class="w-2.5 h-2.5 rounded-full shrink-0" style="background-color: {{ $col->color ?: '#3b82f6' }}"></span>

                        <h3 class="font-bold text-xs uppercase tracking-wider text-gray-800 dark:text-gray-200 truncate">
                            {{ $col->name }}
                        </h3>

                        <span class="column-counter text-[11px] font-semibold bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-300 border border-gray-200/80 dark:border-gray-700 px-2 py-0.5 rounded-full">
                            {{ $colTasks->count() }}
                        </span>
                    </div>
                </div>

                {{-- Cards Droppable Container --}}
                <div id="project-cards-column-{{ $col->id }}"
                     data-column-id="{{ $col->id }}"
                     class="project-cards-dropzone p-3 space-y-2.5 overflow-y-auto flex-1 min-h-[150px] scrollbar-thin">
                    @foreach($colTasks as $task)
                        @include('sprints._kanban_card', ['task' => $task, 'col' => $col])
                    @endforeach
                </div>

                {{-- Inline Add Task in Bucket --}}
                @if(!auth()->user()->hasRole('client'))
                <div class="p-2.5 border-t border-gray-200/60 dark:border-gray-750 shrink-0"
                     x-data="{ adding: false, taskTitle: '', isSubmitting: false }">
                    <template x-if="!adding">
                        <button type="button"
                                @click="adding = true; $nextTick(() => $refs.inlineInput.focus())"
                                class="w-full py-2 px-3 rounded-xl text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-800 transition flex items-center justify-center gap-1.5 border border-transparent hover:border-gray-200 dark:hover:border-gray-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Tambah Task
                        </button>
                    </template>

                    <template x-if="adding">
                        <div class="space-y-2 bg-white dark:bg-gray-800 p-2.5 rounded-xl border border-blue-200 dark:border-blue-900 shadow-sm">
                            <textarea x-ref="inlineInput"
                                      x-model="taskTitle"
                                      @keydown.enter.prevent="submitInlineTask({{ $col->id }})"
                                      @keydown.escape="adding = false; taskTitle = ''"
                                      rows="2"
                                      placeholder="Tulis judul task dan tekan Enter..."
                                      class="w-full text-xs p-2 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            <div class="flex items-center justify-end gap-1.5">
                                <button type="button"
                                        @click="adding = false; taskTitle = ''"
                                        class="px-2.5 py-1 text-xs text-gray-500 hover:text-gray-700">
                                    Batal
                                </button>
                                <button type="button"
                                        @click="submitInlineTask({{ $col->id }})"
                                        :disabled="isSubmitting || !taskTitle.trim()"
                                        class="px-3 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs disabled:opacity-50">
                                    Tambah
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
                @endif
            </div>
            @empty
            <div class="col-span-full text-center text-sm text-gray-400 py-16 w-full">
                Proyek ini belum memiliki kolom board.
            </div>
            @endforelse
        </div>
    </div>

    {{-- Include Task Detail Modal --}}
    @include('sprints._task_modal')

</div>

@push('scripts')
<script>
(function() {
    const MOVE_URL = '{{ route('tasks.move', [$project, '__ID__']) }}';
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;

    window.handleQuickStatusChange = function(select) {
        const form = select.closest('form');
        if (select.value !== 'done') {
            form.submit();
            return;
        }

        const previousValue = select.dataset.prev || Array.from(select.options).find(o => o.defaultSelected)?.value || 'todo';

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
                form.querySelector('input[name="completion_notes"]').value = result.value;
                form.submit();
            } else {
                select.value = previousValue;
            }
        });
    };

    document.querySelectorAll('.quick-status-form select[name="status"]').forEach(sel => {
        sel.dataset.prev = sel.value;
    });

    // Initialize SortableJS for Project Kanban
    document.addEventListener('DOMContentLoaded', () => {
        const dropzones = document.querySelectorAll('.project-cards-dropzone');
        dropzones.forEach(zone => {
            new Sortable(zone, {
                group: 'project-cards',
                animation: 180,
                ghostClass: 'opacity-30',
                chosenClass: 'scale-[1.02]',
                onEnd: async (evt) => {
                    const card = evt.item;
                    const taskId = card.dataset.taskId;
                    const targetCol = evt.to;
                    const targetColumnId = targetCol.dataset.columnId;

                    const cardElements = Array.from(targetCol.querySelectorAll('.kanban-card'));
                    const order = cardElements.map(el => parseInt(el.dataset.taskId, 10));

                    if (evt.from !== evt.to) {
                        try {
                            await fetch(MOVE_URL.replace('__ID__', taskId), {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': CSRF,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ board_column_id: targetColumnId })
                            });
                        } catch (err) {
                            console.error(err);
                        }
                    }

                    try {
                        await fetch(`/projects/{{ $project->id }}/tasks/reorder`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                order: order,
                                board_column_id: targetColumnId
                            })
                        });
                    } catch (err) {
                        console.error(err);
                    }

                    document.querySelectorAll('.kanban-column').forEach(col => {
                        const count = col.querySelectorAll('.kanban-card').length;
                        const counter = col.querySelector('.column-counter');
                        if (counter) counter.textContent = count;
                    });
                }
            });
        });

        const columnsContainer = document.getElementById('project-kanban-columns-container');
        if (columnsContainer) {
            new Sortable(columnsContainer, {
                handle: '.bucket-drag-handle',
                animation: 180,
                ghostClass: 'opacity-40',
                onEnd: async () => {
                    const colElements = Array.from(columnsContainer.querySelectorAll('.kanban-column'));
                    const colOrder = colElements.map(el => parseInt(el.dataset.columnId, 10));

                    try {
                        await fetch(`/projects/{{ $project->id }}/board-columns/reorder`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ order: colOrder })
                        });
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }
    });

    window.openTask = function(taskId) {
        if (window.openTaskModal) {
            window.openTaskModal(taskId);
        } else {
            window.dispatchEvent(new CustomEvent('open-task-modal', { detail: { taskId } }));
        }
    };

    window.submitInlineTask = async function(columnId) {
        const input = event.target?.closest('div')?.querySelector('textarea');
        const title = (input ? input.value : '').trim();
        if (!title) return;

        try {
            const res = await fetch(`/projects/{{ $project->id }}/tasks`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    title: title,
                    board_column_id: columnId
                })
            });

            if (res.ok) {
                window.location.reload();
            }
        } catch (err) {
            console.error(err);
        }
    };
})();
</script>
@endpush
@endsection
