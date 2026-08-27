@extends('layouts.app')
@section('title', 'Aktivitas Kerja — Task')
@section('page-title', 'Aktivitas Kerja')

@section('content')
@php
    $pc  = ['low'=>'bg-green-100 text-green-700','medium'=>'bg-yellow-100 text-yellow-700','high'=>'bg-orange-100 text-orange-700','urgent'=>'bg-red-100 text-red-700'];
    $sc  = ['todo'=>'bg-gray-100 text-gray-600','in_progress'=>'bg-blue-100 text-blue-700','review'=>'bg-purple-100 text-purple-700','done'=>'bg-green-100 text-green-700'];
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
@endphp
<div class="py-4" x-data="{ view: 'list' }">
    @include('partials.work-activity-tabs')

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
            @if(request('status') || request('priority'))
                <a href="{{ route('tasks.all') }}" class="text-sm text-gray-400 hover:text-gray-600 px-2 py-2">✕ Reset</a>
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
    </div>

    {{-- ===== LIST VIEW ===== --}}
    <div x-show="view==='list'" x-cloak class="bg-white rounded-xl border border-gray-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                <tr>
                    <th class="px-4 py-3 text-left">Judul</th>
                    <th class="px-4 py-3 text-left">Proyek</th>
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
                    <td class="px-4 py-3 font-medium text-gray-800 max-w-xs truncate">{{ $task->title }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $task->project->name ?? '-' }}</td>
                    <td class="px-4 py-3 text-gray-500 text-xs">{{ $task->sprint->name ?? '—' }}</td>
                    <td class="px-4 py-3">
                        @if($task->recurring_definition_id)
                        <span class="badge bg-indigo-100 text-indigo-700">Recurring</span>
                        @else
                        <span class="text-gray-300 text-xs">—</span>
                        @endif
                    </td>
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
                <tr><td colspan="9" class="px-4 py-8 text-center text-gray-400">
                    {{ request('status') || request('priority') ? 'Tidak ada task sesuai filter.' : 'Belum ada task.' }}
                </td></tr>
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
