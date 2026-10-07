    {{-- ============================================================
         GANTT CHART (Milestone + Task + Sprint)
         Default: Menampilkan 1 bulan berjalan saat ini dengan kolom per tanggal lebar
    ============================================================ --}}
    @php
        $today = now()->startOfDay();

        $pColors = [
            'todo'        => ['bar'=>'#6366f1','label'=>'To Do'],
            'in_progress' => ['bar'=>'#3b82f6','label'=>'In Progress'],
            'review'      => ['bar'=>'#a855f7','label'=>'Review'],
            'done'        => ['bar'=>'#22c55e','label'=>'Done'],
        ];

        // Buat daftar semua hari dalam rentang tanggal yang dipilih
        $days = collect();
        $cur = $ganttStart->copy()->startOfDay();
        while ($cur->lte($ganttEnd)) {
            $days->push($cur->copy());
            $cur->addDay();
        }
        $totalDays = max(1, $days->count());

        // Lebar kolom per tanggal: 68px agar lega, jelas tiap hari, dan scroll horizontal lancar
        $dayWidth = 68;
        $timelineWidth = $totalDays * $dayWidth;
        $leftColWidth = 320; // px
        $totalCanvasWidth = $leftColWidth + $timelineWidth;

        // Index hari ini dalam timeline
        $diffToday = (int) $ganttStart->diffInDays($today, false);
        $todayIndex = ($diffToday >= 0 && $diffToday < $totalDays) ? $diffToday : null;

        // Group tasks by milestone
        $grouped = $ganttTasks->groupBy(fn($t) => $t->milestone?->title ?? 'Tanpa Milestone');
        $hasData = $ganttTasks->isNotEmpty() || $sprints->isNotEmpty();

        $dayNames = [1=>'Sen', 2=>'Sel', 3=>'Rab', 4=>'Kam', 5=>'Jum', 6=>'Sab', 7=>'Min'];
    @endphp

    {{-- Container Section Gantt Chart: Tanpa Border dan Tanpa Shadow per request --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl mb-6 overflow-hidden">
        {{-- Card Header: Title + Period + Month Quick Switcher + Start/End Filter + Export Buttons --}}
        <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-3 flex-wrap">
                <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                    </svg>
                    Gantt Chart
                </h2>
                <div class="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 font-medium">
                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 font-semibold text-gray-700 dark:text-gray-300">{{ $ganttStart->format('d M Y') }}</span>
                    <span>&rarr;</span>
                    <span class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 font-semibold text-gray-700 dark:text-gray-300">{{ $ganttEnd->format('d M Y') }}</span>
                    <span class="text-gray-400 font-medium">({{ $ganttDays }} hari)</span>
                </div>
            </div>

            {{-- Controls: Quick Month Nav + Custom Date Filter + Export --}}
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Quick Month Switcher --}}
                <div class="inline-flex items-center bg-gray-100 dark:bg-gray-800 rounded-xl p-0.5">
                    <a href="{{ request()->fullUrlWithQuery([
                            'start_date' => $ganttStart->copy()->subMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
                            'end_date'   => $ganttStart->copy()->subMonthNoOverflow()->endOfMonth()->format('Y-m-d')
                        ]) }}"
                       class="p-1.5 rounded-lg text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition"
                       title="Bulan sebelumnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    </a>
                    <span class="px-2.5 text-xs font-bold text-gray-800 dark:text-gray-200 whitespace-nowrap">
                        {{ $ganttStart->format('M Y') }}
                    </span>
                    <a href="{{ request()->fullUrlWithQuery([
                            'start_date' => $ganttStart->copy()->addMonthNoOverflow()->startOfMonth()->format('Y-m-d'),
                            'end_date'   => $ganttStart->copy()->addMonthNoOverflow()->endOfMonth()->format('Y-m-d')
                        ]) }}"
                       class="p-1.5 rounded-lg text-gray-500 hover:text-gray-900 dark:text-gray-400 dark:hover:text-white hover:bg-white dark:hover:bg-gray-700 transition"
                       title="Bulan berikutnya">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    </a>
                </div>

                {{-- Start & End Date Filter Form --}}
                <form method="GET" action="{{ url()->current() }}" class="flex items-center gap-1.5 flex-wrap">
                    @foreach(request()->except(['start_date', 'end_date']) as $k => $v)
                        @if(is_string($v))
                            <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endif
                    @endforeach

                    <div class="flex items-center gap-1 bg-gray-50 dark:bg-gray-800 rounded-lg px-2 py-1 border border-gray-200 dark:border-gray-700">
                        <span class="text-[10px] font-semibold text-gray-400 uppercase">Mulai</span>
                        <input type="date" name="start_date" value="{{ request('start_date', $ganttStart->format('Y-m-d')) }}"
                               class="bg-transparent border-0 p-0 text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-0">
                    </div>
                    <span class="text-xs text-gray-400">-</span>
                    <div class="flex items-center gap-1 bg-gray-50 dark:bg-gray-800 rounded-lg px-2 py-1 border border-gray-200 dark:border-gray-700">
                        <span class="text-[10px] font-semibold text-gray-400 uppercase">Akhir</span>
                        <input type="date" name="end_date" value="{{ request('end_date', $ganttEnd->format('Y-m-d')) }}"
                               class="bg-transparent border-0 p-0 text-xs font-semibold text-gray-800 dark:text-gray-200 focus:outline-none focus:ring-0">
                    </div>
                    <button type="submit"
                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-2xs cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                        Filter
                    </button>
                    @if(request('start_date') || request('end_date'))
                    <a href="{{ request()->url() }}"
                       class="px-2 py-1.5 rounded-lg text-xs font-medium text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white hover:bg-gray-100 dark:hover:bg-gray-800 transition"
                       title="Kembali ke bulan berjalan saat ini">
                        Bulan Ini
                    </a>
                    @endif
                </form>

                {{-- Export Buttons --}}
                <div class="flex items-center gap-1.5 pl-2 border-l border-gray-200 dark:border-gray-700">
                    <a href="{{ route('export.timesheet.gantt.excel', array_merge(['project' => $project], request()->only('start_date', 'end_date'))) }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/50 dark:text-emerald-300 dark:hover:bg-emerald-900/60 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        Excel
                    </a>
                    <a href="{{ route('export.timesheet.gantt.pdf', array_merge(['project' => $project], request()->only('start_date', 'end_date'))) }}"
                       class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300 dark:hover:bg-rose-900/60 transition shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        PDF
                    </a>
                </div>
            </div>
        </div>

        @if($hasData)
        {{-- Horizontal Scrollable Gantt Canvas --}}
        <div class="overflow-x-auto relative">
            <div class="flex" style="min-width: {{ $totalCanvasWidth }}px;">

                {{-- ============================================================
                     LEFT COLUMN: STICKY TASK & SPRINT LABELS
                     ============================================================ --}}
                <div class="w-[320px] flex-shrink-0 sticky left-0 z-20 bg-white dark:bg-gray-850 border-r border-gray-200 dark:border-gray-700">
                    {{-- Header Spacer (Height matches Right Date Header: h-14) --}}
                    <div class="h-14 border-b border-gray-200 dark:border-gray-700 bg-gray-50/90 dark:bg-gray-800/90 px-4 flex items-center justify-between">
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
                     RIGHT COLUMN: GANTT TIMELINE & BARS (DAILY TIAP TANGGAL)
                     ============================================================ --}}
                <div class="relative flex-shrink-0" style="width: {{ $timelineWidth }}px;">
                    {{-- Date Headers (h-14 matching Left spacer): Tiap tanggal lebar & jelas --}}
                    <div class="h-14 border-b border-gray-200 dark:border-gray-700 bg-gray-50/90 dark:bg-gray-800/90 flex">
                        @foreach($days as $day)
                        @php
                            $isToday = $day->isSameDay($today);
                            $isWeekend = in_array($day->dayOfWeekIso, [6, 7], true);
                            $dayName = $dayNames[$day->dayOfWeekIso] ?? $day->format('D');
                        @endphp
                        <div class="flex-shrink-0 flex flex-col items-center justify-center border-r border-gray-200/80 dark:border-gray-700/60 {{ $isWeekend ? 'bg-gray-100/60 dark:bg-gray-900/40' : '' }} {{ $isToday ? 'bg-blue-50/80 dark:bg-blue-950/50' : '' }}"
                             style="width: {{ $dayWidth }}px;">
                            <span class="text-[10px] font-semibold uppercase tracking-wider {{ $isToday ? 'text-blue-600 dark:text-blue-400 font-bold' : ($isWeekend ? 'text-gray-400 dark:text-gray-500' : 'text-gray-500 dark:text-gray-400') }}">
                                {{ $dayName }}
                            </span>
                            <span class="mt-0.5 text-xs font-bold inline-flex items-center justify-center {{ $isToday ? 'w-6 h-6 rounded-full bg-blue-600 text-white shadow-2xs' : ($isWeekend ? 'text-gray-400 dark:text-gray-500' : 'text-gray-800 dark:text-gray-200') }}">
                                {{ $day->format('d') }}
                            </span>
                        </div>
                        @endforeach
                    </div>

                    {{-- Today Marker Vertical Line --}}
                    @if($todayIndex !== null)
                    <div class="absolute top-14 bottom-0 pointer-events-none z-10 w-0.5 bg-blue-500/80 dark:bg-blue-400"
                         style="left: {{ ($todayIndex * $dayWidth) + ($dayWidth / 2) }}px;">
                    </div>
                    @endif

                    {{-- Sprint Swimlane Spacer Row (h-8) --}}
                    @if($sprints->isNotEmpty())
                    <div class="h-8 bg-purple-50/50 dark:bg-purple-950/20 border-b border-purple-100 dark:border-purple-900/60 relative">
                        <div class="absolute inset-0 flex pointer-events-none">
                            @foreach($days as $day)
                                @php $isWk = in_array($day->dayOfWeekIso, [6, 7], true); @endphp
                                <div class="flex-shrink-0 h-full border-r border-gray-100 dark:border-gray-800/60 {{ $isWk ? 'bg-purple-100/20 dark:bg-purple-900/20' : '' }}"
                                     style="width: {{ $dayWidth }}px;"></div>
                            @endforeach
                        </div>
                    </div>

                    @foreach($sprints as $sprint)
                    @php
                        $sHasDates = $sprint->start_date && $sprint->end_date;
                        $sStart = ($sprint->start_date ?? $ganttStart)->copy()->startOfDay();
                        $sEnd   = ($sprint->end_date   ?? $ganttEnd)->copy()->startOfDay();
                        if ($sEnd->lt($sStart)) $sEnd = $sStart->copy();

                        $sDiffStart = (int) $ganttStart->diffInDays($sStart, false);
                        $sDiffEnd   = (int) $ganttStart->diffInDays($sEnd, false);

                        $sVisStart = max(0, $sDiffStart);
                        $sVisEnd   = min($totalDays - 1, $sDiffEnd);
                        $sSpanDays = max(1, ($sVisEnd - $sVisStart) + 1);

                        $sLeftPx = $sVisStart * $dayWidth;
                        $sWidthPx = $sSpanDays * $dayWidth;

                        $sStartsBefore = ($sDiffStart < 0);
                        $sEndsAfter    = ($sDiffEnd >= $totalDays);

                        $sLeftStyle = $sStartsBefore ? '0px' : ($sLeftPx + 3) . 'px';
                        $sWidthStyle = max(28, $sWidthPx - ($sStartsBefore ? 3 : 6) - ($sEndsAfter ? 0 : 3)) . 'px';

                        $sRounded = 'rounded-lg';
                        if ($sStartsBefore && $sEndsAfter) {
                            $sRounded = 'rounded-none';
                        } elseif ($sStartsBefore) {
                            $sRounded = 'rounded-r-lg rounded-l-none';
                        } elseif ($sEndsAfter) {
                            $sRounded = 'rounded-l-lg rounded-r-none';
                        }
                    @endphp
                    <div class="h-11 relative border-b border-gray-100 dark:border-gray-800">
                        {{-- Grid lines --}}
                        <div class="absolute inset-0 flex pointer-events-none">
                            @foreach($days as $day)
                                @php $isWk = in_array($day->dayOfWeekIso, [6, 7], true); @endphp
                                <div class="flex-shrink-0 h-full border-r border-gray-100 dark:border-gray-800/60 {{ $isWk ? 'bg-gray-50/50 dark:bg-gray-900/30' : '' }}"
                                     style="width: {{ $dayWidth }}px;"></div>
                            @endforeach
                        </div>

                        @if($sHasDates)
                        <div class="absolute top-1/2 -translate-y-1/2 h-6 flex items-center px-2.5 overflow-hidden shadow-xs hover:brightness-110 transition cursor-pointer {{ $sRounded }}"
                             style="left: {{ $sLeftStyle }}; width: {{ $sWidthStyle }}; background: linear-gradient(90deg, #9333ea, #6366f1);"
                             title="{{ $sprint->name }}: {{ $sStart->format('d M') }} → {{ $sEnd->format('d M Y') }}">
                            <span class="text-white text-xs truncate font-semibold drop-shadow-xs flex items-center gap-1">
                                @if($sStartsBefore)
                                    <span class="opacity-75 text-[10px]">&laquo;</span>
                                @endif
                                <span class="truncate">{{ $sprint->name }}</span>
                                @if($sEndsAfter)
                                    <span class="opacity-75 text-[10px]">&raquo;</span>
                                @endif
                            </span>
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
                        <div class="absolute inset-0 flex pointer-events-none">
                            @foreach($days as $day)
                                @php $isWk = in_array($day->dayOfWeekIso, [6, 7], true); @endphp
                                <div class="flex-shrink-0 h-full border-r border-gray-100 dark:border-gray-800/60 {{ $isWk ? 'bg-blue-100/20 dark:bg-blue-900/20' : '' }}"
                                     style="width: {{ $dayWidth }}px;"></div>
                            @endforeach
                        </div>
                    </div>

                    @foreach($mTasks as $t)
                    @php
                        $barStart = ($t->start_date ?? $t->due_date ?? $t->sprint?->start_date ?? $ganttStart)->copy()->startOfDay();
                        $barEnd   = ($t->due_date   ?? $t->start_date ?? $t->sprint?->end_date   ?? $ganttEnd)->copy()->startOfDay();
                        if ($barEnd->lt($barStart)) {
                            $barEnd = $barStart->copy();
                        }

                        $diffStart = (int) $ganttStart->diffInDays($barStart, false);
                        $diffEnd   = (int) $ganttStart->diffInDays($barEnd, false);

                        $visStart = max(0, $diffStart);
                        $visEnd   = min($totalDays - 1, $diffEnd);
                        $spanDays = max(1, ($visEnd - $visStart) + 1);

                        $leftPx = $visStart * $dayWidth;
                        $widthPx = $spanDays * $dayWidth;

                        $startsBefore = ($diffStart < 0);
                        $endsAfter    = ($diffEnd >= $totalDays);

                        $barLeftStyle = $startsBefore ? '0px' : ($leftPx + 3) . 'px';
                        $barWidthStyle = max(24, $widthPx - ($startsBefore ? 3 : 6) - ($endsAfter ? 0 : 3)) . 'px';

                        $roundedClass = 'rounded-lg';
                        if ($startsBefore && $endsAfter) {
                            $roundedClass = 'rounded-none';
                        } elseif ($startsBefore) {
                            $roundedClass = 'rounded-r-lg rounded-l-none';
                        } elseif ($endsAfter) {
                            $roundedClass = 'rounded-l-lg rounded-r-none';
                        }

                        $barColor = $pColors[$t->status]['bar'] ?? '#6366f1';
                        $barOpacity = $t->status === 'done' ? '0.75' : '1';

                        // Time log marks inside bar
                        $logSegs = $t->timeLogs->filter(fn($l) => $l->started_at && $l->ended_at)->map(function($l) use ($ganttStart, $dayWidth, $leftPx, $widthPx) {
                            $lStart = $l->started_at->copy()->startOfDay();
                            $lEnd = $l->ended_at->copy()->startOfDay();
                            $lDiff = (int) $ganttStart->diffInDays($lStart, false);
                            $lLeft = $lDiff * $dayWidth;
                            $lSpan = max(1, (int) $lStart->diffInDays($lEnd) + 1);
                            $lWidth = $lSpan * $dayWidth;
                            return [
                                'left' => max(0, $lLeft - $leftPx),
                                'width' => min($widthPx, $lWidth),
                            ];
                        });
                    @endphp
                    <div class="h-12 relative border-b border-gray-100 dark:border-gray-800 hover:bg-gray-50/40 dark:hover:bg-gray-800/30 transition">
                        {{-- Grid lines --}}
                        <div class="absolute inset-0 flex pointer-events-none">
                            @foreach($days as $day)
                                @php $isWk = in_array($day->dayOfWeekIso, [6, 7], true); @endphp
                                <div class="flex-shrink-0 h-full border-r border-gray-100 dark:border-gray-800/60 {{ $isWk ? 'bg-gray-50/50 dark:bg-gray-900/30' : '' }}"
                                     style="width: {{ $dayWidth }}px;"></div>
                            @endforeach
                        </div>

                        {{-- Task bar --}}
                        <div class="absolute top-1/2 -translate-y-1/2 h-6 flex items-center overflow-hidden shadow-xs cursor-pointer hover:shadow-md hover:brightness-105 transition {{ $roundedClass }}"
                             style="left: {{ $barLeftStyle }}; width: {{ $barWidthStyle }}; background-color: {{ $barColor }}; opacity: {{ $barOpacity }};"
                             @click="if(window.openTask) window.openTask({{ $t->id }})"
                             title="{{ $t->title }} [{{ $t->sprint?->name ?? 'Backlog' }}]: {{ $barStart->format('d M') }} → {{ $barEnd->format('d M Y') }}">
                            {{-- Time log marks --}}
                            @foreach($logSegs as $seg)
                            <div class="absolute h-full bg-white/30 rounded pointer-events-none"
                                 style="left: {{ $seg['left'] }}px; width: {{ $seg['width'] }}px;"></div>
                            @endforeach
                            <span class="text-white text-xs px-2 truncate font-semibold drop-shadow-xs relative z-10 flex items-center gap-1"
                                  style="font-size:11px">
                                @if($startsBefore)
                                    <span class="opacity-75 text-[10px]">&laquo;</span>
                                @endif
                                <span class="truncate">{{ $t->title }}</span>
                                @if($endsAfter)
                                    <span class="opacity-75 text-[10px]">&raquo;</span>
                                @endif
                            </span>
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
            <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300">
                <span class="w-3.5 h-3.5 rounded-md inline-block bg-gray-200/80 dark:bg-gray-700 border border-gray-300 dark:border-gray-600"></span>
                Weekend
            </div>
            <div class="flex items-center gap-2 text-xs font-medium text-gray-600 dark:text-gray-300 ml-auto">
                <span class="w-4 h-0.5 bg-blue-500 inline-block rounded-full"></span>
                Hari ini
            </div>
        </div>
        @else
        {{-- Empty State: Ketika tidak ada task/sprint di bulan/periode yang dipilih --}}
        <div class="px-6 py-16 text-center">
            <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-500 flex items-center justify-center mx-auto mb-3">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            </div>
            <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Tidak ada task atau sprint pada periode ini</h3>
            <p class="text-xs text-gray-400 max-w-md mx-auto mb-4">
                Tidak ada aktivitas dengan tanggal pada {{ $ganttStart->format('d M Y') }} — {{ $ganttEnd->format('d M Y') }}. Gunakan tombol navigasi di atas untuk melihat bulan lain.
            </p>
            <div class="flex items-center justify-center gap-2">
                <a href="{{ request()->fullUrlWithQuery(['start_date' => now()->startOfMonth()->format('Y-m-d'), 'end_date' => now()->endOfMonth()->format('Y-m-d')]) }}"
                   class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition shadow-2xs">
                    Kembali ke Bulan Berjalan ({{ now()->format('M Y') }})
                </a>
            </div>
        </div>
        @endif
    </div>

    {{-- ============================================================
         HIDDEN PER USER REQUEST:
         - Ringkasan per Developer (Summary Table)
         - Detail Log Waktu (Detail Logs Table)
         Focus solely on the Gantt Chart.
    ============================================================ --}}
    @if(false)
    <div class="bg-white rounded-2xl mb-6">
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
    <div class="bg-white rounded-2xl">
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
