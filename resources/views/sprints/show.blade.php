@extends('layouts.app')
@section('title', $sprint->name . ' — Sprint Board')
@section('page-title', 'Sprint Board: ' . $sprint->name)

@push('head')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
@endpush

@section('content')
<div class="py-4 space-y-5" x-data="sprintBoardData()">

    {{-- Breadcrumbs Navigation --}}
    <nav class="text-sm text-gray-500 mb-2 flex items-center gap-1.5 flex-wrap">
        <a href="{{ route('projects.show', $project) }}" class="hover:text-blue-600 transition">{{ $project->name }}</a>
        <span class="text-gray-300 dark:text-gray-600">/</span>
        <a href="{{ route('sprints.index', $project) }}" class="hover:text-blue-600 transition">Sprints</a>
        <span class="text-gray-300 dark:text-gray-600">/</span>
        <span class="text-gray-700 dark:text-gray-300 font-medium">{{ $sprint->name }}</span>
    </nav>

    {{-- Sprint Header Summary Card --}}
    @php
        $totalPts = $sprint->totalPoints();
        $donePts = $sprint->completedPoints();
        $pct = $totalPts > 0 ? round(($donePts / $totalPts) * 100) : 0;
        $totalTasks = $sprint->tasks->count();
        $doneTasks = $sprint->tasks->filter(fn($t) => $t->isDone())->count();
        $taskPct = $totalTasks > 0 ? round(($doneTasks / $totalTasks) * 100) : 0;
        $statusColors = [
            'active'    => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-800/40',
            'completed' => 'bg-gray-100 text-gray-700 border-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
            'planned'   => 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-950/40 dark:text-blue-400 dark:border-blue-800/40',
        ];
    @endphp
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-5">
            <div class="space-y-1.5">
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">{{ $sprint->name }}</h2>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full border {{ $statusColors[$sprint->status] ?? $statusColors['planned'] }}">
                        {{ ucfirst($sprint->status) }}
                    </span>
                    @if($sprint->start_date && $sprint->end_date)
                    <span class="text-xs text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-800 px-2.5 py-0.5 rounded-md border border-gray-200/60 dark:border-gray-700/60">
                        {{ $sprint->start_date->format('d M') }} — {{ $sprint->end_date->format('d M Y') }}
                    </span>
                    @endif
                </div>

                @if($sprint->goal)
                <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed max-w-2xl">{{ $sprint->goal }}</p>
                @endif
            </div>

            <div class="flex items-center gap-6 self-start lg:self-auto">
                {{-- Task and Points Stats --}}
                <div class="text-right">
                    <div class="flex items-baseline justify-end gap-1.5">
                        <span class="text-2xl font-black text-blue-600 dark:text-blue-400">{{ $taskPct }}%</span>
                        <span class="text-xs text-gray-400 font-medium">selesai</span>
                    </div>
                    <p class="text-[11px] text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $doneTasks }}/{{ $totalTasks }} task
                        @if($totalPts > 0)
                        • {{ $donePts }}/{{ $totalPts }} pts
                        @endif
                    </p>
                </div>

                {{-- Meeting Actions --}}
                <div class="flex items-center gap-2">
                    @if($sprint->google_meet_link)
                    <a href="{{ $sprint->google_meet_link }}" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-xs transition">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                        Join Meeting
                    </a>
                    @elseif(!auth()->user()->hasRole('client') && $project->google_meet_enabled)
                    <form method="POST" action="{{ route('sprints.meeting.create', [$project, $sprint]) }}">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-xs font-semibold text-blue-700 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/40 border border-blue-200 dark:border-blue-800 rounded-xl hover:bg-blue-100 transition">
                            + Buat Meeting
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>

        {{-- Progress Bar --}}
        <div class="mt-4 bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
            <div class="h-2 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-full transition-all duration-500"
                 style="width: {{ $taskPct }}%"></div>
        </div>
    </div>

    {{-- Board Toolbar (Search & Filter) --}}
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div class="flex items-center gap-2 flex-wrap flex-1">
            {{-- Search Bar --}}
            <div class="relative w-full sm:w-64">
                <input type="text"
                       x-model="searchQuery"
                       @input="filterCards()"
                       placeholder="Cari task di board..."
                       class="w-full text-xs pl-8 pr-3 py-2 bg-white dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-2xs">
                <svg class="w-4 h-4 text-gray-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </div>

            {{-- Filter by Priority --}}
            <select x-model="selectedPriority"
                    @change="filterCards()"
                    class="text-xs bg-white dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 shadow-2xs">
                <option value="">Semua Prioritas</option>
                <option value="urgent">Urgent</option>
                <option value="high">High</option>
                <option value="medium">Medium</option>
                <option value="low">Low</option>
            </select>

            {{-- Filter by Assignee --}}
            <select x-model="selectedMember"
                    @change="filterCards()"
                    class="text-xs bg-white dark:bg-gray-850 border border-gray-200 dark:border-gray-700 rounded-xl px-3 py-2 focus:ring-2 focus:ring-blue-500 shadow-2xs">
                <option value="">Semua Anggota</option>
                @foreach($assignableUsers as $u)
                <option value="{{ $u->id }}">{{ $u->name }}</option>
                @endforeach
            </select>

            <button type="button"
                    x-show="searchQuery || selectedPriority || selectedMember"
                    @click="searchQuery = ''; selectedPriority = ''; selectedMember = ''; filterCards()"
                    x-cloak
                    class="text-xs text-gray-500 hover:text-red-500 font-medium px-2 py-1 transition">
                Reset Filter
            </button>
        </div>

        {{-- Add Bucket Button (Top trigger) --}}
        @if(!auth()->user()->hasRole('client'))
        <button type="button"
                @click="openAddColumnPrompt()"
                class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-semibold text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-850 border border-gray-200 dark:border-gray-700 hover:border-blue-500 rounded-xl shadow-2xs transition shrink-0">
            <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Tambah Kolom
        </button>
        @endif
    </div>

    {{-- Horizontal Scrollable Kanban Board --}}
    <div id="kanban-columns-container"
         class="flex gap-4 overflow-x-auto pb-6 pt-1 items-start min-h-[calc(100vh-320px)] scrollbar-thin">

        @forelse($columns as $col)
        @php
            $colTasks = $sprint->tasks->where('board_column_id', $col->id)->sortBy('sort_order');
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

                {{-- Column Options Dropdown --}}
                @if(!auth()->user()->hasRole('client'))
                <div class="relative" x-data="{ openMenu: false }">
                    <button type="button"
                            @click="openMenu = !openMenu"
                            class="p-1 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-200/60 dark:hover:bg-gray-750 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </button>

                    <div x-show="openMenu"
                         @click.outside="openMenu = false"
                         x-cloak
                         class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1 z-30">
                        <button type="button"
                                @click="renameColumn({{ $col->id }}, '{{ addslashes($col->name) }}'); openMenu = false"
                                class="w-full text-left px-3 py-2 text-xs text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700/50 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Ubah Nama Kolom
                        </button>
                        <button type="button"
                                @click="deleteColumn({{ $col->id }}, '{{ addslashes($col->name) }}'); openMenu = false"
                                class="w-full text-left px-3 py-2 text-xs text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-950/30 flex items-center gap-2">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            Hapus Kolom
                        </button>
                    </div>
                </div>
                @endif
            </div>

            {{-- Cards Droppable Container --}}
            <div id="cards-column-{{ $col->id }}"
                 data-column-id="{{ $col->id }}"
                 class="cards-dropzone p-3 space-y-2.5 overflow-y-auto flex-1 min-h-[150px] scrollbar-thin">
                @foreach($colTasks as $task)
                    @include('sprints._kanban_card', ['task' => $task, 'col' => $col, 'sprint' => $sprint])
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
            <button type="button" @click="openAddColumnPrompt()" class="text-blue-600 font-semibold hover:underline ml-1">Buat kolom pertama</button>
        </div>
        @endforelse

        {{-- Add Bucket Card at End of Board --}}
        @if(!auth()->user()->hasRole('client'))
        <div class="w-80 shrink-0" x-data="{ addingCol: false, colName: '', colColor: '#3b82f6' }">
            <template x-if="!addingCol">
                <button type="button"
                        @click="addingCol = true; $nextTick(() => $refs.colInput.focus())"
                        class="w-full py-3.5 px-4 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800 text-gray-500 dark:text-gray-400 hover:border-blue-400 hover:text-blue-600 dark:hover:text-blue-400 text-xs font-semibold flex items-center justify-center gap-2 transition bg-gray-50/50 dark:bg-gray-900/50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    + Tambah Kolom / Bucket
                </button>
            </template>

            <template x-if="addingCol">
                <div class="bg-white dark:bg-gray-850 p-4 rounded-2xl border border-blue-200 dark:border-blue-900 shadow-md space-y-3">
                    <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200">Kolom / Bucket Baru</h4>
                    <input type="text"
                           x-ref="colInput"
                           x-model="colName"
                           @keydown.enter="createColumn(colName, colColor)"
                           @keydown.escape="addingCol = false; colName = ''"
                           placeholder="Nama kolom (misal: Testing)..."
                           class="w-full text-xs p-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <div class="flex items-center gap-1.5">
                        <template x-for="c in ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#64748b']" :key="c">
                            <button type="button"
                                    @click="colColor = c"
                                    class="w-5 h-5 rounded-full transition-transform"
                                    :style="'background-color:' + c"
                                    :class="colColor === c ? 'ring-2 ring-offset-1 ring-blue-500 scale-110' : 'opacity-80'"></button>
                        </template>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-1">
                        <button type="button" @click="addingCol = false; colName = ''" class="px-3 py-1.5 text-xs text-gray-500 hover:text-gray-700">Batal</button>
                        <button type="button" @click="createColumn(colName, colColor)" class="px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-xs font-semibold shadow-xs">Tambah</button>
                    </div>
                </div>
            </template>
        </div>
        @endif
    </div>

    {{-- Include State-of-the-Art Task Detail Modal --}}
    @include('sprints._task_modal')
