@extends('layouts.app')
@section('title', 'Meeting')
@section('page-title', 'Meeting')

@php
    $typeMeta = [
        'project'   => ['label' => 'Proyek',    'icon' => '📁', 'badge' => 'bg-blue-100 text-blue-700'],
        'sprint'    => ['label' => 'Sprint',    'icon' => '⚡', 'badge' => 'bg-emerald-100 text-emerald-700'],
        'milestone' => ['label' => 'Milestone', 'icon' => '🏁', 'badge' => 'bg-purple-100 text-purple-700'],
        'task'      => ['label' => 'Task',      'icon' => '✅', 'badge' => 'bg-sky-100 text-sky-700'],
        'ticket'    => ['label' => 'Ticket',    'icon' => '🐛', 'badge' => 'bg-amber-100 text-amber-700'],
    ];
    $tabs = [
        'all'       => 'Semua',
        'task'      => 'Task',
        'sprint'    => 'Sprint',
        'milestone' => 'Milestone',
        'project'   => 'Proyek',
        'ticket'    => 'Ticket',
    ];
@endphp

@section('content')
<div class="py-4" x-data="addMeetingModal()">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
        <div>
            <h1 class="text-lg font-bold text-gray-800">Meeting</h1>
            <p class="text-sm text-gray-500">Semua jadwal meeting dari proyek, sprint, milestone, task, dan ticket.</p>
        </div>

        <div class="flex gap-2 flex-wrap items-center">
            <form method="GET" class="flex gap-2 flex-wrap">
                <input type="hidden" name="category" value="{{ $category }}">
                <select name="project" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="">Semua Proyek</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}" {{ (string) $projectId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                    @endforeach
                </select>
                <select name="when" onchange="this.form.submit()" class="text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                    <option value="upcoming" {{ $when === 'upcoming' ? 'selected' : '' }}>Akan Datang</option>
                    <option value="past" {{ $when === 'past' ? 'selected' : '' }}>Sudah Lewat</option>
                    <option value="all" {{ $when === 'all' ? 'selected' : '' }}>Semua</option>
                </select>
            </form>

            @unless(auth()->user()->hasRole('customer'))
            <button @click="open = true"
                    class="inline-flex items-center gap-1.5 px-3 py-2 bg-violet-600 text-white text-sm font-medium rounded-lg hover:bg-violet-700 transition shadow-sm">
                + Tambah Meeting
            </button>
            @endunless
        </div>
    </div>

    {{-- Category tabs --}}
    <div class="flex flex-wrap gap-2 mb-5">
        @foreach($tabs as $key => $label)
            @php
                $isActive = $category === $key;
                $qs = http_build_query(array_filter(['category' => $key, 'when' => $when, 'project' => $projectId]));
            @endphp
            <a href="{{ route('meetings.index') }}?{{ $qs }}"
               class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border transition-colors
                      {{ $isActive ? 'bg-violet-600 text-white border-violet-600' : 'bg-white text-gray-600 border-gray-200 hover:bg-gray-50' }}">
                {{ $label }}
                <span class="inline-flex items-center justify-center min-w-[1.25rem] h-5 px-1 rounded-full text-[10px] font-bold
                             {{ $isActive ? 'bg-white/20 text-white' : 'bg-gray-100 text-gray-600' }}">
                    {{ $counts[$key] ?? 0 }}
                </span>
            </a>
        @endforeach
    </div>

    {{-- Meeting list --}}
    @if($meetings->isEmpty())
        <div class="bg-white rounded-xl border border-gray-200 p-10 text-center">
            <p class="text-sm text-gray-500">Belum ada meeting yang terjadwal untuk filter ini.</p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            @foreach($meetings as $m)
                @php $meta = $typeMeta[$m['type']]; @endphp
                <div class="bg-white rounded-xl border border-gray-200 p-4 hover:border-violet-200 transition-colors">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="inline-flex items-center gap-1 text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $meta['badge'] }}">
                                    {{ $meta['icon'] }} {{ $meta['label'] }}
                                </span>
                                @if($m['recurring'])
                                    <span class="text-[11px] font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600">Recurring</span>
                                @endif
                            </div>
                            <a href="{{ $m['url'] }}" class="font-semibold text-gray-800 hover:text-violet-700 truncate block">
                                {{ $m['title'] }}
                            </a>
                            @if($m['project'])
                                <p class="text-xs text-gray-500 mt-0.5 truncate">{{ $m['project'] }}</p>
                            @endif
                        </div>

                        <div class="shrink-0">
                            @if($m['meetLink'])
                                <a href="{{ $m['meetLink'] }}" target="_blank" rel="noopener"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-600 text-white text-xs font-medium rounded-lg hover:bg-green-700 transition shadow-sm">
                                    Join
                                </a>
                            @else
                                <form method="POST" action="{{ $m['createUrl'] }}">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-violet-700 border border-violet-200 rounded-lg hover:bg-violet-50 transition">
                                        Buat Meeting
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-3 mt-3 pt-3 border-t border-gray-100 text-xs text-gray-500">
                        <span class="inline-flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            {{ $m['startsAt'] ? $m['startsAt']->format('d M Y, H:i') : 'Belum dijadwalkan' }}
                        </span>
                        @if($m['organizer'])
                            <span class="inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                {{ $m['organizer'] }}
                            </span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Modal: Tambah Meeting --}}
    <div x-show="open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
         @click.self="open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-5">
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-semibold text-gray-800 text-sm">Tambah Meeting</h3>
                <button @click="open = false" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form method="POST" action="{{ route('meetings.create') }}" class="space-y-3">
                @csrf
                <input type="hidden" name="entity_id" :value="type === 'project' ? projectId : entityId">

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Tipe</label>
                    <select name="type" x-model="type" @change="onTypeChange()" required
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="project">Proyek (kickoff/general)</option>
                        <option value="sprint">Sprint</option>
                        <option value="milestone">Milestone</option>
                        <option value="task">Task</option>
                        <option value="ticket">Ticket</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-700 mb-1">Proyek</label>
                    <select x-model="projectId" @change="onProjectChange()" required
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="">-- Pilih Proyek --</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="type !== 'project'">
                    <label class="block text-xs font-medium text-gray-700 mb-1" x-text="itemLabel()"></label>
                    <select x-model="entityId" :required="type !== 'project'"
                            class="w-full text-sm border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-violet-500">
                        <option value="" x-text="loading ? '-- Memuat... --' : '-- Pilih --'"></option>
                        <template x-for="item in items" :key="item.id">
                            <option :value="item.id" x-text="item.label"></option>
                        </template>
                    </select>
                    <p class="text-xs text-gray-400 mt-1" x-show="!loading && projectId && items.length === 0">
                        Semua item di proyek ini sudah punya meeting, atau belum ada item.
                    </p>
                </div>

                <label class="flex items-center gap-2" x-show="type === 'sprint'">
                    <input type="checkbox" name="recurring" value="1" x-model="recurring"
                           class="rounded border-gray-300 text-violet-600 focus:ring-violet-500">
                    <span class="text-xs text-gray-700">Standup harian (berulang, Sen–Jum)</span>
                </label>

                <div class="pt-2 flex gap-2">
                    <button type="submit" :disabled="type !== 'project' && !entityId"
                            class="flex-1 text-center text-xs font-semibold bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed">
                        Buat Meeting
                    </button>
                    <button type="button" @click="open = false"
                            class="px-4 py-2 text-xs text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50">
                        Batal
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function addMeetingModal() {
    return {
        open: false,
        type: 'project',
        projectId: '',
        entityId: '',
        recurring: false,
        items: [],
        loading: false,

        itemLabel() {
            return { sprint: 'Sprint', milestone: 'Milestone', task: 'Task', ticket: 'Ticket' }[this.type] || 'Item';
        },

        onTypeChange() {
            this.entityId = '';
            this.recurring = false;
            this.items = [];
            if (this.projectId && this.type !== 'project') this.fetchItems();
        },

        onProjectChange() {
            this.entityId = '';
            this.items = [];
            if (this.projectId && this.type !== 'project') this.fetchItems();
        },

        fetchItems() {
            this.loading = true;
            const params = new URLSearchParams({ type: this.type, project_id: this.projectId });
            fetch('{{ route("meetings.pickables") }}?' + params.toString())
                .then(r => r.json())
                .then(data => { this.items = data; this.loading = false; })
                .catch(() => { this.items = []; this.loading = false; });
        },
    };
}
</script>
@endpush
@endsection
