@extends('layouts.app')
@section('title', 'Kalender')
@section('page-title', 'Kalender')

@push('head')
<link href='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.css' rel='stylesheet'>
@endpush

@section('content')
<div class="py-4" x-data="calendarApp()" x-init="init()">

    <div class="flex gap-4 items-start">

        {{-- ── Sidebar ──────────────────────────────────────────────────────── --}}
        <div class="w-64 shrink-0 space-y-3">

            {{-- Mini stats --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Bulan Ini</p>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-blue-500"></span>Task Due
                        </span>
                        <span class="text-xs font-bold text-gray-800" x-text="stats.task"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-purple-500"></span>Milestone
                        </span>
                        <span class="text-xs font-bold text-gray-800" x-text="stats.milestone"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-green-500"></span>Sprint
                        </span>
                        <span class="text-xs font-bold text-gray-800" x-text="stats.sprint"></span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="flex items-center gap-1.5 text-xs text-gray-600">
                            <span class="w-2 h-2 rounded-full bg-yellow-400"></span>Ticket SLA
                        </span>
                        <span class="text-xs font-bold text-gray-800" x-text="stats.ticket"></span>
                    </div>
                </div>
            </div>

            {{-- Filter type --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Filter Tipe</p>
                <div class="space-y-2">
                    <template x-for="f in filters" :key="f.type">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" x-model="f.active" @change="refetch()"
                                   class="w-3.5 h-3.5 rounded border-gray-300">
                            <span class="flex items-center gap-1.5 text-xs text-gray-700">
                                <span class="w-2 h-2 rounded-full" :style="'background:'+f.color"></span>
                                <span x-text="f.label"></span>
                            </span>
                        </label>
                    </template>
                </div>
            </div>

            {{-- Upcoming events (7 days) --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">7 Hari ke Depan</p>
                <div class="space-y-2" id="upcoming-list">
                    <p class="text-xs text-gray-400">Memuat…</p>
                </div>
            </div>

        </div>

        {{-- ── Main Calendar ─────────────────────────────────────────────────── --}}
        <div class="flex-1 min-w-0">
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <div id="calendar"></div>
            </div>
        </div>

    </div>

    {{-- ── Event Detail Modal ────────────────────────────────────────────────── --}}
    <div x-show="modal.open" x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/30"
         @click.self="modal.open = false">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 p-5">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="w-3 h-3 rounded-full shrink-0" :style="'background:'+modal.color"></span>
                    <h3 class="font-semibold text-gray-800 text-sm" x-text="modal.title"></h3>
                </div>
                <button @click="modal.open = false" class="text-gray-400 hover:text-gray-600 ml-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="space-y-1.5 text-xs text-gray-600">
                <div class="flex gap-2">
                    <span class="text-gray-400 w-20 shrink-0">Tipe</span>
                    <span class="font-medium capitalize" x-text="modal.type"></span>
                </div>
                <div class="flex gap-2" x-show="modal.project">
                    <span class="text-gray-400 w-20 shrink-0">Proyek</span>
                    <span x-text="modal.project"></span>
                </div>
                <div class="flex gap-2" x-show="modal.date">
                    <span class="text-gray-400 w-20 shrink-0">Tanggal</span>
                    <span x-text="modal.date"></span>
                </div>
                <div class="flex gap-2" x-show="modal.status">
                    <span class="text-gray-400 w-20 shrink-0">Status</span>
                    <span class="capitalize font-medium" x-text="modal.status?.replace('_',' ')"></span>
                </div>
                <div class="flex gap-2" x-show="modal.priority">
                    <span class="text-gray-400 w-20 shrink-0">Prioritas</span>
                    <span class="capitalize font-medium" x-text="modal.priority"></span>
                </div>
                <div class="flex gap-2" x-show="modal.assignee">
                    <span class="text-gray-400 w-20 shrink-0">Assignee</span>
                    <span x-text="modal.assignee"></span>
                </div>
                <div x-show="modal.overdue"
                     class="mt-2 bg-red-50 border border-red-200 text-red-600 rounded-lg px-3 py-1.5 font-medium">
                    ⚠ Overdue
                </div>
                <div x-show="modal.breached"
                     class="mt-2 bg-red-50 border border-red-200 text-red-600 rounded-lg px-3 py-1.5 font-medium">
                    ⚠ SLA Breached
                </div>
            </div>

            <div class="mt-4 flex gap-2">
                <a :href="modal.url" target="_blank"
                   class="flex-1 text-center text-xs font-semibold bg-violet-600 text-white px-4 py-2 rounded-lg hover:bg-violet-700">
                    Lihat Detail →
                </a>
                <button @click="modal.open = false"
                        class="px-4 py-2 text-xs text-gray-500 border border-gray-300 rounded-lg hover:bg-gray-50">
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

@push('head')
<style>
    .fc .fc-toolbar-title { font-size: 1rem; font-weight: 600; }
    .fc .fc-button { font-size: 0.75rem !important; padding: 4px 10px !important; }
    .fc .fc-button-primary { background-color: #2563EB !important; border-color: #2563EB !important; }
    .fc .fc-button-primary:not(.fc-button-active):hover { background-color: #1D4ED8 !important; }
    .fc .fc-button-active { background-color: #1D4ED8 !important; }
    .fc .fc-daygrid-event { font-size: 0.7rem; padding: 1px 4px; border-radius: 4px; border: none; }
    .fc .fc-event-title { font-weight: 500; }
    .fc .fc-col-header-cell { font-size: 0.7rem; font-weight: 600; }
    .fc .fc-daygrid-day-number { font-size: 0.75rem; padding: 4px 6px; }
    .fc-today-button { text-transform: capitalize !important; }
    .fc .fc-list-event:hover td { background: #EFF6FF; cursor: pointer; }
    .fc .fc-list-event-title { font-size: 0.8rem; }
    .fc .fc-list-event-time { font-size: 0.75rem; }
</style>
@endpush

@push('scripts')
<script src='https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js'></script>
<script>
function calendarApp() {
    return {
        calendar: null,
        modal: { open: false, title:'', type:'', project:'', date:'', status:'', priority:'', assignee:'', url:'#', color:'#3B82F6', overdue:false, breached:false },
        stats:   { task: 0, milestone: 0, sprint: 0, ticket: 0 },
        filters: [
            { type: 'task',      label: 'Task Due',     color: '#3B82F6', active: true },
            { type: 'milestone', label: 'Milestone',    color: '#7C3AED', active: true },
            { type: 'sprint',    label: 'Sprint',       color: '#10B981', active: true },
            { type: 'ticket',    label: 'Ticket SLA',   color: '#F59E0B', active: true },
        ],

        getActiveTypes() {
            return this.filters.filter(f => f.active).map(f => f.type);
        },

        buildEventsUrl(info) {
            const types = this.getActiveTypes();
            const params = new URLSearchParams({
                start: info.startStr,
                end:   info.endStr,
            });
            types.forEach(t => params.append('types[]', t));
            return '{{ route("calendar.events") }}?' + params.toString();
        },

        refetch() {
            this.calendar?.refetchEvents();
        },

        init() {
            const self = this;
            const el   = document.getElementById('calendar');

            this.calendar = new FullCalendar.Calendar(el, {
                locale: 'id',
                initialView: 'dayGridMonth',
                height: 'auto',
                headerToolbar: {
                    left:   'prev,next today',
                    center: 'title',
                    right:  'dayGridMonth,timeGridWeek,listWeek'
                },
                buttonText: { today:'Hari ini', month:'Bulan', week:'Minggu', list:'List' },
                firstDay: 1,
                dayMaxEvents: 4,
                eventDisplay: 'block',
                nowIndicator: true,

                events(info, successCallback, failureCallback) {
                    fetch(self.buildEventsUrl(info))
                        .then(r => r.json())
                        .then(data => {
                            // Count stats for current month
                            self.stats = { task:0, milestone:0, sprint:0, ticket:0 };
                            data.forEach(e => {
                                const t = e.extendedProps?.type;
                                if (t && self.stats[t] !== undefined) self.stats[t]++;
                            });
                            successCallback(data);
                        })
                        .catch(failureCallback);
                },

                eventClick(info) {
                    const p = info.event.extendedProps;
                    self.modal = {
                        open:     true,
                        title:    info.event.title.replace(/^[^\s]+\s/, ''), // strip emoji prefix
                        type:     p.type,
                        project:  p.project   || '',
                        date:     info.event.startStr || '',
                        status:   p.status    || '',
                        priority: p.priority  || '',
                        assignee: p.assignee  || '',
                        url:      p.url       || '#',
                        color:    info.event.backgroundColor,
                        overdue:  p.overdue   || false,
                        breached: p.breached  || false,
                    };
                    info.jsEvent.preventDefault();
                },

                eventMouseEnter(info) {
                    info.el.style.cursor = 'pointer';
                    info.el.title = info.event.title;
                },
            });

            this.calendar.render();
            this.loadUpcoming();
        },

        loadUpcoming() {
            fetch('{{ route("calendar.upcoming") }}')
                .then(r => r.json())
                .then(items => {
                    const el = document.getElementById('upcoming-list');
                    if (!items.length) {
                        el.innerHTML = '<p class="text-xs text-gray-400">Tidak ada event dalam 7 hari ke depan.</p>';
                        return;
                    }
                    const typeIcon  = { task:'📋', milestone:'🏁', sprint:'⚡', ticket:'🐛' };
                    const typeColor = { task:'text-blue-600', milestone:'text-purple-600', sprint:'text-green-600', ticket:'text-yellow-600' };
                    const prioColor = { critical:'bg-red-100 text-red-700', high:'bg-orange-100 text-orange-700', medium:'bg-blue-100 text-blue-700', low:'bg-green-100 text-green-700' };

                    el.innerHTML = items.map(item => `
                        <a href="${item.url}" class="block group">
                            <div class="flex items-start gap-2 p-2 rounded-lg hover:bg-gray-50 transition-colors">
                                <span class="text-sm mt-0.5">${typeIcon[item.type] || '📌'}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="text-xs font-medium text-gray-800 truncate group-hover:text-blue-600">${item.title}</p>
                                    <p class="text-xs text-gray-400">${item.date} · ${item.project || '—'}</p>
                                    ${item.priority ? `<span class="inline-block mt-0.5 text-xs px-1.5 py-0.5 rounded-full font-medium ${prioColor[item.priority] || ''}">${item.priority}</span>` : ''}
                                </div>
                            </div>
                        </a>
                    `).join('');
                });
        },
    };
}
</script>
@endpush
@endsection
