{{-- ============================================================
SPRINTS DASHBOARD & MANAGEMENT (Card & Roadmap, Sprint List Table)
============================================================ --}}
@php
    $allSprints = $project->sprints()->with(['lead', 'milestone', 'tasks.assignee'])->orderByDesc('start_date')->get();
    $totalSprints = $allSprints->count();
    $activeSprints = $allSprints->whereIn('status', ['active', 'in_progress'])->count();
    $completedSprints = $allSprints->where('status', 'completed')->count();
    $totalSprintTasks = $allSprints->sum(fn($s) => $s->tasks->count());
    $atRiskSprints = $allSprints->filter(fn($s) => $s->status === 'at_risk' || ($s->end_date && $s->end_date->isPast() && $s->status !== 'completed'))->count();

    // Timeline scale calculation for Sprint Roadmap
    $sprintMinDate = now()->startOfMonth();
    $sprintMaxDate = now()->addMonths(2)->endOfMonth();

    foreach ($allSprints as $s) {
        if ($s->start_date && $s->start_date->lt($sprintMinDate))
            $sprintMinDate = $s->start_date->copy()->startOfMonth();
        if ($s->end_date && $s->end_date->gt($sprintMaxDate))
            $sprintMaxDate = $s->end_date->copy()->endOfMonth();
    }
    $totalSprintRoadmapDays = max(1, $sprintMinDate->diffInDays($sprintMaxDate));
@endphp

