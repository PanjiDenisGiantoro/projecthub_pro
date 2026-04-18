@extends('layouts.app')
@section('title', 'Analytics')
@section('page-title', 'Analytics')

@section('content')
@php
    $doneRate = $totalTasks > 0 ? round($doneTasks / $totalTasks * 100) : 0;
    $milestoneRate = $totalMilestones > 0 ? round($completedMilestones / $totalMilestones * 100) : 0;
@endphp

<div class="py-4 space-y-5">

    {{-- Header: period filter --}}
    <div class="flex items-center justify-between">
        <p class="text-xs text-gray-500">Data real-time · diperbarui setiap reload</p>
        <form method="GET" class="flex gap-2 items-center">
            <label class="text-xs text-gray-500">Periode:</label>
            <select name="period" onchange="this.form.submit()"
                    class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
                @foreach([7=>'7 Hari',14=>'14 Hari',30=>'30 Hari',90=>'3 Bulan'] as $val=>$label)
                <option value="{{ $val }}" {{ $period == $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </form>
    </div>

    {{-- ── Row 1: KPI Cards (8 cards) ─────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-3">
        @php
        $kpis = [
            ['label'=>'Total Task',    'value'=>$totalTasks,         'color'=>'text-gray-800',   'sub'=>null],
            ['label'=>'Selesai',       'value'=>$doneTasks,          'color'=>'text-green-600',  'sub'=>$doneRate.'%'],
            ['label'=>'Overdue',       'value'=>$overdueCount,       'color'=>'text-red-600',    'sub'=>null],
            ['label'=>'Total Proyek',  'value'=>$totalProjects,      'color'=>'text-gray-800',   'sub'=>null],
            ['label'=>'Aktif',         'value'=>$activeProjects,     'color'=>'text-blue-600',   'sub'=>null],
            ['label'=>'Open Ticket',   'value'=>$openTickets,        'color'=>'text-orange-600', 'sub'=>null],
            ['label'=>'SLA Breach',    'value'=>$slaBreached,        'color'=>'text-red-600',    'sub'=>null],
            ['label'=>'Request Baru',  'value'=>$newRequests,        'color'=>'text-violet-600', 'sub'=>$period.'h'],
        ];
        @endphp
        @foreach($kpis as $k)
        <div class="bg-white rounded-xl border border-gray-200 px-3 py-3 text-center">
            <p class="text-xs text-gray-400 leading-tight mb-1">{{ $k['label'] }}</p>
            <p class="text-xl font-bold {{ $k['color'] }}">{{ $k['value'] }}</p>
            @if($k['sub'])
            <p class="text-xs text-gray-400 mt-0.5">{{ $k['sub'] }}</p>
            @endif
        </div>
        @endforeach
    </div>

    {{-- ── Row 2: 3 Charts ────────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Weekly Task Chart --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Task Dibuat vs Selesai (8 Minggu)</p>
            <canvas id="weeklyChart" height="160"></canvas>
        </div>

        {{-- Task by Status Doughnut --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Status Task</p>
            <div class="flex gap-4 items-center">
                <canvas id="statusChart" width="120" height="120" style="max-width:120px;max-height:120px;"></canvas>
                <div class="flex-1 space-y-1.5">
                    @php
                    $statusColors = ['todo'=>'bg-slate-400','in_progress'=>'bg-blue-400','review'=>'bg-yellow-400','done'=>'bg-green-400','blocked'=>'bg-red-400'];
                    $statusLabels = ['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done','blocked'=>'Blocked'];
                    @endphp
                    @foreach($tasksByStatus as $status => $count)
                    <div class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full {{ $statusColors[$status] ?? 'bg-gray-400' }} shrink-0"></span>
                        <span class="text-xs text-gray-600 flex-1">{{ $statusLabels[$status] ?? $status }}</span>
                        <span class="text-xs font-semibold text-gray-700">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Ticket by Status --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Status Ticket</p>
            <canvas id="ticketStatusChart" height="160"></canvas>
        </div>

    </div>

    {{-- ── Row 3: 3 Columns ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Top Developers --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Top Developer — Jam Log ({{ $period }}h)</p>
            @forelse($topDevs as $dev)
            @php $hrs = round($dev->total_minutes / 60, 1); $max = $topDevs->first()->total_minutes ?: 1; @endphp
            <div class="flex items-center gap-2 mb-2">
                <div class="w-20 text-xs text-gray-700 truncate shrink-0">{{ $dev->user?->name ?? '—' }}</div>
                <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div class="h-1.5 bg-blue-500 rounded-full" style="width:{{ round($dev->total_minutes/$max*100) }}%"></div>
                </div>
                <div class="w-10 text-xs text-right text-gray-500 shrink-0">{{ $hrs }}j</div>
            </div>
            @empty
            <p class="text-xs text-gray-400">Belum ada time log.</p>
            @endforelse
        </div>

        {{-- Project Progress --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Progress Proyek Aktif</p>
            @forelse($projectProgress as $proj)
            <div class="mb-2.5">
                <div class="flex justify-between mb-0.5">
                    <span class="text-xs text-gray-700 truncate max-w-[70%]">{{ $proj['name'] }}</span>
                    <span class="text-xs text-gray-500">{{ $proj['done'] }}/{{ $proj['total'] }}</span>
                </div>
                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                    <div class="h-1.5 rounded-full {{ $proj['percent'] >= 100 ? 'bg-green-500' : ($proj['percent'] >= 50 ? 'bg-blue-500' : 'bg-yellow-400') }}"
                         style="width:{{ $proj['percent'] }}%"></div>
                </div>
            </div>
            @empty
            <p class="text-xs text-gray-400">Tidak ada proyek aktif.</p>
            @endforelse
        </div>

        {{-- Ticket by Priority + Milestone --}}
        <div class="space-y-4">
            {{-- Ticket Priority --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-600 mb-3">Ticket Terbuka per Prioritas</p>
                @php
                $prioColors = ['critical'=>'bg-red-500','high'=>'bg-orange-400','medium'=>'bg-yellow-400','low'=>'bg-blue-300'];
                $prioLabels = ['critical'=>'Critical','high'=>'High','medium'=>'Medium','low'=>'Low'];
                $prioTotal  = $ticketsByPriority->sum() ?: 1;
                @endphp
                @foreach(['critical','high','medium','low'] as $p)
                @php $cnt = $ticketsByPriority->get($p, 0); @endphp
                <div class="flex items-center gap-2 mb-1.5">
                    <span class="w-14 text-xs text-gray-600">{{ $prioLabels[$p] }}</span>
                    <div class="flex-1 bg-gray-100 rounded-full h-1.5 overflow-hidden">
                        <div class="h-1.5 rounded-full {{ $prioColors[$p] }}" style="width:{{ round($cnt/$prioTotal*100) }}%"></div>
                    </div>
                    <span class="w-5 text-xs text-right text-gray-500">{{ $cnt }}</span>
                </div>
                @endforeach
            </div>

            {{-- Milestones --}}
            <div class="bg-white rounded-xl border border-gray-200 p-4">
                <p class="text-xs font-semibold text-gray-600 mb-2">Milestone Selesai</p>
                <div class="flex items-end gap-3">
                    <div>
                        <span class="text-2xl font-bold text-gray-800">{{ $completedMilestones }}</span>
                        <span class="text-xs text-gray-400"> / {{ $totalMilestones }}</span>
                    </div>
                    <div class="flex-1">
                        <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                            <div class="h-2 bg-green-500 rounded-full transition-all" style="width:{{ $milestoneRate }}%"></div>
                        </div>
                        <p class="text-xs text-gray-400 mt-1">{{ $milestoneRate }}% selesai</p>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Row 4: 2 Columns ───────────────────────────────────────────────── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        {{-- Daily new tasks trend (14 days) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Task Dibuat per Hari (14 Hari Terakhir)</p>
            <canvas id="dailyTrendChart" height="120"></canvas>
        </div>

        {{-- Projects by status (horizontal bar) --}}
        <div class="bg-white rounded-xl border border-gray-200 p-4">
            <p class="text-xs font-semibold text-gray-600 mb-3">Proyek per Status</p>
            @php
            $projStatusColors = ['planning'=>'bg-slate-400','active'=>'bg-blue-500','on_hold'=>'bg-yellow-400','completed'=>'bg-green-500','cancelled'=>'bg-red-400'];
            $projStatusLabels = ['planning'=>'Planning','active'=>'Active','on_hold'=>'On Hold','completed'=>'Completed','cancelled'=>'Cancelled'];
            $projTotal = $projectsByStatus->sum() ?: 1;
            @endphp
            <div class="space-y-2">
            @foreach($projectsByStatus as $status => $count)
            <div class="flex items-center gap-2">
                <span class="w-20 text-xs text-gray-600 shrink-0">{{ $projStatusLabels[$status] ?? $status }}</span>
                <div class="flex-1 bg-gray-100 rounded-full h-3 overflow-hidden">
                    <div class="h-3 rounded-full {{ $projStatusColors[$status] ?? 'bg-gray-400' }}" style="width:{{ round($count/$projTotal*100) }}%"></div>
                </div>
                <span class="w-6 text-xs text-right text-gray-600 font-semibold shrink-0">{{ $count }}</span>
            </div>
            @endforeach
            </div>
        </div>

    </div>

</div>

@push('scripts')
<script>
const weeklyData    = @json($weeks);
const statusData    = @json($tasksByStatus);
const ticketStatus  = @json($ticketsByStatus);
const dailyData     = @json($dailyCreated);

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: true,
    plugins: { legend: { labels: { font: { size: 10 }, boxWidth: 10, padding: 8 } } },
};

// Weekly bar
new Chart(document.getElementById('weeklyChart'), {
    type: 'bar',
    data: {
        labels: weeklyData.map(w => w.label),
        datasets: [
            { label: 'Dibuat',  data: weeklyData.map(w => w.created),   backgroundColor: '#93C5FD', borderRadius: 3, barPercentage: 0.6 },
            { label: 'Selesai', data: weeklyData.map(w => w.completed), backgroundColor: '#34D399', borderRadius: 3, barPercentage: 0.6 },
        ]
    },
    options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 9 } } } } }
});

// Status doughnut (small)
const statusLabels = { todo:'To Do', in_progress:'In Progress', review:'Review', done:'Done', blocked:'Blocked' };
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: Object.keys(statusData).map(k => statusLabels[k] || k),
        datasets: [{ data: Object.values(statusData), backgroundColor: ['#94A3B8','#60A5FA','#FBBF24','#34D399','#F87171'], borderWidth: 0, hoverOffset: 4 }]
    },
    options: { responsive: false, cutout: '65%', plugins: { legend: { display: false } } }
});

// Ticket status bar
const ticketStatusLabels = { open:'Open', assigned:'Assigned', in_progress:'In Progress', pending_review:'Pending Review', resolved:'Resolved', closed:'Closed' };
const ticketColors       = { open:'#FB923C', assigned:'#60A5FA', in_progress:'#818CF8', pending_review:'#FBBF24', resolved:'#34D399', closed:'#94A3B8' };
new Chart(document.getElementById('ticketStatusChart'), {
    type: 'bar',
    data: {
        labels: Object.keys(ticketStatus).map(k => ticketStatusLabels[k] || k),
        datasets: [{
            data: Object.values(ticketStatus),
            backgroundColor: Object.keys(ticketStatus).map(k => ticketColors[k] || '#94A3B8'),
            borderRadius: 3, barPercentage: 0.6,
        }]
    },
    options: { ...chartDefaults, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 9 } } } } }
});

// Daily trend line
new Chart(document.getElementById('dailyTrendChart'), {
    type: 'line',
    data: {
        labels: dailyData.map(d => d.label),
        datasets: [{
            label: 'Task Baru',
            data: dailyData.map(d => d.count),
            borderColor: '#6366F1', backgroundColor: 'rgba(99,102,241,0.08)',
            borderWidth: 2, pointRadius: 3, tension: 0.3, fill: true,
        }]
    },
    options: { ...chartDefaults, scales: { y: { beginAtZero: true, ticks: { font: { size: 10 } } }, x: { ticks: { font: { size: 9 } } } } }
});
</script>
@endpush
@endsection