</div>

@push('scripts')
<script>
function sprintBoardData() {
    return {
        searchQuery: '',
        selectedPriority: '',
        selectedMember: '',

        init() {
            this.$nextTick(() => {
                this.initDragAndDrop();
            });
        },

        initDragAndDrop() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            // 1. Setup Card Sorting across Columns
            const dropzones = document.querySelectorAll('.cards-dropzone');
            dropzones.forEach(zone => {
                new Sortable(zone, {
                    group: 'sprint-cards',
                    animation: 180,
                    ghostClass: 'opacity-30',
                    chosenClass: 'scale-[1.02]',
                    dragClass: 'shadow-2xl',
                    onEnd: async (evt) => {
                        const card = evt.item;
                        const taskId = card.dataset.taskId;
                        const targetCol = evt.to;
                        const targetColumnId = targetCol.dataset.columnId;

                        // Gather new card order in the destination column
                        const cardElements = Array.from(targetCol.querySelectorAll('.kanban-card'));
                        const order = cardElements.map(el => parseInt(el.dataset.taskId, 10));

                        // 1. Move status if column changed
                        if (evt.from !== evt.to) {
                            try {
                                await fetch(`/projects/{{ $project->id }}/tasks/${taskId}/move`, {
                                    method: 'PATCH',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': csrfToken,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({ board_column_id: targetColumnId })
                                });
                            } catch (err) {
                                console.error('Move error:', err);
                            }
                        }

                        // 2. Persist sort order
                        try {
                            await fetch(`/projects/{{ $project->id }}/tasks/reorder`, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    order: order,
                                    board_column_id: targetColumnId
                                })
                            });
                        } catch (err) {
                            console.error('Reorder error:', err);
                        }

                        // Update counters
                        this.updateColumnCounters();
                    }
                });
            });

            // 2. Setup Bucket (Column) Drag Reordering
            const columnsContainer = document.getElementById('kanban-columns-container');
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
                                    'X-CSRF-TOKEN': csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ order: colOrder })
                            });
                        } catch (err) {
                            console.error('Column reorder error:', err);
                        }
                    }
                });
            }
        },

        updateColumnCounters() {
            document.querySelectorAll('.kanban-column').forEach(col => {
                const count = col.querySelectorAll('.kanban-card').length;
                const counter = col.querySelector('.column-counter');
                if (counter) counter.textContent = count;
            });
        },

        // Client-side visual card filtering
        filterCards() {
            const q = this.searchQuery.toLowerCase().trim();
            const p = this.selectedPriority;
            const m = this.selectedMember;

            document.querySelectorAll('.kanban-card').forEach(card => {
                const title = (card.querySelector('h4')?.textContent || '').toLowerCase();
                const cardPriority = card.dataset.priority || '';
                let match = true;

                if (q && !title.includes(q)) match = false;
                if (p && cardPriority !== p) match = false;

                card.style.display = match ? '' : 'none';
            });
        },

        // Inline Task Creation inside Bucket
        async submitInlineTask(columnId) {
            const input = this.$event?.target?.closest('div')?.querySelector('textarea');
            const title = (this.taskTitle || (input ? input.value : '')).trim();
            if (!title) return;

            this.isSubmitting = true;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const res = await fetch(`/projects/{{ $project->id }}/tasks`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        title: title,
                        board_column_id: columnId,
                        sprint_id: {{ $sprint->id }}
                    })
                });

                if (res.ok) {
                    // Quick reload to render card partial with server data
                    window.location.reload();
                } else {
                    const err = await res.json();
                    Swal.fire({ icon: 'error', title: 'Gagal', text: err.message || 'Gagal menambahkan task.' });
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.isSubmitting = false;
            }
        },

        // Column management
        async createColumn(name, color) {
            if (!name || !name.trim()) return;
            const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

            try {
                const res = await fetch(`/projects/{{ $project->id }}/board-columns`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ name: name.trim(), color: color || 'blue' })
                });

                window.location.reload();
            } catch (err) {
                console.error(err);
            }
        },

        openAddColumnPrompt() {
            Swal.fire({
                title: 'Tambah Kolom / Bucket Baru',
                input: 'text',
                inputPlaceholder: 'Nama Kolom (misal: QA / Testing)...',
                showCancelButton: true,
                confirmButtonText: 'Tambah Kolom',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                inputValidator: (value) => {
                    if (!value) return 'Nama kolom tidak boleh kosong!';
                }
            }).then(result => {
                if (result.isConfirmed) {
                    this.createColumn(result.value, 'blue');
                }
            });
        },

        renameColumn(columnId, currentName) {
            Swal.fire({
                title: 'Ubah Nama Kolom',
                input: 'text',
                inputValue: currentName,
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#2563eb',
                inputValidator: (value) => {
                    if (!value) return 'Nama kolom tidak boleh kosong!';
                }
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    try {
                        const res = await fetch(`/projects/{{ $project->id }}/board-columns/${columnId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({ name: result.value, color: 'blue' })
                        });
                        window.location.reload();
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        },

        deleteColumn(columnId, colName) {
            Swal.fire({
                title: `Hapus Kolom "${colName}"?`,
                text: 'Kolom harus kosong sebelum dihapus.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, Hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#ef4444'
            }).then(async (result) => {
                if (result.isConfirmed) {
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                    try {
                        const res = await fetch(`/projects/{{ $project->id }}/board-columns/${columnId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'Accept': 'application/json'
                            }
                        });
                        window.location.reload();
                    } catch (err) {
                        console.error(err);
                    }
                }
            });
        }
    };
}

// Global modal opener
function openTask(taskId) {
    if (window.openTaskModal) {
        window.openTaskModal(taskId);
    } else {
        window.dispatchEvent(new CustomEvent('open-task-modal', { detail: { taskId } }));
    }
}
</script>
@endpush
@endsection
