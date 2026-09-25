@php
    $tasksCollection = $project->tasks;
    $todoTasks = $tasksCollection->where('status', 'todo')->count();
    $inProgressTasks = $tasksCollection->where('status', 'in_progress')->count();
    $reviewTasks = $tasksCollection->where('status', 'review')->count();
    $doneTasksCount = $tasksCollection->where('status', 'done')->count();

    $todoPct = $totalTasks > 0 ? round(($todoTasks / $totalTasks) * 100) : 0;
    $inProgressPct = $totalTasks > 0 ? round(($inProgressTasks / $totalTasks) * 100) : 0;
    $reviewPct = $totalTasks > 0 ? round(($reviewTasks / $totalTasks) * 100) : 0;
    $donePct = $totalTasks > 0 ? round(($doneTasksCount / $totalTasks) * 100) : 0;

    $activeSprint = $project->sprints->firstWhere('status', 'active');
    $upcomingMilestones = $project->milestones->sortBy('due_date')->take(3);
    $activeTickets = $project->tickets->whereNotIn('status', ['resolved', 'closed'])->take(4);
    $totalStoryPoints = $tasksCollection->sum('story_points') ?: ($totalTasks * 3);
    $doneStoryPoints = $tasksCollection->where('status', 'done')->sum('story_points') ?: round($totalStoryPoints * ($progress / 100));
@endphp

