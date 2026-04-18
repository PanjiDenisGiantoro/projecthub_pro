@extends('layouts.app')
@section('title', 'Tasks — ' . $project->name)
@section('page-title', 'Tasks: ' . $project->name)

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

        @if(!auth()->user()->hasRole('customer'))
        <button @click="showForm=!showForm"
                class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium px-4 py-2 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <span x-text="showForm ? 'Batal' : 'Tambah Task'"></span>
        </button>
        @endif
    </div>

    {{-- Add Task Form --}}
    @if(!auth()->user()->hasRole('customer'))
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
                    @if(!auth()->user()->hasRole('customer'))
                    <form method="POST" action="{{ route('tasks.update', [$project, $task]) }}" class="inline">
                        @csrf @method('PUT')
                        <select name="status" onchange="this.form.submit()"
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

        @if($tasks->hasPages())
            <div class="mt-4">{{ $tasks->links() }}</div>
        @endif
    </div>

    {{-- ===== KANBAN VIEW ===== --}}
    <div x-show="view==='kanban'" x-cloak>
        @php
            $kanbanCols = [
                'todo'        => ['label' => 'To Do',       'color' => 'bg-gray-400', 'header' => 'bg-gray-50 border-gray-200'],
                'in_progress' => ['label' => 'In Progress', 'color' => 'bg-blue-500', 'header' => 'bg-blue-50 border-blue-100'],
                'review'      => ['label' => 'Review',      'color' => 'bg-purple-500','header'=> 'bg-purple-50 border-purple-100'],
                'done'        => ['label' => 'Done',        'color' => 'bg-green-500', 'header' => 'bg-green-50 border-green-100'],
            ];
            $allTasks = $project->tasks()->with(['assignee','milestone'])->latest()->get();
        @endphp
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4" id="kanban-board">
            @foreach($kanbanCols as $statusKey => $col)
            @php $colTasks = $allTasks->where('status', $statusKey); @endphp
            <div class="flex flex-col min-h-64" data-column="{{ $statusKey }}">
                {{-- Column header --}}
                <div class="flex items-center gap-2 px-3 py-2.5 rounded-t-xl border border-b-0 {{ $col['header'] }}">
                    <span class="w-2.5 h-2.5 rounded-full {{ $col['color'] }}"></span>
                    <span class="text-sm font-semibold text-gray-700">{{ $col['label'] }}</span>
                    <span class="ml-auto bg-white text-gray-500 text-xs font-medium px-2 py-0.5 rounded-full border border-gray-200 kanban-count"
                          id="count-{{ $statusKey }}">{{ $colTasks->count() }}</span>
                </div>

                {{-- Drop zone --}}
                <div class="flex-1 border border-t-0 border-gray-200 rounded-b-xl bg-gray-50 p-2 space-y-2 min-h-24 kanban-col"
                     data-status="{{ $statusKey }}"
                     ondragover="event.preventDefault(); this.classList.add('ring-2','ring-blue-400','ring-inset')"
                     ondragleave="this.classList.remove('ring-2','ring-blue-400','ring-inset')"
                     ondrop="handleDrop(event, '{{ $statusKey }}')">

                    @forelse($colTasks as $task)
                    @php
                        $overdue = $task->isOverdue();
                        $days    = $task->daysRemaining();
                        $pl      = $plb[$task->priority] ?? 'border-l-gray-300';
                    @endphp
                    <div class="bg-white rounded-lg border border-gray-200 border-l-4 {{ $pl }} p-3 hover:shadow-sm transition-shadow cursor-grab active:cursor-grabbing select-none kanban-card"
                         draggable="true"
                         data-task-id="{{ $task->id }}"
                         data-status="{{ $task->status }}"
                         ondragstart="handleDragStart(event)"
                         ondragend="handleDragEnd(event)">

                        <a href="{{ route('tasks.show', [$project, $task]) }}"
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
                            <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
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

        {{-- Drop feedback toast --}}
        <div id="kanban-toast" class="fixed bottom-4 right-4 bg-gray-800 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg hidden transition-all z-50"></div>
    </div>

</div>

@push('scripts')
<script>
(function() {
    const MOVE_URL = '{{ route('tasks.move', [$project, '__ID__']) }}';
    const CSRF     = document.querySelector('meta[name="csrf-token"]').content;
    let dragging   = null;

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

    window.handleDrop = function(e, newStatus) {
        e.preventDefault();
        const col = e.currentTarget;
        col.classList.remove('ring-2','ring-blue-400','ring-inset');

        const card = dragging || document.querySelector(`.kanban-card[data-task-id="${e.dataTransfer.getData('text/plain')}"]`);
        if (!card) return;

        const oldStatus = card.dataset.status;
        if (oldStatus === newStatus) return;

        const taskId = card.dataset.taskId;

        // Optimistic UI: move card DOM
        const emptyEl = col.querySelector('.kanban-empty');
        if (emptyEl) emptyEl.remove();
        col.appendChild(card);
        card.dataset.status = newStatus;

        // Remove empty placeholder from old col if needed
        const oldCol = document.querySelector(`.kanban-col[data-status="${oldStatus}"]`);
        if (oldCol && oldCol.querySelectorAll('.kanban-card').length === 0) {
            oldCol.innerHTML = '<div class="py-6 text-center text-xs text-gray-400 kanban-empty">Tidak ada task</div>';
        }

        // Update counts
        ['todo','in_progress','review','done'].forEach(s => {
            const cnt = document.querySelectorAll(`.kanban-col[data-status="${s}"] .kanban-card`).length;
            const badge = document.getElementById('count-' + s);
            if (badge) badge.textContent = cnt;
        });

        // API call
        fetch(MOVE_URL.replace('__ID__', taskId), {
            method: 'PATCH',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ status: newStatus })
        })
        .then(r => r.json())
        .then(data => {
            if (data.ok) showToast('✓ Status diperbarui ke ' + formatStatus(newStatus));
        })
        .catch(() => {
            showToast('✕ Gagal update status', true);
        });
    };

    function formatStatus(s) {
        return { todo:'To Do', in_progress:'In Progress', review:'Review', done:'Done' }[s] || s;
    }

    function showToast(msg, err = false) {
        const t = document.getElementById('kanban-toast');
        t.textContent = msg;
        t.className = `fixed bottom-4 right-4 text-white text-sm px-4 py-2.5 rounded-lg shadow-lg z-50 transition-all ${err ? 'bg-red-600' : 'bg-gray-800'}`;
        t.classList.remove('hidden');
        setTimeout(() => t.classList.add('hidden'), 3000);
    }
})();
</script>
@endpush
@endsection