<div id="sprints-dashboard-root" x-data="sprintsDashboardData()" x-init="initSprintsDashboard()" class="space-y-6">

    {{-- ============================================================
    1. TOP KPI STAT CARDS (UNIFIED 4-COLUMN CARD MATCHING GAMBAR 3)
    ============================================================ --}}
    <div
        class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 shadow-2xs grid grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800">
        {{-- Col 1: TOTAL SPRINTS --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">TOTAL
                    SPRINTS</span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $totalSprints }}</p>
            <div class="flex items-center gap-1.5 text-xs font-semibold">
                <span class="text-emerald-600 dark:text-emerald-400">↑ {{ $totalSprints }} in scope</span>
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <span class="text-gray-400 dark:text-gray-500 font-normal">{{ $totalSprintTasks }} tasks</span>
            </div>
        </div>

        {{-- Col 2: ACTIVE SPRINT --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span
                    class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">ACTIVE
                    NOW</span>
                <div
                    class="w-4 h-4 rounded-full border-2 border-amber-500 flex items-center justify-start overflow-hidden">
                    <div class="w-2 h-4 bg-amber-500"></div>
                </div>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $activeSprints }}</p>
            <div class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                </svg>
                <span>{{ $totalSprints > 0 ? round(($activeSprints / $totalSprints) * 100) : 0 }}% active
                    tracking</span>
            </div>
        </div>

        {{-- Col 3: COMPLETED --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span
                    class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">COMPLETED</span>
                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                        clip-rule="evenodd" />
                </svg>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $completedSprints }}</p>
            <div class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                </svg>
                <span>{{ $totalSprints > 0 ? round(($completedSprints / $totalSprints) * 100) : 0 }}% delivered</span>
            </div>
        </div>

        {{-- Col 4: AT RISK / ISSUES --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">AT
                    RISK / ISSUES</span>
                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <p class="text-3xl font-black text-rose-600 dark:text-rose-400 leading-none my-2.5">{{ $atRiskSprints }}</p>
            <div
                class="flex items-center gap-1 text-xs font-semibold {{ $atRiskSprints > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                @if($atRiskSprints > 0)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2" />
                        <path stroke-width="2" d="M12 8v4m0 4h.01" />
                    </svg>
                    <span>Needs Review (Blocked)</span>
                @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>On Track / Healthy</span>
                @endif
            </div>
        </div>
    </div>

    {{-- ============================================================
    2. CONTROL & FILTER BAR
    ============================================================ --}}
    <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">

        {{-- View Switcher (2 Tabs) --}}
        <div
            class="inline-flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200/60 dark:border-gray-750 self-start">
            <button type="button" @click="activeSprintView = 'card_roadmap'"
                :class="activeSprintView === 'card_roadmap' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Card & Roadmap</span>
            </button>

            <button type="button" @click="activeSprintView = 'table_view'"
                :class="activeSprintView === 'table_view' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>Sprint Table</span>
            </button>
        </div>

        {{-- Filters & Actions --}}
        <div class="flex items-center gap-2.5 flex-wrap justify-end">
            {{-- Search Bar --}}
            <div class="relative min-w-[190px]">
                <input type="text" x-model="searchSprintQuery" placeholder="Search sprints..."
                    class="w-full pl-8 pr-3.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Status Filter Popover --}}
            <div class="relative" x-data="{ openFilterSprintStatus: false }">
                <button type="button" @click="openFilterSprintStatus = !openFilterSprintStatus"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0" :class="{
                                'bg-slate-300 dark:bg-slate-600': !filterSprintStatus,
                                'bg-slate-400': filterSprintStatus === 'planning',
                                'bg-blue-500': filterSprintStatus === 'active',
                                'bg-emerald-500': filterSprintStatus === 'completed',
                                'bg-rose-500': filterSprintStatus === 'at_risk'
                            }"></span>
                        <span class="font-medium" x-text="{
                            '': 'All Status',
                            'planning': 'Planning',
                            'active': 'Active (In Progress)',
                            'completed': 'Completed',
                            'at_risk': 'At Risk'
                        }[filterSprintStatus] || 'All Status'"></span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openFilterSprintStatus" @click.outside="openFilterSprintStatus = false" x-cloak
                    x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 top-full mt-1.5 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                    <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        FILTER STATUS
                    </div>
                    <div class="space-y-0.5 mt-1 px-1.5">
                        <button type="button" @click="filterSprintStatus = ''; openFilterSprintStatus = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterSprintStatus === '' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                <span>All Status</span>
                            </span>
                            <svg x-show="filterSprintStatus === ''" class="w-3.5 h-3.5 text-blue-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        @foreach([
                                ['value' => 'planning', 'label' => 'Planning', 'dot' => 'bg-slate-400'],
                                ['value' => 'active', 'label' => 'Active (In Progress)', 'dot' => 'bg-blue-500'],
                                ['value' => 'completed', 'label' => 'Completed', 'dot' => 'bg-emerald-500'],
                                ['value' => 'at_risk', 'label' => 'At Risk', 'dot' => 'bg-rose-500'],
                            ] as $st)
                            <button type="button"
                                @click="filterSprintStatus = '{{ $st['value'] }}'; openFilterSprintStatus = false"
                                class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                                :class="filterSprintStatus === '{{ $st['value'] }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $st['dot'] }}"></span>
                                    <span>{{ $st['label'] }}</span>
                                </span>
                                <svg x-show="filterSprintStatus === '{{ $st['value'] }}'" class="w-3.5 h-3.5 text-blue-600"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Priority Filter Popover (Task Priority Match) --}}
            <div class="relative" x-data="{ openFilterSprintPriority: false }">
                <button type="button" @click="openFilterSprintPriority = !openFilterSprintPriority"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                    <span class="flex items-center gap-2">
                        <span x-show="!filterSprintPriority" class="w-2 h-2 rounded-full bg-slate-300 shrink-0"></span>
                        <span x-show="filterSprintPriority === 'urgent'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                            </svg>
                        </span>
                        <span x-show="filterSprintPriority === 'high'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
                            </svg>
                        </span>
                        <span x-show="filterSprintPriority === 'normal' || filterSprintPriority === 'medium'"
                            class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M4 9h16M4 15h16" />
                            </svg>
                        </span>
                        <span x-show="filterSprintPriority === 'low'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                            </svg>
                        </span>

                        <span class="font-medium" :class="{
                                'text-red-600 font-semibold': filterSprintPriority === 'urgent',
                                'text-amber-700 font-semibold': filterSprintPriority === 'high',
                            }" x-text="{
                                '': 'All Priority',
                                'urgent': 'Urgent',
                                'high': 'High',
                                'normal': 'Normal',
                                'medium': 'Normal',
                                'low': 'Low'
                            }[filterSprintPriority] || 'All Priority'"></span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openFilterSprintPriority" @click.outside="openFilterSprintPriority = false" x-cloak
                    x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 top-full mt-1.5 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                    <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        FILTER PRIORITY
                    </div>
                    <div class="space-y-0.5 mt-1 px-1.5">
                        <button type="button" @click="filterSprintPriority = ''; openFilterSprintPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterSprintPriority === '' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                <span>All Priority</span>
                            </span>
                            <svg x-show="filterSprintPriority === ''" class="w-3.5 h-3.5 text-blue-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- Urgent --}}
                        <button type="button" @click="filterSprintPriority = 'urgent'; openFilterSprintPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterSprintPriority === 'urgent' ? 'bg-red-50/80 dark:bg-red-950/40 text-red-700 dark:text-red-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                                </svg>
                                <span>Urgent</span>
                                <span
                                    class="px-1 py-0.2 bg-red-100 text-red-700 text-[8px] font-bold rounded">CRITICAL</span>
                            </span>
                            <svg x-show="filterSprintPriority === 'urgent'" class="w-3.5 h-3.5 text-red-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- High --}}
                        <button type="button" @click="filterSprintPriority = 'high'; openFilterSprintPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterSprintPriority === 'high' ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                </svg>
                                <span>High</span>
                            </span>
                            <svg x-show="filterSprintPriority === 'high'" class="w-3.5 h-3.5 text-amber-600" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- Normal --}}
                        <button type="button" @click="filterSprintPriority = 'normal'; openFilterSprintPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="(filterSprintPriority === 'normal' || filterSprintPriority === 'medium') ? 'bg-slate-100 dark:bg-slate-700 font-semibold text-slate-800 dark:text-slate-100' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M4 9h16M4 15h16" />
                                </svg>
                                <span>Normal</span>
                            </span>
                            <svg x-show="filterSprintPriority === 'normal' || filterSprintPriority === 'medium'"
                                class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- Low --}}
                        <button type="button" @click="filterSprintPriority = 'low'; openFilterSprintPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterSprintPriority === 'low' ? 'bg-emerald-50/80 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                </svg>
                                <span>Low</span>
                            </span>
                            <svg x-show="filterSprintPriority === 'low'" class="w-3.5 h-3.5 text-emerald-600"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                    d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>



            {{-- Create Sprint Button --}}
            @if(!auth()->user()->hasRole('client'))
                <button type="button" @click="openCreateSprintModal()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Sprint</span>
                </button>
            @endif
        </div>
    </div>

    {{-- ============================================================
    3. EMPTY STATE
    ============================================================ --}}
    @if($allSprints->isEmpty())
        <div
            class="bg-white dark:bg-gray-850 rounded-3xl border border-gray-200/90 dark:border-gray-700/80 p-8 sm:p-12 text-center shadow-2xs space-y-5">
            <div
                class="relative w-16 h-16 mx-auto rounded-3xl bg-indigo-50 dark:bg-indigo-950/60 border-2 border-indigo-200/70 dark:border-indigo-800/80 flex items-center justify-center text-indigo-600 dark:text-indigo-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div class="space-y-1.5 max-w-md mx-auto">
                <h3 class="text-lg font-black text-gray-900 dark:text-white">No Sprints Created Yet</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Break down project goals into 1–2 week sprints to track agile velocity and commitments.
                </p>
            </div>
            @if(!auth()->user()->hasRole('client'))
                <button type="button" @click="openCreateSprintModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create First Sprint</span>
                </button>
            @endif
        </div>
    @else

        {{-- ============================================================
        4. VIEW 1: CARD & ROADMAP VIEW
        ============================================================ --}}
        <div x-show="activeSprintView === 'card_roadmap'" x-cloak
            class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">

            {{-- Left Column: Sprint Cards (5 cols) --}}
            <div class="xl:col-span-6 space-y-4">
                @foreach($allSprints as $sprint)
                    @php
                        $spPct = $sprint->taskProgressPercent();
                        $spDaysLeft = $sprint->workingDaysRemaining();
                        $statusStyle = $sprint->statusStyle();
                        $priorityStyle = $sprint->priorityStyle();
                    @endphp
                    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs hover:shadow-md transition-all group"
                        x-show="matchesSprintFilter('{{ strtolower(addslashes($sprint->name . ' ' . $sprint->code)) }}', '{{ $sprint->status }}', '{{ $sprint->priority ?? 'normal' }}')">

                        {{-- Badges & Actions --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200/50 font-mono">
                                    {{ $sprint->code ?? ('SP-' . $sprint->id) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusStyle['pill'] }}">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full {{ $statusStyle['dot'] }} mr-1"></span>
                                    {{ $sprint->statusLabel() }}
                                </span>
                                @if($sprint->priority)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $priorityStyle['pill'] }}">
                                        {{ $sprint->priorityLabel() }}
                                    </span>
                                @endif
                                @if($sprint->milestone)
                                    <span
                                        class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 border border-blue-200/50 truncate max-w-[140px]">
                                        {{ $sprint->milestone->title }}
                                    </span>
                                @endif
                            </div>

                            {{-- Dropdown Action Menu --}}
                            <div class="relative shrink-0" x-data="{ open: false }">
                                <button type="button" @click="open = !open" @click.away="open = false"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-30 space-y-0.5 text-xs">
                                    <button type="button"
                                        @click="open = false; window.filterTasksBySprint({{ $sprint->id }}, '{{ addslashes($sprint->name) }}')"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 dark:text-blue-400 font-medium flex items-center gap-2">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        <span>View Tasks Board</span>
                                    </button>

                                    @if(!auth()->user()->hasRole('client'))
                                        <button type="button" @click="open = false; openEditSprintModal({{ json_encode($sprint) }})"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center gap-2 font-medium">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                            </svg>
                                            <span>Edit Sprint</span>
                                        </button>

                                        <button type="button"
                                            @click="open = false; openAssignSprintModal({{ json_encode($sprint) }}, {{ json_encode($sprint->tasks->pluck('id')) }})"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-indigo-600 dark:text-indigo-400 flex items-center gap-2 font-medium">
                                            <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                            <span>Assign Tasks</span>
                                        </button>

                                        <button type="button"
                                            @click="promptDeleteSprint('{{ route('sprints.destroy', [$project, $sprint]) }}', '{{ addslashes($sprint->name) }}')"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 font-medium flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Sprint Name & Goal --}}
                        <div class="mb-3">
                            <button type="button"
                                @click="window.filterTasksBySprint({{ $sprint->id }}, '{{ addslashes($sprint->name) }}')"
                                class="text-left text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 cursor-pointer transition-colors"
                                title="View tasks for this sprint">
                                {{ $sprint->name }}
                            </button>
                            @if($sprint->goal)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                                    {{ $sprint->goal }}
                                </p>
                            @endif
                        </div>

                        {{-- Lead & Timeline --}}
                        <div
                            class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 py-2 border-t border-b border-gray-100 dark:border-gray-800 mb-3">
                            <div class="flex items-center gap-2">
                                @if($sprint->lead)
                                    <div
                                        class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-700 text-[10px] font-black flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($sprint->lead->name, 0, 2)) }}
                                    </div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $sprint->lead->name }}</span>
                                @else
                                    <span class="text-gray-400 italic">No Lead</span>
                                @endif
                            </div>

                            <div class="text-right">
                                <span class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $sprint->start_date ? $sprint->start_date->format('d M') : '—' }} →
                                    {{ $sprint->end_date ? $sprint->end_date->format('d M Y') : '—' }}
                                </span>
                                @if($spDaysLeft !== null)
                                    <span
                                        class="block text-[10px] {{ $sprint->isOverdue() ? 'text-rose-500 font-bold' : 'text-gray-400' }}">
                                        {{ $sprint->isOverdue() ? abs($spDaysLeft) . ' days overdue' : $spDaysLeft . ' days left' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Progress Bar --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Sprint Progress</span>
                                    <span
                                        class="text-[10px] px-1.5 py-0.2 rounded bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-400 font-bold">
                                        {{ $sprint->tasks->count() }} tasks
                                    </span>
                                </div>
                                <span class="font-black text-indigo-600 dark:text-indigo-400">{{ $spPct }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-gradient-to-r from-indigo-600 to-purple-600 transition-all duration-500"
                                    style="width: {{ min(100, $spPct) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Right Column: Sprint Roadmap Gantt View (7 cols) --}}
            <div
                class="xl:col-span-6 bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs overflow-hidden">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-750">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Sprint Execution Roadmap</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Timeline and iterations schedule</p>
                    </div>
                    <span
                        class="text-[11px] font-semibold px-2.5 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-600 dark:text-gray-300">
                        {{ $sprintMinDate->format('M Y') }} – {{ $sprintMaxDate->format('M Y') }}
                    </span>
                </div>

                {{-- Roadmap Timeline Container --}}
                <div class="overflow-x-auto">
                    <div class="min-w-[550px] space-y-4">
                        {{-- Timeline Header Scale --}}
                        <div
                            class="flex border-b border-gray-200 dark:border-gray-700 pb-2 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            @php
                                $spCursor = $sprintMinDate->copy();
                                $spMonthSegments = [];
                                while ($spCursor->lte($sprintMaxDate)) {
                                    $spMonthSegments[] = $spCursor->copy();
                                    $spCursor->addMonth();
                                }
                            @endphp
                            @foreach($spMonthSegments as $mSeg)
                                <div class="flex-1 text-center border-r border-gray-100 dark:border-gray-800 last:border-r-0">
                                    {{ $mSeg->format('M Y') }}
                                </div>
                            @endforeach
                        </div>

                        {{-- Sprint Roadmap Bars --}}
                        <div class="space-y-3 pt-2">
                            @foreach($allSprints as $sprint)
                                @php
                                    $start = $sprint->start_date ?? $sprintMinDate;
                                    $end = $sprint->end_date ?? $start->copy()->addDays(14);
                                    if ($start->lt($sprintMinDate))
                                        $start = $sprintMinDate->copy();
                                    if ($end->gt($sprintMaxDate))
                                        $end = $sprintMaxDate->copy();

                                    $offsetDays = max(0, $sprintMinDate->diffInDays($start));
                                    $durationDays = max(1, $start->diffInDays($end));
                                    $leftPercent = round(($offsetDays / $totalSprintRoadmapDays) * 100, 2);
                                    $widthPercent = max(6, min(100 - $leftPercent, round(($durationDays / $totalSprintRoadmapDays) * 100, 2)));
                                    $spPct = $sprint->taskProgressPercent();
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span
                                                class="font-extrabold text-[10px] font-mono text-indigo-600 dark:text-indigo-400">{{ $sprint->code ?? ('SP-' . $sprint->id) }}</span>
                                            <span
                                                class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-[200px]">{{ $sprint->name }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-500">{{ $start->format('d M') }} –
                                            {{ $end->format('d M') }} ({{ $spPct }}%)</span>
                                    </div>

                                    {{-- Bar Track --}}
                                    <div
                                        class="relative w-full h-8 bg-gray-50 dark:bg-gray-800/60 rounded-xl overflow-hidden border border-gray-100 dark:border-gray-750">
                                        <div class="absolute top-1 bottom-1 rounded-lg shadow-2xs flex items-center px-2.5 text-[11px] font-bold text-white transition-all duration-300 {{ $sprint->status === 'completed' ? 'bg-emerald-600' : 'bg-gradient-to-r from-indigo-600 to-purple-600' }}"
                                            style="left: {{ $leftPercent }}%; width: {{ $widthPercent }}%">
                                            <span class="truncate">{{ $sprint->name }}</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============================================================
        5. VIEW 2: SPRINT TABLE VIEW
        ============================================================ --}}
        <div x-show="activeSprintView === 'table_view'" x-cloak
            class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 shadow-2xs overflow-hidden">

            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-750 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Sprint Iteration Directory</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">All planned, active, and completed iterations &rarr; Expand to view tasks</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="expandAllSprints()" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition cursor-pointer">
                        Expand All
                    </button>
                    <button type="button" @click="collapseAllSprints()" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition cursor-pointer">
                        Collapse All
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                    <thead
                        class="bg-gray-50/75 dark:bg-gray-800/60 font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5 text-left min-w-[240px]">Sprint Name</th>
                            <th class="px-4 py-3.5 text-left">Parent Milestone</th>
                            <th class="px-4 py-3.5 text-left">Lead</th>
                            <th class="px-4 py-3.5 text-left">Status</th>
                            <th class="px-4 py-3.5 text-left">Priority</th>
                            <th class="px-4 py-3.5 text-left">Progress</th>
                            <th class="px-4 py-3.5 text-left">Timeline</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                        @foreach($allSprints as $sprint)
                            @php
                                $spPct = $sprint->taskProgressPercent();
                                $statusStyle = $sprint->statusStyle();
                                $priorityStyle = $sprint->priorityStyle();
                            @endphp
                            {{-- LEVEL 1: SPRINT ROW --}}
                            <tr class="bg-indigo-50/20 dark:bg-indigo-950/10 hover:bg-indigo-50/40 dark:hover:bg-indigo-950/20 transition">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="toggleSprintDetails({{ $sprint->id }})" class="p-1 text-gray-500 hover:text-indigo-600 rounded transition cursor-pointer">
                                            <svg class="w-4 h-4 transform transition-transform" :class="isSprintDetailsOpen({{ $sprint->id }}) ? 'rotate-90 text-indigo-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                        <span
                                            class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-mono">
                                            {{ $sprint->code ?? ('SP-' . $sprint->id) }}
                                        </span>
                                        <button type="button"
                                            @click="window.filterTasksBySprint({{ $sprint->id }}, '{{ addslashes($sprint->name) }}')"
                                            class="font-bold text-gray-900 dark:text-white hover:text-blue-600 transition text-left cursor-pointer"
                                            title="View tasks for this sprint">
                                            {{ $sprint->name }}
                                        </button>
                                        <span class="text-[10px] text-gray-400">({{ $sprint->tasks->count() }} tasks)</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">
                                    @if($sprint->milestone)
                                        <span class="px-2 py-0.5 rounded-md text-[10px] bg-blue-50 text-blue-700 font-semibold">
                                            {{ $sprint->milestone->title }}
                                        </span>
                                    @else
                                        <span class="text-gray-400 italic">None</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3.5 text-gray-800 dark:text-gray-200">
                                    <div class="flex items-center gap-1.5">
                                        @if($sprint->lead)
                                            @if($sprint->lead->avatar)
                                                <img src="{{ Storage::url($sprint->lead->avatar) }}" alt="{{ $sprint->lead->name }}" class="w-5 h-5 rounded-full object-cover">
                                            @else
                                                <div class="w-5 h-5 rounded-full ring-1 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[9px] font-bold shadow-2xs"
                                                     style="background-color: {{ $sprint->lead->avatarColor() }};"
                                                     title="{{ $sprint->lead->name }}">
                                                    {{ $sprint->lead->initials() }}
                                                </div>
                                            @endif
                                            <span class="text-xs font-semibold">{{ $sprint->lead->name }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusStyle['pill'] }}">
                                        {{ $sprint->statusLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span
                                        class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $priorityStyle['pill'] }}">
                                        {{ $sprint->priorityLabel() }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2 min-w-[90px]">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full bg-indigo-600" style="width: {{ $spPct }}%"></div>
                                        </div>
                                        <span class="font-bold text-indigo-600">{{ $spPct }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">
                                    {{ $sprint->start_date ? $sprint->start_date->format('d M') : '—' }} →
                                    {{ $sprint->end_date ? $sprint->end_date->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-right space-x-1">
                                    @if(!auth()->user()->hasRole('client'))
                                        <button type="button"
                                            @click="openAssignSprintModal({{ json_encode($sprint) }}, {{ json_encode($sprint->tasks->pluck('id')) }})"
                                            class="p-1.5 text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-950/40 rounded-lg inline-flex items-center justify-center transition"
                                            title="Assign Tasks">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="openEditSprintModal({{ json_encode($sprint) }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/40 rounded-lg inline-flex items-center justify-center transition"
                                            title="Edit Sprint">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                            </svg>
                                        </button>
                                        <button type="button"
                                            @click="promptDeleteSprint('{{ route('sprints.destroy', [$project, $sprint]) }}', '{{ addslashes($sprint->name) }}')"
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/40 rounded-lg inline-flex items-center justify-center transition"
                                            title="Delete Sprint">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- LEVEL 2: TASKS UNDER SPRINT --}}
                            @foreach($sprint->tasks as $t)
                                <tr x-show="isSprintDetailsOpen({{ $sprint->id }})"
                                    class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition cursor-pointer"
                                    @click="openTaskModal({{ $t->id }})">
                                    <td class="px-6 py-2 pl-14 text-gray-600 dark:text-gray-400">
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-400">&lfloor;</span>
                                            @if($t->code)
                                                <span class="font-mono text-[10px] text-gray-400">{{ $t->code }}</span>
                                            @endif
                                            <span class="hover:text-blue-600 transition font-medium text-gray-800 dark:text-gray-200">{{ $t->title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-gray-400">
                                        <span class="text-[10px] text-gray-500">{{ $sprint->milestone?->title ?? '—' }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-600 dark:text-gray-300">
                                        <div class="flex items-center gap-1.5">
                                            @if($t->assignee)
                                                @if($t->assignee->avatar)
                                                    <img src="{{ Storage::url($t->assignee->avatar) }}" alt="{{ $t->assignee->name }}" class="w-5 h-5 rounded-full object-cover">
                                                @else
                                                    <div class="w-5 h-5 rounded-full ring-1 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[9px] font-bold shadow-2xs"
                                                         style="background-color: {{ $t->assignee->avatarColor() }};"
                                                         title="{{ $t->assignee->name }}">
                                                        {{ $t->assignee->initials() }}
                                                    </div>
                                                @endif
                                                <span class="text-xs">{{ $t->assignee->name }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $t->status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $t->boardColumn?->name ?? ucfirst($t->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2">
                                        <span class="capitalize text-[10px] text-gray-500">{{ $t->priority ?? 'normal' }}</span>
                                    </td>
                                    <td class="px-4 py-2 text-gray-400">{{ $t->status === 'done' ? '100%' : '0%' }}</td>
                                    <td class="px-4 py-2 text-gray-400">{{ $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M') : '—' }}</td>
                                    <td class="px-4 py-2 text-right text-gray-400 font-semibold text-[11px] text-blue-600 hover:underline">Details &rarr;</td>
                                </tr>
                            @endforeach
                            @if($sprint->tasks->isEmpty())
                                <tr x-show="isSprintDetailsOpen({{ $sprint->id }})" class="bg-gray-50/20">
                                    <td colspan="8" class="px-6 py-2 pl-14 text-xs text-gray-400 italic">
                                        No tasks assigned to this sprint yet.
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

    @endif

    {{-- ============================================================
    6. MODAL INCLUDES
    ============================================================ --}}
    @include('projects.sprints._create_edit_modal')
    @include('projects.sprints._assign_modal')
    @include('projects.sprints._delete_modal')

</div>

{{-- Alpine.js Component Script --}}
<script>
    function sprintsDashboardData() {
        return {
            activeSprintView: 'card_roadmap',
            searchSprintQuery: '',
            filterSprintStatus: '',
            filterSprintPriority: '',

            // Create / Edit Sprint Modal
            showSprintModal: false,
            sprintModalMode: 'create',
            sprintEditActionUrl: '',
            sprintForm: {
                code: '',
                name: '',
                goal: '',
                milestone_id: '',
                assigned_to: '',
                priority: 'normal',
                start_date: '',
                end_date: '',
                status: 'planning',
            },

            // Assign Tasks to Sprint Modal
            showSprintAssignModal: false,
            assignSprintId: null,
            assignSprintMilestoneId: null,
            assignSprintCode: '',
            assignSprintTitle: '',
            assignSprintActionUrl: '',
            assignSprintSearchQuery: '',
            selectedSprintTaskIds: [],

            // Expandable Sprint Details in Table
            openSprintDetailIds: [],
            toggleSprintDetails(id) {
                const idx = this.openSprintDetailIds.indexOf(id);
                if (idx > -1) {
                    this.openSprintDetailIds.splice(idx, 1);
                } else {
                    this.openSprintDetailIds.push(id);
                }
            },
            isSprintDetailsOpen(id) {
                return this.openSprintDetailIds.includes(id);
            },
            expandAllSprints() {
                this.openSprintDetailIds = [@foreach($allSprints as $s) {{ $s->id }}, @endforeach];
            },
            collapseAllSprints() {
                this.openSprintDetailIds = [];
            },
            openTaskModal(taskId) {
                if (typeof window.openTask === 'function') {
                    window.openTask(taskId);
                } else if (typeof window.openTaskModal === 'function') {
                    window.openTaskModal(taskId);
                } else {
                    window.dispatchEvent(new CustomEvent('open-task-modal', { detail: { taskId } }));
                }
            },

            initSprintsDashboard() {
                @if($allSprints->isNotEmpty())
                    this.openSprintDetailIds.push({{ $allSprints->first()->id }});
                @endif
            },

            matchesSprintFilter(nameAndCode, status, priority) {
                if (this.searchSprintQuery && !nameAndCode.includes(this.searchSprintQuery.toLowerCase())) return false;
                if (this.filterSprintStatus && status !== this.filterSprintStatus) return false;
                if (this.filterSprintPriority && priority !== this.filterSprintPriority) return false;
                return true;
            },

            openCreateSprintModal() {
                this.sprintModalMode = 'create';
                this.sprintForm = {
                    code: 'SP-' + String({{ $allSprints->count() + 1 }}).padStart(2, '0'),
                    name: '',
                    goal: '',
                    milestone_id: '',
                    assigned_to: '',
                    priority: 'normal',
                    start_date: '',
                    end_date: '',
                    status: 'planned',
                };
                this.showSprintModal = true;
            },
            isSubmittingSprint: false,
            isSubmittingSprintAssign: false,

            // Delete Modal State (Task-style)
            deleteConfirmOpen: false,
            deleteConfirmChecked: false,
            deleteSprintUrl: '',
            deleteSprintName: '',
            isDeletingSprint: false,

            openEditSprintModal(sp) {
                this.sprintModalMode = 'edit';
                this.sprintEditActionUrl = '{{ url('projects/' . $project->id . '/sprints') }}/' + sp.id;
                this.sprintForm = {
                    code: sp.code || ('SP-' + sp.id),
                    name: sp.name || '',
                    goal: sp.goal || '',
                    milestone_id: sp.milestone_id || '',
                    assigned_to: sp.assigned_to || '',
                    priority: sp.priority || 'normal',
                    start_date: sp.start_date ? sp.start_date.substring(0, 10) : '',
                    end_date: sp.end_date ? sp.end_date.substring(0, 10) : '',
                    status: sp.status || 'planning',
                };
                this.showSprintModal = true;
            },

            closeSprintModal() {
                this.showSprintModal = false;
            },

            openAssignSprintModal(sp, taskIds) {
                this.assignSprintId = sp.id;
                this.assignSprintMilestoneId = sp.milestone_id || null;
                this.assignSprintCode = sp.code || ('SP-' + sp.id);
                this.assignSprintTitle = sp.name;
                this.assignSprintActionUrl = '{{ url('projects/' . $project->id . '/sprints') }}/' + sp.id + '/assign';
                this.assignSprintSearchQuery = '';
                this.selectedSprintTaskIds = (taskIds ? [...taskIds] : []).map(Number);
                this.showSprintAssignModal = true;
            },

            closeSprintAssignModal() {
                this.showSprintAssignModal = false;
            },

            toggleSprintTaskSelection(id) {
                const numId = Number(id);
                const idx = this.selectedSprintTaskIds.indexOf(numId);
                if (idx > -1) {
                    this.selectedSprintTaskIds.splice(idx, 1);
                } else {
                    this.selectedSprintTaskIds.push(numId);
                }
            },

            async submitSprintForm(event) {
                if (this.isSubmittingSprint) return;
                this.isSubmittingSprint = true;

                const form = event.target;
                const url = this.sprintModalMode === 'edit' ? this.sprintEditActionUrl : form.action;
                const formData = new FormData(form);

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json().catch(() => null);

                    if (res.ok && (!data || data.success !== false)) {
                        this.closeSprintModal();
                        if (window.showToast) {
                            window.showToast('Changes saved successfully');
                        }
                        await this.refreshSprintsDOM();
                    } else {
                        let errorMsg = (data && data.message) ? data.message : 'Gagal menyimpan sprint.';
                        if (data && data.errors) {
                            const errList = Object.values(data.errors).flat();
                            if (errList.length > 0) {
                                errorMsg = errList.join(' ');
                            }
                        }
                        if (window.showToast) {
                            window.showToast(errorMsg, 'error');
                        } else if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Periksa Kembali',
                                html: errorMsg,
                                confirmButtonColor: '#4f46e5'
                            });
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (err) {
                    console.error('Sprint submission error:', err);
                    if (window.showToast) {
                        window.showToast('A system error occurred', 'error');
                    } else if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan jaringan atau sistem.',
                            confirmButtonColor: '#4f46e5'
                        });
                    } else {
                        alert('Terjadi kesalahan.');
                    }
                } finally {
                    this.isSubmittingSprint = false;
                }
            },

            async submitSprintAssignForm(event) {
                if (this.isSubmittingSprintAssign) return;
                this.isSubmittingSprintAssign = true;

                const form = event.target;
                const url = this.assignSprintActionUrl || form.action;
                const formData = new FormData();
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                    || form.querySelector('input[name="_token"]')?.value
                    || '{{ csrf_token() }}';
                formData.append('_token', csrfToken);
                (this.selectedSprintTaskIds || []).forEach(id => {
                    formData.append('task_ids[]', id);
                });

                try {
                    const res = await fetch(url, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });

                    const data = await res.json().catch(() => null);

                    if (res.ok && (!data || data.success !== false)) {
                        this.closeSprintAssignModal();
                        if (window.showToast) {
                            window.showToast('Changes saved successfully');
                        }

                        // Synchronize DOM elements on Tasks tab (Kanban cards and list rows)
                        const targetSprintId = this.assignSprintId ? this.assignSprintId.toString() : '';
                        const spMilestoneId = this.assignSprintMilestoneId ? this.assignSprintMilestoneId.toString() : '';
                        const selectedIds = (this.selectedSprintTaskIds || []).map(id => id.toString());

                        // 1. Reset sprintId on tasks previously in this sprint that were unselected
                        document.querySelectorAll(`.kanban-card[data-sprint-id="${targetSprintId}"], .list-task-row[data-sprint-id="${targetSprintId}"], tr[data-sprint-id="${targetSprintId}"]`).forEach(el => {
                            if (!selectedIds.includes(el.dataset.taskId)) {
                                el.dataset.sprintId = '';
                            }
                        });

                        // 2. Set sprintId (and milestoneId if sprint is linked) on all newly selected tasks
                        selectedIds.forEach(id => {
                            document.querySelectorAll(`.kanban-card[data-task-id="${id}"], .list-task-row[data-task-id="${id}"], tr[data-task-id="${id}"]`).forEach(el => {
                                el.dataset.sprintId = targetSprintId;
                                if (spMilestoneId) {
                                    el.dataset.milestoneId = spMilestoneId;
                                }
                            });
                        });

                        // 3. Re-apply task filters so if Sprint filter is active, matching tasks show immediately
                        const alpineEl = document.querySelector('[x-data*="projectPageData"]');
                        if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                            alpineEl._x_dataStack[0].applyFilters();
                        }

                        await this.refreshSprintsDOM();
                    } else {
                        let errorMsg = (data && data.message) ? data.message : 'Gagal menyimpan tasks ke sprint.';
                        if (data && data.errors) {
                            const errList = Object.values(data.errors).flat();
                            if (errList.length > 0) {
                                errorMsg = errList.join(' ');
                            }
                        }
                        if (window.showToast) {
                            window.showToast(errorMsg, 'error');
                        } else if (window.Swal) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Periksa Kembali',
                                html: errorMsg,
                                confirmButtonColor: '#4f46e5'
                            });
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (err) {
                    console.error('Sprint assign error:', err);
                    if (window.showToast) {
                        window.showToast('A system error occurred', 'error');
                    } else if (window.Swal) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Terjadi kesalahan jaringan atau sistem.',
                            confirmButtonColor: '#4f46e5'
                        });
                    } else {
                        alert('Terjadi kesalahan.');
                    }
                } finally {
                    this.isSubmittingSprintAssign = false;
                }
            },

            promptDeleteSprint(url, name) {
                this.deleteSprintUrl = url;
                this.deleteSprintName = name;
                this.deleteConfirmChecked = false;
                this.deleteConfirmOpen = true;
            },

            async confirmDeleteSprint() {
                if (!this.deleteConfirmChecked || this.isDeletingSprint) return;
                this.isDeletingSprint = true;

                try {
                    const res = await fetch(this.deleteSprintUrl, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    });
                    const data = await res.json().catch(() => null);

                    this.deleteConfirmOpen = false;
                    this.deleteConfirmChecked = false;

                    if (res.ok) {
                        if (window.showToast) {
                            window.showToast('Changes saved successfully');
                        }
                        await this.refreshSprintsDOM();
                    } else {
                        let errorMsg = (data && data.message) ? data.message : 'Gagal menghapus sprint.';
                        if (window.showToast) {
                            window.showToast(errorMsg, 'error');
                        } else if (window.Swal) {
                            Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
                        } else {
                            alert(errorMsg);
                        }
                    }
                } catch (err) {
                    console.error('Delete sprint error:', err);
                    if (window.showToast) {
                        window.showToast('A system error occurred', 'error');
                    } else if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
                    }
                } finally {
                    this.isDeletingSprint = false;
                }
            },

            async refreshSprintsDOM() {
                try {
                    const res = await fetch(window.location.href);
                    const html = await res.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');

                    const newContainer = doc.querySelector('#sprints-dashboard-root');
                    const currentContainer = document.querySelector('#sprints-dashboard-root');

                    if (newContainer && currentContainer) {
                        if (window.Alpine && typeof window.Alpine.destroyTree === 'function') {
                            window.Alpine.destroyTree(currentContainer);
                        }
                        currentContainer.outerHTML = newContainer.outerHTML;
                        const refreshed = document.querySelector('#sprints-dashboard-root');
                        if (refreshed && window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(refreshed);
                        }
                    }
                } catch (e) {
                    console.error('Failed to refresh sprints DOM:', e);
                }
            },

            showToast(message = 'Changes saved successfully', type = 'success') {
                if (window.showToast) {
                    window.showToast(message, type);
                }
            }
        };
    }
</script>