<div class="space-y-6">
    {{-- ============================================================
    1. DIRECT ACTION SHORTCUTS (MENU CEPAT KE MODUL LAIN)
    ============================================================ --}}
    <div>
        <div class="flex items-center justify-between mb-3">
            <h2 class="text-xs font-black text-gray-500 dark:text-gray-400 uppercase tracking-wider flex items-center gap-1.5">
                <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
                Direct Module Shortcuts
            </h2>
            <span class="text-[11px] text-gray-400">Klik kartu untuk langsung menuju ke halaman modul</span>
        </div>

        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            {{-- Shortcut 1: Sprints & Tasks --}}
            <button type="button" @click="tab = 'sprints'"
                class="group text-left p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700/80 shadow-2xs hover:border-blue-500 dark:hover:border-blue-500 hover:shadow-md transition-all -translate-y-0.5 hover:-translate-y-1 cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between w-full mb-2">
                    <div class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400">
                        {{ $project->sprints->count() }} Sp
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">
                        Sprints
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        {{ $activeSprint ? $activeSprint->name : 'Sprint Board' }}
                    </p>
                </div>
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] font-semibold text-blue-600 dark:text-blue-400">
                    <span>Buka Sprint</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </button>

            {{-- Shortcut 2: Milestones --}}
            <button type="button" @click="tab = 'milestones'"
                class="group text-left p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700/80 shadow-2xs hover:border-emerald-500 dark:hover:border-emerald-500 hover:shadow-md transition-all -translate-y-0.5 hover:-translate-y-1 cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between w-full mb-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-950/60 text-emerald-600 dark:text-emerald-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-600 dark:text-emerald-400">
                        {{ $project->milestones->count() }} Ms
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition-colors">
                        Milestones
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        Roadmap & Rilis
                    </p>
                </div>
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">
                    <span>Lihat Roadmap</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </button>

            {{-- Shortcut 3: Team Members --}}
            <button type="button" @click="tab = 'team'"
                class="group text-left p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700/80 shadow-2xs hover:border-purple-500 dark:hover:border-purple-500 hover:shadow-md transition-all -translate-y-0.5 hover:-translate-y-1 cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between w-full mb-2">
                    <div class="w-8 h-8 rounded-xl bg-purple-50 dark:bg-purple-950/60 text-purple-600 dark:text-purple-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-purple-50 dark:bg-purple-950/50 text-purple-600 dark:text-purple-400">
                        {{ $project->members->count() }} User
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors">
                        Tim Proyek
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        Kelola Anggota
                    </p>
                </div>
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] font-semibold text-purple-600 dark:text-purple-400">
                    <span>Lihat Tim</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </button>

            {{-- Shortcut 4: Timesheet & Gantt --}}
            <button type="button" @click="tab = 'timesheet'"
                class="group text-left p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700/80 shadow-2xs hover:border-amber-500 dark:hover:border-amber-500 hover:shadow-md transition-all -translate-y-0.5 hover:-translate-y-1 cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between w-full mb-2">
                    <div class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-950/60 text-amber-600 dark:text-amber-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-amber-50 dark:bg-amber-950/50 text-amber-600 dark:text-amber-400">
                        Gantt
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-amber-600 dark:group-hover:text-amber-400 transition-colors">
                        Timesheet
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        Jadwal & Log Waktu
                    </p>
                </div>
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] font-semibold text-amber-600 dark:text-amber-400">
                    <span>Buka Timeline</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </button>

            {{-- Shortcut 5: Tickets --}}
            <button type="button" @click="tab = 'tickets'"
                class="group text-left p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700/80 shadow-2xs hover:border-rose-500 dark:hover:border-rose-500 hover:shadow-md transition-all -translate-y-0.5 hover:-translate-y-1 cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between w-full mb-2">
                    <div class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-rose-50 dark:bg-rose-950/50 text-rose-600 dark:text-rose-400">
                        {{ $project->tickets->count() }} Tiket
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-rose-600 dark:group-hover:text-rose-400 transition-colors">
                        Tiket / Isu
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        Laporan Bug & SLA
                    </p>
                </div>
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] font-semibold text-rose-600 dark:text-rose-400">
                    <span>Lihat Tiket</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </button>

            {{-- Shortcut 6: Files & KB --}}
            <button type="button" @click="tab = 'files'"
                class="group text-left p-3.5 rounded-2xl bg-white dark:bg-gray-850 border border-gray-200/90 dark:border-gray-700/80 shadow-2xs hover:border-indigo-500 dark:hover:border-indigo-500 hover:shadow-md transition-all -translate-y-0.5 hover:-translate-y-1 cursor-pointer flex flex-col justify-between">
                <div class="flex items-center justify-between w-full mb-2">
                    <div class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" />
                        </svg>
                    </div>
                    <span class="text-[10px] font-bold px-1.5 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                        {{ $recentFilesTotal }} File
                    </span>
                </div>
                <div>
                    <h3 class="text-xs font-bold text-gray-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors">
                        Dokumen
                    </h3>
                    <p class="text-[11px] text-gray-400 dark:text-gray-500 truncate mt-0.5">
                        Berkas & Spesifikasi
                    </p>
                </div>
                <div class="mt-2.5 pt-2 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-[10px] font-semibold text-indigo-600 dark:text-indigo-400">
                    <span>Buka File</span>
                    <span class="group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </button>
        </div>
    </div>

    {{-- ============================================================
    2. MAIN DASHBOARD GRID (ANALYTICS & SPOTLIGHT)
    ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
        
        {{-- LEFT COLUMN: Execution Analytics & Sprints (7 Cols) --}}
        <div class="lg:col-span-7 space-y-5">

            {{-- ANALYTICS CARD: Task Distribution & Progress --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-blue-600"></span>
                            Task Status Distribution
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Sebaran status seluruh task dalam project</p>
                    </div>
                    <button type="button" @click="tab = 'tasks'"
                        class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                        <span>Buka Kanban</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                {{-- Multi-Segment Stacked Progress Bar --}}
                <div class="space-y-2">
                    <div class="h-3 w-full bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden flex shadow-inner">
                        @if($totalTasks > 0)
                            <div style="width: {{ $donePct }}%" title="Done: {{ $doneTasksCount }} ({{ $donePct }}%)"
                                class="h-full bg-emerald-500 hover:brightness-110 transition-all"></div>
                            <div style="width: {{ $reviewPct }}%" title="Review: {{ $reviewTasks }} ({{ $reviewPct }}%)"
                                class="h-full bg-purple-500 hover:brightness-110 transition-all"></div>
                            <div style="width: {{ $inProgressPct }}%" title="In Progress: {{ $inProgressTasks }} ({{ $inProgressPct }}%)"
                                class="h-full bg-blue-500 hover:brightness-110 transition-all"></div>
                            <div style="width: {{ $todoPct }}%" title="To Do: {{ $todoTasks }} ({{ $todoPct }}%)"
                                class="h-full bg-gray-300 dark:bg-gray-600 hover:brightness-110 transition-all"></div>
                        @else
                            <div class="h-full w-full bg-gray-200 dark:bg-gray-700"></div>
                        @endif
                    </div>

                    {{-- Legend & Counts --}}
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-1">
                        {{-- To Do --}}
                        <div class="p-2.5 rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-100 dark:border-gray-800 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-gray-400"></span>
                                <span class="text-xs font-semibold text-gray-600 dark:text-gray-300">To Do</span>
                            </div>
                            <span class="text-xs font-black text-gray-900 dark:text-white">{{ $todoTasks }} <span class="text-[10px] text-gray-400 font-normal">({{ $todoPct }}%)</span></span>
                        </div>

                        {{-- In Progress --}}
                        <div class="p-2.5 rounded-xl bg-blue-50/50 dark:bg-blue-950/20 border border-blue-100 dark:border-blue-900/40 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span class="text-xs font-semibold text-blue-700 dark:text-blue-300">In Progress</span>
                            </div>
                            <span class="text-xs font-black text-blue-900 dark:text-blue-300">{{ $inProgressTasks }} <span class="text-[10px] text-blue-400 font-normal">({{ $inProgressPct }}%)</span></span>
                        </div>

                        {{-- Review --}}
                        <div class="p-2.5 rounded-xl bg-purple-50/50 dark:bg-purple-950/20 border border-purple-100 dark:border-purple-900/40 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-purple-500"></span>
                                <span class="text-xs font-semibold text-purple-700 dark:text-purple-300">Review</span>
                            </div>
                            <span class="text-xs font-black text-purple-900 dark:text-purple-300">{{ $reviewTasks }} <span class="text-[10px] text-purple-400 font-normal">({{ $reviewPct }}%)</span></span>
                        </div>

                        {{-- Done --}}
                        <div class="p-2.5 rounded-xl bg-emerald-50/50 dark:bg-emerald-950/20 border border-emerald-100 dark:border-emerald-900/40 flex items-center justify-between">
                            <div class="flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">Done</span>
                            </div>
                            <span class="text-xs font-black text-emerald-900 dark:text-emerald-300">{{ $doneTasksCount }} <span class="text-[10px] text-emerald-400 font-normal">({{ $donePct }}%)</span></span>
                        </div>
                    </div>
                </div>

                {{-- Story Points & Delivery Rate Info --}}
                <div class="pt-3 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between text-xs">
                    <span class="text-gray-500 dark:text-gray-400">
                        Story Points: <strong class="text-gray-900 dark:text-white font-bold">{{ $doneStoryPoints }}</strong> / {{ $totalStoryPoints }} pts terealisasi
                    </span>
                    <span class="font-extrabold text-emerald-600 dark:text-emerald-400">
                        {{ $progress }}% Complete
                    </span>
                </div>
            </div>

            {{-- SPOTLIGHT: Active Sprint Card --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></span>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white">Active Sprint Spotlight</h3>
                    </div>
                    <button type="button" @click="tab = 'sprints'"
                        class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                        <span>Semua Sprint</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                @if($activeSprint)
                    @php
                        $asTasks = $activeSprint->tasks;
                        $asTotal = $asTasks->count();
                        $asDone = $asTasks->where('status', 'done')->count();
                        $asPct = $asTotal > 0 ? round(($asDone / $asTotal) * 100) : 0;
                    @endphp
                    <div class="p-4 rounded-xl bg-gradient-to-br from-blue-50/50 via-white to-purple-50/30 dark:from-gray-800/60 dark:via-gray-850 dark:to-gray-800/40 border border-blue-100/80 dark:border-gray-700/80 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider bg-blue-600 text-white shadow-2xs">
                                        Active Now
                                    </span>
                                    <h4 class="text-base font-black text-gray-900 dark:text-white">{{ $activeSprint->name }}</h4>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    {{ $activeSprint->goal ?: 'Fokus penyelesaian fitur utama pada sprint berjalan.' }}
                                </p>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-xs font-semibold text-gray-400">Periode:</span>
                                <p class="text-xs font-bold text-gray-800 dark:text-gray-200">
                                    {{ $activeSprint->start_date ? \Carbon\Carbon::parse($activeSprint->start_date)->format('d M') : 'Start' }} – 
                                    {{ $activeSprint->end_date ? \Carbon\Carbon::parse($activeSprint->end_date)->format('d M Y') : 'End' }}
                                </p>
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-500 font-medium">Sprint Completion:</span>
                                <span class="font-extrabold text-blue-600 dark:text-blue-400">{{ $asDone }} / {{ $asTotal }} Tasks ({{ $asPct }}%)</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-gradient-to-r from-blue-600 to-indigo-600" style="width: {{ $asPct }}%"></div>
                            </div>
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <div class="flex items-center gap-2">
                                @if($activeSprint->lead)
                                    <div class="w-6 h-6 rounded-full text-white flex items-center justify-center text-[10px] font-bold shadow-2xs"
                                        style="background-color: {{ $activeSprint->lead->avatarColor() }};">
                                        {{ $activeSprint->lead->initials() }}
                                    </div>
                                    <span class="text-xs text-gray-600 dark:text-gray-300">Lead: <strong>{{ $activeSprint->lead->name }}</strong></span>
                                @else
                                    <span class="text-xs text-gray-400">Belum ada sprint lead</span>
                                @endif
                            </div>

                            <button type="button" @click="tab = 'sprints'"
                                class="px-3 py-1.5 rounded-lg bg-blue-600 text-white text-xs font-bold hover:bg-blue-700 transition shadow-2xs inline-flex items-center gap-1.5 cursor-pointer">
                                <span>Kelola Sprint Ini</span>
                                <span>→</span>
                            </button>
                        </div>
                    </div>
                @else
                    <div class="p-4 rounded-xl bg-gray-50 dark:bg-gray-800/40 border border-dashed border-gray-200 dark:border-gray-700 text-center space-y-2">
                        <p class="text-xs text-gray-500 dark:text-gray-400">Belum ada sprint yang sedang aktif saat ini.</p>
                        <button type="button" @click="tab = 'sprints'"
                            class="px-3.5 py-1.5 bg-blue-600 text-white text-xs font-bold rounded-xl hover:bg-blue-700 transition shadow-2xs">
                            + Rencanakan Sprint Baru
                        </button>
                    </div>
                @endif
            </div>

            {{-- MILESTONE ROADMAP PIPELINE --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                            </svg>
                            Milestones Delivery Pipeline
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Target rilis dan capaian milestone project</p>
                    </div>
                    <button type="button" @click="tab = 'milestones'"
                        class="text-xs font-bold text-emerald-600 dark:text-emerald-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                        <span>Roadmap Lengkap</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    @forelse($upcomingMilestones as $ms)
                        @php
                            $msTasks = $ms->tasks;
                            $msTotal = $msTasks->count();
                            $msDone = $msTasks->where('status', 'done')->count();
                            $msPct = $msTotal > 0 ? round(($msDone / $msTotal) * 100) : ($ms->progress ?? 0);
                        @endphp
                        <div class="p-3.5 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 hover:bg-gray-50 dark:hover:bg-gray-800/60 transition space-y-2">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $msPct == 100 ? 'bg-emerald-500' : ($msPct > 0 ? 'bg-blue-500' : 'bg-gray-400') }}"></span>
                                    <h4 class="text-xs font-bold text-gray-900 dark:text-white">{{ $ms->title }}</h4>
                                    @if($ms->release_target)
                                        <span class="text-[9px] px-1.5 py-0.5 rounded bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400 font-bold border border-purple-200 dark:border-purple-900/50">
                                            {{ $ms->release_target }}
                                        </span>
                                    @endif
                                </div>
                                <span class="text-[11px] font-extrabold text-blue-600 dark:text-blue-400">{{ $msPct }}%</span>
                            </div>

                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                <div class="h-1.5 rounded-full {{ $msPct == 100 ? 'bg-emerald-500' : 'bg-blue-600' }}" style="width: {{ $msPct }}%"></div>
                            </div>

                            <div class="flex items-center justify-between text-[11px] text-gray-500 dark:text-gray-400 pt-0.5">
                                <span>{{ $msTotal }} tasks terhubung</span>
                                <span>Target: <strong>{{ $ms->due_date ? \Carbon\Carbon::parse($ms->due_date)->format('d M Y') : '—' }}</strong></span>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-3">Belum ada milestone dibuat.</p>
                    @endforelse
                </div>
            </div>

        </div>

        {{-- RIGHT COLUMN: Project Info, Team & Tickets (5 Cols) --}}
        <div class="lg:col-span-5 space-y-5">
            
            {{-- PROJECT DETAILS CARD --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs space-y-4">
                <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                    <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Project Information
                </h3>

                @if($project->description)
                    <p class="text-xs text-gray-600 dark:text-gray-300 leading-relaxed bg-gray-50 dark:bg-gray-800/40 p-3 rounded-xl border border-gray-100 dark:border-gray-800">
                        {{ $project->description }}
                    </p>
                @endif

                <div class="space-y-2.5 text-xs">
                    {{-- Client --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 font-medium">Klien / Customer:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">{{ $project->client?->name ?? 'Internal Project' }}</span>
                    </div>

                    {{-- Project Lead --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 font-medium">Project Lead:</span>
                        <div class="flex items-center gap-1.5">
                            @if($project->manager)
                                <div class="w-5 h-5 rounded-full text-white flex items-center justify-center text-[9px] font-bold shadow-2xs"
                                    style="background-color: {{ $project->manager->avatarColor() }};">
                                    {{ $project->manager->initials() }}
                                </div>
                                <span class="font-bold text-gray-800 dark:text-gray-200">{{ $project->manager->name }}</span>
                            @else
                                <span class="font-bold text-gray-400">—</span>
                            @endif
                        </div>
                    </div>

                    {{-- Budget --}}
                    <div class="flex items-center justify-between py-1.5 border-b border-gray-100 dark:border-gray-800">
                        <span class="text-gray-400 font-medium">Budget:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ $project->budget ? 'Rp ' . number_format($project->budget, 0, ',', '.') : 'Not Set' }}
                        </span>
                    </div>

                    {{-- Total Sprints & Milestones --}}
                    <div class="flex items-center justify-between py-1.5">
                        <span class="text-gray-400 font-medium">Siklus Kerja:</span>
                        <span class="font-bold text-gray-800 dark:text-gray-200">
                            {{ $project->sprints->count() }} Sprints · {{ $project->milestones->count() }} Milestones
                        </span>
                    </div>
                </div>
            </div>

            {{-- TEAM ROSTER SPOTLIGHT --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                            Team Members ({{ $project->members->count() }})
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Anggota yang berkontribusi dalam project ini</p>
                    </div>
                    <button type="button" @click="tab = 'team'"
                        class="text-xs font-bold text-purple-600 dark:text-purple-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                        <span>Kelola Tim</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-2.5">
                    @forelse($project->members->take(5) as $member)
                        @php
                            $u = $member->user;
                            $assignedCount = $memberTaskCounts[$u->id ?? 0] ?? 0;
                        @endphp
                        @if($u)
                            <div class="flex items-center justify-between p-2 rounded-xl hover:bg-gray-50 dark:hover:bg-gray-800/50 transition">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-full text-white flex items-center justify-center text-xs font-bold shadow-2xs shrink-0"
                                        style="background-color: {{ $u->avatarColor() }};">
                                        {{ $u->initials() }}
                                    </div>
                                    <div>
                                        <p class="text-xs font-bold text-gray-900 dark:text-white leading-tight">{{ $u->name }}</p>
                                        <span class="text-[10px] text-gray-400 capitalize">{{ $member->role ?? 'Member' }}</span>
                                    </div>
                                </div>
                                <span class="text-[11px] font-bold px-2 py-0.5 rounded-full bg-blue-50 dark:bg-blue-950/40 text-blue-600 dark:text-blue-400">
                                    {{ $assignedCount }} Tasks
                                </span>
                            </div>
                        @endif
                    @empty
                        <p class="text-xs text-gray-400 text-center py-2">Belum ada anggota tim ditambahkan.</p>
                    @endforelse
                </div>

                @if($project->members->count() > 5)
                    <div class="pt-2 text-center border-t border-gray-100 dark:border-gray-800">
                        <button type="button" @click="tab = 'team'" class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                            + Lihat {{ $project->members->count() - 5 }} anggota lainnya
                        </button>
                    </div>
                @endif
            </div>

            {{-- RECENT TICKETS & ISSUES ALERT --}}
            <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-black text-gray-900 dark:text-white flex items-center gap-2">
                            <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            Recent Tickets & Issues
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Tiket terbuka yang membutuhkan perhatian</p>
                    </div>
                    <button type="button" @click="tab = 'tickets'"
                        class="text-xs font-bold text-rose-600 dark:text-rose-400 hover:underline inline-flex items-center gap-1 cursor-pointer">
                        <span>Semua Tiket</span>
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </div>

                <div class="space-y-2.5">
                    @forelse($recentTickets as $ticket)
                        <div class="p-2.5 rounded-xl border border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-800/30 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $ticket->subject }}</p>
                                <div class="flex items-center gap-2 mt-0.5 text-[10px] text-gray-400">
                                    <span>#{{ $ticket->ticket_number ?? $ticket->id }}</span>
                                    <span>·</span>
                                    <span>{{ $ticket->reporter?->name ?? 'User' }}</span>
                                </div>
                            </div>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full shrink-0 {{ $ticket->priority === 'urgent' ? 'bg-rose-50 text-rose-600 border border-rose-200' : 'bg-amber-50 text-amber-700 border border-amber-200' }}">
                                {{ ucfirst($ticket->priority ?? 'normal') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 text-center py-2">Tidak ada tiket aktif yang dilaporkan.</p>
                    @endforelse
                </div>
            </div>

        </div>

    </div>
</div>
