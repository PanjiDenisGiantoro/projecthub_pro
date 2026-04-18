@extends('layouts.app')
@section('title', 'Timesheet: ' . $project->name)

@section('content')
<div class="py-4">

    {{-- Header --}}
    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('projects.show', $project) }}"
           class="p-2 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
            </svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-gray-900">Timesheet</h1>
            <p class="text-sm text-gray-500">{{ $project->name }}</p>
        </div>
    </div>

    {{-- ============================================================
         GANTT CHART
    ============================================================ --}}
    @if($ganttTasks->count())
    @php
        $today = now()->startOfDay();
        $todayPct = max(0, min(100, round($ganttStart->diffInDays($today) / $ganttDays * 100, 2)));

        $pColors = [
            'todo'        => ['bar'=>'#6366f1','label'=>'Indigo'],
            'in_progress' => ['bar'=>'#3b82f6','label'=>'Blue'],
            'review'      => ['bar'=>'#a855f7','label'=>'Purple'],
            'done'        => ['bar'=>'#22c55e','label'=>'Green'],
        ];

        // Build week markers
        $markers = collect();
        $cur = $ganttStart->copy()->startOfWeek();
        while ($cur->lte($ganttEnd)) {
            $pct = max(0, round($ganttStart->diffInDays($cur) / $ganttDays * 100, 2));
            $markers->push(['date' => $cur->copy(), 'pct' => $pct]);
            $cur->addWeek();
        }

        // Group tasks by milestone
        $grouped = $ganttTasks->groupBy(fn($t) => $t->milestone?->title ?? 'Tanpa Milestone');
    @endphp

    <div class="bg-white rounded-xl border border-gray-200 mb-6 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900 flex items-center gap-2">
                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                Gantt Chart
            </h2>
            <div class="flex items-center gap-4 text-xs text-gray-500">
                <span>{{ $ganttStart->format('d M Y') }}</span>
                <span>→</span>
                <span>{{ $ganttEnd->format('d M Y') }}</span>
                <span class="text-gray-400">({{ $ganttDays }} hari)</span>
            </div>
        </div>

        <div class="overflow-x-auto">
            <div style="min-width: max(700px, 100%);">
                {{-- Layout: left labels + right chart --}}
                <div class="flex">
                    {{-- Left: task labels --}}
                    <div class="flex-shrink-0 w-56 border-r border-gray-100">
                        {{-- Header spacer --}}
                        <div class="h-8 border-b border-gray-100 bg-gray-50 px-3 flex items-center">
                            <span class="text-xs font-medium text-gray-500">Task</span>
                        </div>
                        @foreach($grouped as $milestoneName => $mTasks)
                            <div class="px-3 py-1.5 bg-indigo-50 border-b border-indigo-100">
                                <span class="text-xs font-semibold text-indigo-600">{{ $milestoneName }}</span>
                            </div>
                            @foreach($mTasks as $t)
                            <div class="px-3 border-b border-gray-50 flex items-center" style="height:40px">
                                <div class="flex items-center gap-2 min-w-0">
                                    @if($t->assignee)
                                    <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center flex-shrink-0">
                                        {{ strtoupper(substr($t->assignee->name, 0, 1)) }}
                                    </div>
                                    @endif
                                    <a href="{{ route('tasks.show', [$project, $t]) }}"
                                       class="text-xs text-gray-700 hover:text-blue-600 truncate" title="{{ $t->title }}">
                                        {{ $t->title }}
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        @endforeach
                    </div>

                    {{-- Right: bars --}}
                    <div class="flex-1 relative">
                        {{-- Date headers --}}
                        <div class="h-8 border-b border-gray-100 bg-gray-50 relative">
                            @foreach($markers as $m)
                            <div class="absolute top-0 bottom-0 flex items-center" style="left: {{ $m['pct'] }}%">
                                <div class="border-l border-gray-200 h-full absolute"></div>
                                <span class="text-xs text-gray-400 pl-1 whitespace-nowrap">{{ $m['date']->format('d M') }}</span>
                            </div>
                            @endforeach
                        </div>

                        {{-- Today marker --}}
                        @if($todayPct >= 0 && $todayPct <= 100)
                        <div class="absolute top-8 bottom-0 w-px bg-red-400 z-10" style="left: {{ $todayPct }}%">
                            <div class="absolute -top-1 -left-5 text-xs text-red-500 font-medium whitespace-nowrap bg-white px-1 rounded">
                                Hari ini
                            </div>
                        </div>
                        @endif

                        {{-- Task rows --}}
                        @foreach($grouped as $milestoneName => $mTasks)
                        {{-- Milestone row spacer --}}
                        <div class="py-1.5 px-2 bg-indigo-50 border-b border-indigo-100 relative" style="height:30px">
                            @foreach($markers as $m)
                            <div class="absolute top-0 bottom-0 border-l border-gray-100" style="left: {{ $m['pct'] }}%"></div>
                            @endforeach
                        </div>

                        @foreach($mTasks as $t)
                        @php
                            $barStart = $t->start_date ?? $t->due_date ?? $ganttStart;
                            $barEnd   = $t->due_date   ?? $t->start_date ?? $ganttEnd;
                            if ($barEnd->lt($barStart)) $barEnd = $barStart->copy()->addDay();

                            $barLeft  = max(0, min(100, round($ganttStart->diffInDays($barStart) / $ganttDays * 100, 2)));
                            $barWidth = max(0.5, min(100 - $barLeft, round($barStart->diffInDays($barEnd) / $ganttDays * 100, 2)));
                            $barColor = $pColors[$t->status]['bar'] ?? '#6366f1';
                            $barOpacity = $t->status === 'done' ? '0.7' : '1';

                            // Time log segments
                            $logSegs = $t->timeLogs->filter(fn($l) => $l->started_at && $l->ended_at)->map(function($l) use ($ganttStart, $ganttDays) {
                                $ls = max(0, min(100, round($ganttStart->diffInDays($l->started_at->startOfDay()) / $ganttDays * 100, 2)));
                                $lw = max(0.3, min(100 - $ls, round(max(0.016, $l->minutes / (60 * 24)) / $ganttDays * 100, 2)));
                                return ['left' => $ls, 'width' => $lw];
                            });
                        @endphp
                        <div class="relative border-b border-gray-50" style="height:40px">
                            {{-- Grid lines --}}
                            @foreach($markers as $m)
                            <div class="absolute top-0 bottom-0 border-l border-gray-100" style="left: {{ $m['pct'] }}%"></div>
                            @endforeach

                            {{-- Task bar --}}
                            <div class="absolute top-1/2 -translate-y-1/2 rounded h-5 flex items-center overflow-hidden"
                                 style="left: {{ $barLeft }}%; width: {{ $barWidth }}%; background-color: {{ $barColor }}; opacity: {{ $barOpacity }};"
                                 title="{{ $t->title }}: {{ $barStart->format('d M') }} → {{ $barEnd->format('d M Y') }}">
                                {{-- Time log marks --}}
                                @foreach($logSegs as $seg)
                                <div class="absolute h-full bg-white bg-opacity-30 rounded"
                                     style="left: {{ max(0, ($seg['left'] - $barLeft) / $barWidth * 100) }}%; width: {{ min(100, $seg['width'] / $barWidth * 100) }}%"></div>
                                @endforeach
                                <span class="text-white text-xs px-1.5 truncate font-medium relative z-10 hidden sm:block"
                                      style="font-size:10px">{{ $t->title }}</span>
                            </div>
                        </div>
                        @endforeach
                        @endforeach
                    </div>
                </div>

                {{-- Legend --}}
                <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex items-center gap-5 flex-wrap">
                    @foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $s=>$sl)
                    <div class="flex items-center gap-1.5 text-xs text-gray-600">
                        <span class="w-3 h-3 rounded-sm inline-block" style="background:{{ $pColors[$s]['bar'] }}"></span>
                        {{ $sl }}
                    </div>
                    @endforeach
                    <div class="flex items-center gap-1.5 text-xs text-gray-600 ml-auto">
                        <span class="w-3 h-0.5 bg-red-400 inline-block"></span>
                        Hari ini
                    </div>
                </div>
            </div>
        </div>
    </div>
    @else
    <div class="bg-white rounded-xl border border-gray-200 px-6 py-10 text-center text-sm text-gray-400 mb-6">
        Tidak ada task dengan tanggal mulai/selesai untuk ditampilkan di Gantt.
    </div>
    @endif

    {{-- ============================================================
         SUMMARY TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Ringkasan per Developer</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Developer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Total Jam</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Jumlah Log</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($summary as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-semibold text-xs uppercase">
                                    {{ substr($row['user']->name ?? '?', 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $row['user']->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $row['user']->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-indigo-700">{{ number_format($row['total_hours'], 2) }} jam</span>
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-600">{{ $row['logs_count'] }} log</td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="px-6 py-8 text-center text-sm text-gray-400">Belum ada data timesheet.</td></tr>
                    @endforelse
                </tbody>
                @if(count($summary) > 0)
                <tfoot class="bg-gray-50 border-t border-gray-200">
                    <tr>
                        <td class="px-6 py-3 text-xs font-semibold text-gray-500 uppercase">Total Keseluruhan</td>
                        <td class="px-6 py-3 text-sm font-bold text-indigo-700">{{ number_format(collect($summary)->sum('total_hours'), 2) }} jam</td>
                        <td class="px-6 py-3 text-sm font-bold text-gray-700">{{ collect($summary)->sum('logs_count') }} log</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- ============================================================
         DETAIL LOGS TABLE
    ============================================================ --}}
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-semibold text-gray-900">Detail Log Waktu</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Developer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Task</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Mulai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Selesai</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Durasi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wide">Catatan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($logs as $log)
                    @php
                        $minutes = $log->minutes ?? 0;
                        $hours   = floor($minutes / 60);
                        $mins    = $minutes % 60;
                        $dur     = $hours > 0 ? $hours . 'j ' . ($mins > 0 ? $mins . 'm' : '') : $mins . 'm';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm font-medium text-gray-900">{{ $log->user->name ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-700">{{ $log->task->title ?? '-' }}</td>
                        <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">
                            {{ $log->started_at ? \Carbon\Carbon::parse($log->started_at)->format('d M Y, H:i') : '-' }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-600 whitespace-nowrap">
                            {{ $log->ended_at ? \Carbon\Carbon::parse($log->ended_at)->format('d M Y, H:i') : '—' }}
                        </td>
                        <td class="px-6 py-3">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-700">
                                {{ trim($dur) }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-500 max-w-xs truncate" title="{{ $log->notes }}">
                            {{ $log->notes ?: '—' }}
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-sm text-gray-400">Belum ada log waktu.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
