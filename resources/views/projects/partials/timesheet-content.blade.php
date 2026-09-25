    {{-- ============================================================
         GANTT CHART  (milestone + task + sprint gabung di sini)
    ============================================================ --}}
    @if($ganttTasks->count() || $sprints->count())
    @php
        $today = now()->startOfDay();
        $todayPct = max(0, min(100, round($ganttStart->diffInDays($today) / $ganttDays * 100, 2)));

        $pColors = [
            'todo'        => ['bar'=>'#6366f1','label'=>'To Do'],
            'in_progress' => ['bar'=>'#3b82f6','label'=>'In Progress'],
            'review'      => ['bar'=>'#a855f7','label'=>'Review'],
            'done'        => ['bar'=>'#22c55e','label'=>'Done'],
        ];

        // Build week markers
        $markers = collect();
        $cur = $ganttStart->copy()->startOfWeek();
        while ($cur->lte($ganttEnd)) {
            $pct = max(0, round($ganttStart->diffInDays($cur) / $ganttDays * 100, 2));
            $markers->push(['date' => $cur->copy(), 'pct' => $pct]);
            $cur->addWeek();
        }

        // Timeline minimum width: generous width so bars and dates are spacious and easily readable
        // Provides ample horizontal scroll space so gantt bars aren't tiny
        $timelineWidth = max(2200, $markers->count() * 100);
        $leftColWidth = 300; // px
        $totalCanvasWidth = $leftColWidth + $timelineWidth;

        // Group tasks by milestone
        $grouped = $ganttTasks->groupBy(fn($t) => $t->milestone?->title ?? 'Tanpa Milestone');
    @endphp

    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 mb-6 shadow-2xs overflow-hidden">
        {{-- Card Header: Title + Date Range + Export Buttons --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between flex-wrap gap-4">
            <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                </svg>
                Gantt Chart
            </h2>
            <div class="flex items-center gap-4 flex-wrap">
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 font-medium">
                    <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800">{{ $ganttStart->format('d M Y') }}</span>
                    <span>&rarr;</span>
                    <span class="px-2 py-0.5 rounded bg-gray-100 dark:bg-gray-800">{{ $ganttEnd->format('d M Y') }}</span>
                    <span class="text-gray-400 font-semibold">({{ $ganttDays }} hari)</span>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('export.timesheet.gantt.excel', $project) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Excel
                    </a>
                    <a href="{{ route('export.timesheet.gantt.pdf', $project) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/60 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        PDF
                    </a>
                </div>
            </div>
        </div>

        {{-- Horizontal Scrollable Gantt Canvas --}}
        <div class="overflow-x-auto relative">
            <div class="flex" style="min-width: {{ $totalCanvasWidth }}px;">

                {{-- ============================================================
                     LEFT COLUMN: STICKY TASK & SPRINT LABELS
                     ============================================================ --}}
                <div class="w-[300px] flex-shrink-0 sticky left-0 z-20 bg-white dark:bg-gray-850 border-r border-gray-200 dark:border-gray-700 shadow-[4px_0_12px_-2px_rgba(0,0,0,0.07)]">
                    {{-- Header Spacer (Height matches Right Date Header: h-10) --}}
                    <div class="h-10 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 px-4 flex items-center justify-between">
                        <span class="text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Task / Deliverable</span>
                        <span class="text-[10px] text-gray-400 font-mono">Sprint/MS</span>
                    </div>

                    {{-- Sprint Swimlane Header (h-8) --}}
                    @if($sprints->isNotEmpty())
                    <div class="h-8 px-4 bg-purple-50 dark:bg-purple-950/50 border-b border-purple-100 dark:border-purple-900/60 flex items-center justify-between">
                        <span class="text-xs font-bold text-purple-700 dark:text-purple-300 uppercase tracking-wider">Sprint</span>
                        <span class="text-[10px] text-purple-600 dark:text-purple-400 font-semibold">{{ $sprints->count() }} Sprints</span>
                    </div>
                    @foreach($sprints as $sprint)
                    <div class="h-11 relative px-4 bg-white dark:bg-gray-850 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-2" x-data="{edit:false}">
                        <div class="min-w-0">
                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate" title="{{ $sprint->name }}">
                                {{ $sprint->name }}
                            </p>
                            <p class="text-[10px] text-gray-400">
                                {{ $sprint->start_date?->format('d M') ?? '—' }} → {{ $sprint->end_date?->format('d M Y') ?? '—' }}
                            </p>
                        </div>
                        @if(!auth()->user()->hasRole('client'))
                        <button type="button" @click="edit = !edit" @click.outside="edit = false"
                                class="text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 p-1 rounded shrink-0 cursor-pointer" title="Edit tanggal sprint">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </button>

                        <div x-show="edit" x-cloak x-transition
                             class="absolute z-30 left-4 top-full mt-1 w-64 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-xl p-3.5">
                            <form method="POST" action="{{ route('sprints.update', [$project, $sprint]) }}" class="space-y-2.5">
                                @csrf @method('PUT')
                                <input type="hidden" name="name" value="{{ $sprint->name }}">
                                <input type="hidden" name="goal" value="{{ $sprint->goal }}">
                                <input type="hidden" name="status" value="{{ $sprint->status }}">
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 dark:text-gray-300 mb-0.5">Mulai</label>
                                    <input type="date" name="start_date" value="{{ $sprint->start_date?->format('Y-m-d') }}"
                                           class="w-full px-2.5 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <div>
                                    <label class="block text-[10px] font-semibold text-gray-600 dark:text-gray-300 mb-0.5">Selesai</label>
                                    <input type="date" name="end_date" value="{{ $sprint->end_date?->format('Y-m-d') }}"
                                           class="w-full px-2.5 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg text-xs bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                </div>
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold py-1.5 rounded-lg transition">Simpan</button>
                            </form>
                        </div>
                        @endif
                    </div>
                    @endforeach
                    @endif

                    {{-- Milestone Groups and Tasks --}}
                    @foreach($grouped as $milestoneName => $mTasks)
                        <div class="h-8 px-4 bg-blue-50 dark:bg-blue-950/50 border-b border-blue-100 dark:border-blue-900/60 flex items-center justify-between">
                            <span class="text-xs font-bold text-blue-700 dark:text-blue-300 truncate">{{ $milestoneName }}</span>
                            <span class="text-[10px] text-blue-600 dark:text-blue-400 font-semibold">{{ $mTasks->count() }} Tasks</span>
                        </div>
                        @foreach($mTasks as $t)
                        <div class="h-12 px-4 bg-white dark:bg-gray-850 border-b border-gray-100 dark:border-gray-800 flex items-center hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition">
                            <div class="flex items-center gap-2.5 min-w-0 w-full">
                                @if($t->assignee)
                                    @if($t->assignee->avatar)
                                        <img src="{{ Storage::url($t->assignee->avatar) }}" alt="{{ $t->assignee->name }}" class="w-5 h-5 rounded-full object-cover shrink-0">
                                    @else
                                        <div class="w-5 h-5 rounded-full ring-1 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[9px] font-bold shrink-0 shadow-2xs"
                                             style="background-color: {{ $t->assignee->avatarColor() }};"
                                             title="{{ $t->assignee->name }}">
                                            {{ $t->assignee->initials() }}
                                        </div>
                                    @endif
                                @else
                                    <div class="w-5 h-5 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-400 flex items-center justify-center text-[9px] shrink-0">
                                        -
                                    </div>
                                @endif
                                <div class="min-w-0 flex-1">
                                    <button type="button"
                                       @click="if(window.openTask) window.openTask({{ $t->id }})"
                                       class="block text-xs font-medium text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 truncate text-left cursor-pointer transition"
                                       title="{{ $t->title }}">
                                        {{ $t->title }}
                                    </button>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="inline-flex items-center gap-1 text-[9px] px-1.5 py-0.2 rounded font-medium
                                            {{ $t->sprint ? 'bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300' : 'bg-gray-100 dark:bg-gray-800 text-gray-500' }}">
                                            <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                            {{ $t->sprint->name ?? 'Backlog' }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @endforeach
                </div>

                {{-- ============================================================
                     RIGHT COLUMN: GANTT TIMELINE & BARS
                     ============================================================ --}}
                <div class="relative flex-shrink-0" style="width: {{ $timelineWidth }}px;">
                    {{-- Date Headers (h-10 matching Left spacer) --}}
                    <div class="h-10 border-b border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800 relative">
                        @foreach($markers as $m)
                        <div class="absolute top-0 bottom-0 flex items-center" style="left: {{ $m['pct'] }}%">
                            <div class="border-l border-gray-200 dark:border-gray-700 h-full absolute"></div>
                            <span class="text-[11px] font-semibold text-gray-500 dark:text-gray-400 pl-1.5 whitespace-nowrap">{{ $m['date']->format('d M') }}</span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Today Marker Line --}}
                    @if($todayPct >= 0 && $todayPct <= 100)
                    <div class="absolute top-10 bottom-0 w-px bg-rose-500 z-10 pointer-events-none" style="left: {{ $todayPct }}%">
                        <div class="absolute -top-1 -left-5 text-[10px] font-bold text-white bg-rose-500 px-1.5 py-0.2 rounded shadow-xs whitespace-nowrap">
                            Hari ini
                        </div>
                    </div>
                    @endif

                    {{-- Sprint Swimlane Spacer Row (h-8) --}}
                    @if($sprints->isNotEmpty())
                    <div class="h-8 bg-purple-50/50 dark:bg-purple-950/20 border-b border-purple-100 dark:border-purple-900/60 relative">
                        @foreach($markers as $m)
                        <div class="absolute top-0 bottom-0 border-l border-gray-100 dark:border-gray-800/80" style="left: {{ $m['pct'] }}%"></div>
                        @endforeach
                    </div>
                    @foreach($sprints as $sprint)
                    @php
                        $sHasDates = $sprint->start_date && $sprint->end_date;
                        $sStart = $sprint->start_date ?? $ganttStart;
                        $sEnd   = $sprint->end_date   ?? $ganttEnd;
                        if ($sEnd->lt($sStart)) $sEnd = $sStart->copy()->addDay();
                        $sLeft  = max(0, min(100, round($ganttStart->diffInDays($sStart) / $ganttDays * 100, 2)));
                        $sWidth = max(0.5, min(100 - $sLeft, round($sStart->diffInDays($sEnd) / $ganttDays * 100, 2)));
                    @endphp
                    <div class="h-11 relative border-b border-gray-100 dark:border-gray-800">
                        @foreach($markers as $m)
                        <div class="absolute top-0 bottom-0 border-l border-gray-100 dark:border-gray-800/80" style="left: {{ $m['pct'] }}%"></div>
                        @endforeach
                        @if($sHasDates)
                        <div class="absolute top-1/2 -translate-y-1/2 rounded-lg h-6 flex items-center px-2.5 overflow-hidden shadow-xs hover:brightness-110 transition cursor-pointer"
                             style="left: {{ $sLeft }}%; width: {{ $sWidth }}%; background: linear-gradient(90deg, #9333ea, #6366f1);"
                             title="{{ $sprint->name }}: {{ $sStart->format('d M') }} → {{ $sEnd->format('d M Y') }}">
                            <span class="text-white text-xs truncate font-semibold drop-shadow-xs">{{ $sprint->name }}</span>
                        </div>
                        @else
                        <span class="absolute top-1/2 -translate-y-1/2 left-2 text-[10px] text-gray-300 italic">Belum ada tanggal</span>
                        @endif
                    </div>
                    @endforeach
                    @endif

                    {{-- Milestone Groups & Task Rows --}}
                    @foreach($grouped as $milestoneName => $mTasks)
                    {{-- Milestone Row Spacer (h-8) --}}
                    <div class="h-8 bg-blue-50/50 dark:bg-blue-950/20 border-b border-blue-100 dark:border-blue-900/60 relative">
                        @foreach($markers as $m)
                        <div class="absolute top-0 bottom-0 border-l border-gray-100 dark:border-gray-800/80" style="left: {{ $m['pct'] }}%"></div>
                        @endforeach
                    </div>

                    @foreach($mTasks as $t)
                    @php
                        $barStart = $t->start_date ?? $t->due_date ?? $t->sprint?->start_date ?? $ganttStart;
                        $barEnd   = $t->due_date   ?? $t->start_date ?? $t->sprint?->end_date   ?? $ganttEnd;
                        if ($barEnd->lt($barStart)) $barEnd = $barStart->copy()->addDay();

                        $barLeft  = max(0, min(100, round($ganttStart->diffInDays($barStart) / $ganttDays * 100, 2)));
                        $barWidth = max(0.5, min(100 - $barLeft, round($barStart->diffInDays($barEnd) / $ganttDays * 100, 2)));
                        $barColor = $pColors[$t->status]['bar'] ?? '#6366f1';
                        $barOpacity = $t->status === 'done' ? '0.75' : '1';

                        // Time log segments
                        $logSegs = $t->timeLogs->filter(fn($l) => $l->started_at && $l->ended_at)->map(function($l) use ($ganttStart, $ganttDays) {
                            $ls = max(0, min(100, round($ganttStart->diffInDays($l->started_at->startOfDay()) / $ganttDays * 100, 2)));
                            $lw = max(0.3, min(100 - $ls, round(max(0.016, $l->minutes / (60 * 24)) / $ganttDays * 100, 2)));
                            return ['left' => $ls, 'width' => $lw];
                        });
                    @endphp
                    <div class="h-12 relative border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50/40 dark:hover:bg-gray-800/30 transition">
                        {{-- Grid lines --}}
                        @foreach($markers as $m)
                        <div class="absolute top-0 bottom-0 border-l border-gray-100 dark:border-gray-800/80" style="left: {{ $m['pct'] }}%"></div>
                        @endforeach

                        {{-- Task bar --}}
                        <div class="absolute top-1/2 -translate-y-1/2 rounded-lg h-6 flex items-center overflow-hidden shadow-xs cursor-pointer hover:shadow-md hover:brightness-105 transition"
                             style="left: {{ $barLeft }}%; width: {{ $barWidth }}%; background-color: {{ $barColor }}; opacity: {{ $barOpacity }};"
                             @click="if(window.openTask) window.openTask({{ $t->id }})"
                             title="{{ $t->title }} [{{ $t->sprint->name ?? 'Backlog' }}]: {{ $barStart->format('d M') }} → {{ $barEnd->format('d M Y') }}">
                            {{-- Time log marks --}}
                            @foreach($logSegs as $seg)
                            <div class="absolute h-full bg-white bg-opacity-30 rounded"
                                 style="left: {{ max(0, ($seg['left'] - $barLeft) / $barWidth * 100) }}%; width: {{ min(100, $seg['width'] / $barWidth * 100) }}%"></div>
                            @endforeach
                            <span class="text-white text-xs px-2 truncate font-semibold drop-shadow-xs relative z-10"
                                  style="font-size:11px">{{ $t->title }}</span>
                        </div>
                    </div>
                    @endforeach
                    @endforeach
                </div>

            </div>
        </div>

        {{-- Legend (Outside the scroll area so always visible across the card) --}}
        <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50/80 dark:bg-gray-800/70 flex items-center gap-6 flex-wrap">
            @foreach(['todo'=>'To Do','in_progress'=>'In Progress','review'=>'Review','done'=>'Done'] as $s=>$sl)
            <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                <span class="w-3.5 h-3.5 rounded-md inline-block shadow-2xs" style="background:{{ $pColors[$s]['bar'] }}"></span>
                {{ $sl }}
            </div>
            @endforeach
            @if($sprints->isNotEmpty())
            <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                <span class="w-3.5 h-3.5 rounded-md inline-block shadow-2xs" style="background: linear-gradient(90deg,#9333ea,#6366f1);"></span>
                Sprint
            </div>
            @endif
            <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 ml-auto">
                <span class="w-4 h-0.5 bg-rose-500 inline-block rounded-full"></span>
                Hari ini
            </div>
        </div>
    </div>
    @else
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 px-6 py-12 text-center text-sm text-gray-400 mb-6">
        Tidak ada task dengan tanggal mulai/selesai untuk ditampilkan di Gantt.
    </div>
    @endif

    {{-- ============================================================
         HIDDEN PER USER REQUEST:
         - Ringkasan per Developer (Summary Table)
         - Detail Log Waktu (Detail Logs Table)
         Focus solely on the Gantt Chart.
    ============================================================ --}}
    @if(false)
    <div class="bg-white rounded-xl border border-gray-200 mb-6">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">Ringkasan per Developer</h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('export.timesheet.summary.excel', $project) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
                </a>
                <a href="{{ route('export.timesheet.summary.pdf', $project) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </a>
            </div>
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
                                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-semibold text-xs uppercase">
                                    {{ substr($row['user']->name ?? '?', 0, 2) }}
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-900">{{ $row['user']->name ?? '-' }}</p>
                                    <p class="text-xs text-gray-500">{{ $row['user']->email ?? '' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm font-semibold text-blue-700">{{ number_format($row['total_hours'], 2) }} jam</span>
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
                        <td class="px-6 py-3 text-sm font-bold text-blue-700">{{ number_format(collect($summary)->sum('total_hours'), 2) }} jam</td>
                        <td class="px-6 py-3 text-sm font-bold text-gray-700">{{ collect($summary)->sum('logs_count') }} log</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    @php $displayLogs = $logs ?? $recentLogs; @endphp
    <div class="bg-white rounded-xl border border-gray-200">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">
                Detail Log Waktu
                @if(!isset($logs) && isset($recentLogsTotal))
                <span class="text-gray-400 font-normal text-sm">({{ min($recentLogs->count(), $recentLogsTotal) }} dari {{ $recentLogsTotal }})</span>
                @endif
            </h2>
            <div class="flex items-center gap-2">
                <a href="{{ route('export.timesheet.excel', $project) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-green-50 text-green-700 hover:bg-green-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    Excel
                </a>
                <a href="{{ route('export.timesheet.pdf', $project) }}"
                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium bg-red-50 text-red-700 hover:bg-red-100 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    PDF
                </a>
            </div>
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
                    @forelse($displayLogs as $log)
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
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
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
        @if(isset($logs))
        @if($logs->hasPages())
        <div class="px-6 py-4 border-t border-gray-100">{{ $logs->links() }}</div>
        @endif
        @elseif(isset($recentLogsTotal) && $recentLogsTotal > $recentLogs->count())
        <div class="px-6 py-3 border-t border-gray-100 text-center">
            <a href="{{ route('projects.timesheet', $project) }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                Lihat semua log &rarr;
            </a>
        </div>
        @endif
    </div>
    @endif
