{{-- ============================================================
     MILESTONES DASHBOARD & MANAGEMENT (Card & Roadmap, Hierarchy Table, Hierarchy Diagram)
     ============================================================ --}}
@php
    $allMilestones = $project->milestones()->with(['assignee', 'sprints.lead', 'sprints.tasks.assignee', 'standaloneTasks.assignee', 'tasks.assignee'])->get();
    $totalMilestones = $allMilestones->count();
    $completedMilestones = $allMilestones->where('status', 'completed')->count();
    $inProgressMilestones = $allMilestones->whereIn('status', ['in_progress', 'active'])->count();
    $atRiskMilestones = $allMilestones->filter(fn($m) => $m->status === 'at_risk' || $m->isOverdue())->count();

    // Standalone tasks (not in sprint and not in milestone, or tasks directly in milestone)
    $allProjectTasks = $project->tasks;
    $allProjectSprints = $project->sprints;

    // Timeline scale calculation for Roadmap
    $minDate = now()->startOfMonth();
    $maxDate = now()->addMonths(3)->endOfMonth();

    foreach($allMilestones as $m) {
        if ($m->start_date && $m->start_date->lt($minDate)) $minDate = $m->start_date->copy()->startOfMonth();
        if ($m->due_date && $m->due_date->gt($maxDate)) $maxDate = $m->due_date->copy()->endOfMonth();
    }
    $totalRoadmapDays = max(1, $minDate->diffInDays($maxDate));
@endphp

<div id="milestones-dashboard-root" x-data="milestonesDashboardData()" x-init="initMilestonesDashboard()" class="space-y-6">

    {{-- ============================================================
         1. TOP KPI STAT CARDS (UNIFIED 4-COLUMN CARD MATCHING GAMBAR 3)
         ============================================================ --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 shadow-2xs grid grid-cols-2 lg:grid-cols-4 divide-y sm:divide-y-0 sm:divide-x divide-gray-100 dark:divide-gray-800">
        {{-- Col 1: TOTAL MILESTONES --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">TOTAL MILESTONES</span>
                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9"/>
                </svg>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $totalMilestones }}</p>
            <div class="flex items-center gap-1.5 text-xs font-semibold">
                <span class="text-emerald-600 dark:text-emerald-400">↑ {{ $totalMilestones }} in scope</span>
                <span class="text-gray-300 dark:text-gray-600">·</span>
                <span class="text-gray-400 dark:text-gray-500 font-normal">100% charted</span>
            </div>
        </div>

        {{-- Col 2: IN PROGRESS --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IN PROGRESS</span>
                <div class="w-4 h-4 rounded-full border-2 border-amber-500 flex items-center justify-start overflow-hidden">
                    <div class="w-2 h-4 bg-amber-500"></div>
                </div>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $inProgressMilestones }}</p>
            <div class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/>
                </svg>
                <span>{{ $totalMilestones > 0 ? round(($inProgressMilestones / $totalMilestones) * 100) : 0 }}% active tracking</span>
            </div>
        </div>

        {{-- Col 3: COMPLETED --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">COMPLETED</span>
                <svg class="w-4 h-4 text-emerald-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
            </div>
            <p class="text-3xl font-black text-gray-900 dark:text-white leading-none my-2.5">{{ $completedMilestones }}</p>
            <div class="flex items-center gap-1 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span>{{ $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0 }}% delivered</span>
            </div>
        </div>

        {{-- Col 4: AT RISK / ISSUES --}}
        <div class="p-5 flex flex-col justify-between">
            <div class="flex items-center justify-between">
                <span class="text-[10px] font-extrabold text-gray-500 dark:text-gray-400 uppercase tracking-wider">AT RISK / ISSUES</span>
                <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <p class="text-3xl font-black text-rose-600 dark:text-rose-400 leading-none my-2.5">{{ $atRiskMilestones }}</p>
            <div class="flex items-center gap-1 text-xs font-semibold {{ $atRiskMilestones > 0 ? 'text-rose-600 dark:text-rose-400' : 'text-emerald-600 dark:text-emerald-400' }}">
                @if($atRiskMilestones > 0)
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="10" stroke-width="2"/>
                        <path stroke-width="2" d="M12 8v4m0 4h.01"/>
                    </svg>
                    <span>Needs Review (Blocked)</span>
                @else
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
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
        
        {{-- View Switcher (3 Tabs) --}}
        <div class="inline-flex p-1 bg-gray-100 dark:bg-gray-800 rounded-xl border border-gray-200/60 dark:border-gray-750 self-start">
            <button type="button" @click="activeMilestoneView = 'card_roadmap'"
                :class="activeMilestoneView === 'card_roadmap' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" />
                </svg>
                <span>Card & Roadmap</span>
            </button>

            <button type="button" @click="activeMilestoneView = 'hierarchy_table'"
                :class="activeMilestoneView === 'hierarchy_table' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                </svg>
                <span>Hierarchy Table</span>
            </button>

            <button type="button" @click="activeMilestoneView = 'hierarchy_diagram'"
                :class="activeMilestoneView === 'hierarchy_diagram' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-xs font-bold' : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white font-medium'"
                class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-lg text-xs transition cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
                </svg>
                <span>Hierarchy Diagram</span>
            </button>
        </div>

        {{-- Filters & Action Buttons --}}
        <div class="flex items-center gap-2.5 flex-wrap justify-end">
            {{-- Search Bar --}}
            <div class="relative min-w-[190px]">
                <input type="text" x-model="searchQuery" placeholder="Search milestones..."
                    class="w-full pl-8 pr-3.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                <svg class="w-3.5 h-3.5 text-slate-400 absolute left-2.5 top-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>

            {{-- Status Filter Popover --}}
            <div class="relative" x-data="{ openFilterStatus: false }">
                <button type="button" @click="openFilterStatus = !openFilterStatus"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                    <span class="flex items-center gap-2">
                        <span class="w-2 h-2 rounded-full shrink-0"
                            :class="{
                                'bg-slate-300 dark:bg-slate-600': !filterStatus,
                                'bg-slate-400': filterStatus === 'planning',
                                'bg-blue-500': filterStatus === 'in_progress',
                                'bg-emerald-500': filterStatus === 'completed',
                                'bg-rose-500': filterStatus === 'at_risk',
                                'bg-amber-500': filterStatus === 'pending'
                            }"></span>
                        <span class="font-medium" x-text="{
                            '': 'All Status',
                            'planning': 'Planning Phase',
                            'in_progress': 'In Progress',
                            'completed': 'Completed',
                            'at_risk': 'At Risk',
                            'pending': 'Pending'
                        }[filterStatus] || 'All Status'"></span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openFilterStatus" @click.outside="openFilterStatus = false" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 top-full mt-1.5 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                    <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        FILTER STATUS
                    </div>
                    <div class="space-y-0.5 mt-1 px-1.5">
                        <button type="button" @click="filterStatus = ''; openFilterStatus = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterStatus === '' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                <span>All Status</span>
                            </span>
                            <svg x-show="filterStatus === ''" class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        @foreach([
                            ['value' => 'planning', 'label' => 'Planning Phase', 'dot' => 'bg-slate-400'],
                            ['value' => 'in_progress', 'label' => 'In Progress', 'dot' => 'bg-blue-500'],
                            ['value' => 'completed', 'label' => 'Completed', 'dot' => 'bg-emerald-500'],
                            ['value' => 'at_risk', 'label' => 'At Risk', 'dot' => 'bg-rose-500'],
                            ['value' => 'pending', 'label' => 'Pending', 'dot' => 'bg-amber-500'],
                        ] as $st)
                            <button type="button" @click="filterStatus = '{{ $st['value'] }}'; openFilterStatus = false"
                                class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                                :class="filterStatus === '{{ $st['value'] }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                <span class="flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $st['dot'] }}"></span>
                                    <span>{{ $st['label'] }}</span>
                                </span>
                                <svg x-show="filterStatus === '{{ $st['value'] }}'" class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Priority Filter Popover (Task Priority Match) --}}
            <div class="relative" x-data="{ openFilterPriority: false }">
                <button type="button" @click="openFilterPriority = !openFilterPriority"
                    class="flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-700 dark:text-slate-300 hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                    <span class="flex items-center gap-2">
                        <span x-show="!filterPriority" class="w-2 h-2 rounded-full bg-slate-300 shrink-0"></span>
                        <span x-show="filterPriority === 'urgent'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 11l7-7 7 7M5 19l7-7 7 7"/></svg>
                        </span>
                        <span x-show="filterPriority === 'high'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>
                        </span>
                        <span x-show="filterPriority === 'medium'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 9h16M4 15h16"/></svg>
                        </span>
                        <span x-show="filterPriority === 'low'" class="shrink-0">
                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        </span>

                        <span class="font-medium"
                            :class="{
                                'text-red-600 font-semibold': filterPriority === 'urgent',
                                'text-amber-700 font-semibold': filterPriority === 'high',
                            }"
                            x-text="{
                                '': 'All Priority',
                                'urgent': 'Urgent',
                                'high': 'High',
                                'medium': 'Medium',
                                'low': 'Low'
                            }[filterPriority] || 'All Priority'"></span>
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div x-show="openFilterPriority" @click.outside="openFilterPriority = false" x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="absolute right-0 top-full mt-1.5 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                    <div class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                        FILTER PRIORITY
                    </div>
                    <div class="space-y-0.5 mt-1 px-1.5">
                        <button type="button" @click="filterPriority = ''; openFilterPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterPriority === '' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-slate-300"></span>
                                <span>All Priority</span>
                            </span>
                            <svg x-show="filterPriority === ''" class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- Urgent --}}
                        <button type="button" @click="filterPriority = 'urgent'; openFilterPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterPriority === 'urgent' ? 'bg-red-50/80 dark:bg-red-950/40 text-red-700 dark:text-red-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 11l7-7 7 7M5 19l7-7 7 7"/>
                                </svg>
                                <span>Urgent</span>
                                <span class="px-1 py-0.2 bg-red-100 text-red-700 text-[8px] font-bold rounded">CRITICAL</span>
                            </span>
                            <svg x-show="filterPriority === 'urgent'" class="w-3.5 h-3.5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- High --}}
                        <button type="button" @click="filterPriority = 'high'; openFilterPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterPriority === 'high' ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/>
                                </svg>
                                <span>High</span>
                            </span>
                            <svg x-show="filterPriority === 'high'" class="w-3.5 h-3.5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- Medium --}}
                        <button type="button" @click="filterPriority = 'medium'; openFilterPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterPriority === 'medium' ? 'bg-slate-100 dark:bg-slate-700 font-semibold text-slate-800 dark:text-slate-100' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 9h16M4 15h16"/>
                                </svg>
                                <span>Medium</span>
                            </span>
                            <svg x-show="filterPriority === 'medium'" class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>

                        {{-- Low --}}
                        <button type="button" @click="filterPriority = 'low'; openFilterPriority = false"
                            class="w-full text-left px-2.5 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                            :class="filterPriority === 'low' ? 'bg-emerald-50/80 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                            <span class="flex items-center gap-2">
                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                                </svg>
                                <span>Low</span>
                            </span>
                            <svg x-show="filterPriority === 'low'" class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {{-- Clear Filters --}}
            <button type="button" x-show="searchQuery || filterStatus || filterPriority" @click="searchQuery = ''; filterStatus = ''; filterPriority = ''" x-cloak
                class="text-xs font-semibold text-rose-500 hover:text-rose-600 px-2 py-1.5 transition">
                Clear
            </button>

            {{-- Create Milestone Button --}}
            @if(!auth()->user()->hasRole('client'))
                <button type="button" @click="openCreateMilestoneModal()"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Milestone</span>
                </button>
            @endif
        </div>
    </div>

    {{-- ============================================================
         3. EMPTY STATE
         ============================================================ --}}
    @if($allMilestones->isEmpty())
        <div class="bg-white dark:bg-gray-850 rounded-3xl border border-gray-200/90 dark:border-gray-700/80 p-8 sm:p-12 text-center shadow-2xs space-y-5">
            <div class="relative w-16 h-16 mx-auto rounded-3xl bg-blue-50 dark:bg-blue-950/60 border-2 border-blue-200/70 dark:border-blue-800/80 flex items-center justify-center text-blue-600 dark:text-blue-400">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                </svg>
            </div>
            <div class="space-y-1.5 max-w-md mx-auto">
                <h3 class="text-lg font-black text-gray-900 dark:text-white">No Milestones Defined Yet</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Group sprints and deliverables into structured phases to track progress toward high-level business goals.
                </p>
            </div>
            @if(!auth()->user()->hasRole('client'))
                <button type="button" @click="openCreateMilestoneModal()"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-sm cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create First Milestone</span>
                </button>
            @endif
        </div>
    @else

        {{-- ============================================================
             4. VIEW 1: CARD & ROADMAP VIEW
             ============================================================ --}}
        <div x-show="activeMilestoneView === 'card_roadmap'" x-cloak class="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
            
            {{-- Left Column: Milestone Cards (5 cols) --}}
            <div class="xl:col-span-6 space-y-4">
                @foreach($allMilestones as $ms)
                    @php
                        $mPct = $ms->taskProgressPercent();
                        $mDaysLeft = $ms->workingDaysRemaining();
                        $mTotalSprints = $ms->sprints->count();
                        $mTotalTasks = $ms->standaloneTasks->count();
                        foreach($ms->sprints as $sp) {
                            $mTotalTasks += $sp->tasks->count();
                        }
                        $statusStyle = $ms->statusStyle();
                        $priorityStyle = $ms->priorityStyle();
                    @endphp
                    <div class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs hover:shadow-md transition-all group"
                        x-show="matchesFilter('{{ strtolower(addslashes($ms->title . ' ' . $ms->code)) }}', '{{ $ms->status }}', '{{ $ms->priority ?? 'low' }}')">
                        
                        {{-- Top Badges & Actions --}}
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="px-2 py-0.5 rounded-md text-[11px] font-extrabold bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-400 border border-blue-200/50 font-mono">
                                    {{ $ms->code ?? ('MS-' . $ms->id) }}
                                </span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusStyle['pill'] }}">
                                    <span class="inline-block w-1.5 h-1.5 rounded-full {{ $statusStyle['dot'] }} mr-1"></span>
                                    {{ $statusStyle['label'] }}
                                </span>
                                @if($ms->priority)
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold {{ $priorityStyle['pill'] }}">
                                        {{ $priorityStyle['label'] }}
                                    </span>
                                @endif
                                @if($ms->release_target)
                                    <span class="px-2 py-0.5 rounded-md text-[10px] font-semibold bg-purple-50 dark:bg-purple-950/60 text-purple-700 dark:text-purple-300 border border-purple-200/50">
                                        {{ $ms->release_target }}
                                    </span>
                                @endif
                            </div>

                            {{-- Dropdown Action Menu --}}
                            <div class="relative shrink-0" x-data="{ open: false }">
                                <button type="button" @click="open = !open" @click.away="open = false"
                                    class="p-1.5 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z" />
                                    </svg>
                                </button>
                                <div x-show="open" x-cloak
                                    class="absolute right-0 top-full mt-1 w-44 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl shadow-lg p-1.5 z-30 space-y-0.5 text-xs">
                                    <button type="button" @click="open = false; window.filterTasksByMilestone({{ $ms->id }}, '{{ addslashes($ms->title) }}')"
                                        class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-blue-600 dark:text-blue-400 flex items-center gap-2 font-medium">
                                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                        </svg>
                                        <span>View Tasks Board</span>
                                    </button>

                                    @if(!auth()->user()->hasRole('client'))
                                        <button type="button" @click="open = false; openEditMilestoneModal({{ json_encode($ms) }})"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center gap-2 font-medium">
                                            <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                            </svg>
                                            <span>Edit Milestone</span>
                                        </button>

                                        <button type="button" @click="open = false; openAssignMilestoneModal({{ json_encode($ms) }}, {{ json_encode($ms->sprints->pluck('id')) }}, {{ json_encode($ms->standaloneTasks->pluck('id')) }})"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-purple-600 dark:text-purple-400 flex items-center gap-2 font-medium">
                                            <svg class="w-3.5 h-3.5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                            <span>Assign Sprints & Tasks</span>
                                        </button>
                                    @endif

                                    @if($ms->google_meet_link)
                                        <a href="{{ $ms->google_meet_link }}" target="_blank" rel="noopener"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-emerald-600 font-medium flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-emerald-500" fill="currentColor" viewBox="0 0 24 24">
                                                <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z" />
                                            </svg>
                                            <span>Join Meet</span>
                                        </a>
                                    @elseif(!auth()->user()->hasRole('client') && $project->google_meet_enabled)
                                        <form method="POST" action="{{ route('milestones.meeting.create', [$project, $ms]) }}">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 flex items-center gap-2 font-medium">
                                                <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                                                </svg>
                                                <span>Create Meet</span>
                                            </button>
                                        </form>
                                    @endif

                                    @if(!auth()->user()->hasRole('client'))
                                        <button type="button"
                                            @click="promptDeleteMilestone('{{ route('milestones.destroy', [$project, $ms]) }}', '{{ addslashes($ms->title) }}')"
                                            class="w-full text-left px-2.5 py-1.5 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 text-rose-600 font-medium flex items-center gap-2">
                                            <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5" />
                                            </svg>
                                            <span>Delete</span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>

                        {{-- Title & Scope --}}
                        <div class="mb-3">
                            <h4 @click="window.filterTasksByMilestone({{ $ms->id }}, '{{ addslashes($ms->title) }}')"
                                class="text-sm font-bold text-gray-900 dark:text-white hover:text-blue-600 cursor-pointer transition-colors"
                                title="View tasks for this milestone">
                                {{ $ms->title }}
                            </h4>
                            @if($ms->description)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2 leading-relaxed">
                                    {{ $ms->description }}
                                </p>
                            @endif
                        </div>

                        {{-- PIC & Timeline --}}
                        <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400 py-2 border-t border-b border-gray-100 dark:border-gray-800 mb-3">
                            <div class="flex items-center gap-2">
                                @if($ms->assignee)
                                    <div class="w-6 h-6 rounded-full bg-blue-100 text-blue-700 text-[10px] font-black flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($ms->assignee->name, 0, 2)) }}
                                    </div>
                                    <span class="font-semibold text-gray-800 dark:text-gray-200">{{ $ms->assignee->name }}</span>
                                @else
                                    <span class="text-gray-400 italic">No PIC</span>
                                @endif
                            </div>

                            <div class="text-right">
                                <span class="font-medium text-gray-800 dark:text-gray-200">
                                    {{ $ms->start_date ? $ms->start_date->format('d M') : 'Start' }} → {{ $ms->due_date ? $ms->due_date->format('d M Y') : 'Due' }}
                                </span>
                                @if($mDaysLeft !== null)
                                    <span class="block text-[10px] {{ $ms->isOverdue() ? 'text-rose-500 font-bold' : 'text-gray-400' }}">
                                        {{ $ms->isOverdue() ? abs($mDaysLeft) . ' days overdue' : $mDaysLeft . ' days left' }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Progress & Hierarchy Badges --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="font-bold text-gray-700 dark:text-gray-300">Progress</span>
                                    <span class="text-[10px] px-1.5 py-0.2 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-bold">
                                        {{ $mTotalSprints }} sprints &bull; {{ $mTotalTasks }} tasks
                                    </span>
                                </div>
                                <span class="font-black text-blue-600 dark:text-blue-400">{{ $mPct }}%</span>
                            </div>
                            <div class="w-full bg-gray-100 dark:bg-gray-800 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full bg-gradient-to-r from-blue-600 via-indigo-600 to-emerald-500 transition-all duration-500"
                                    style="width: {{ min(100, $mPct) }}%"></div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- Right Column: Interactive Roadmap Gantt View (7 cols) --}}
            <div class="xl:col-span-6 bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-5 shadow-2xs overflow-hidden">
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-gray-100 dark:border-gray-750">
                    <div>
                        <h4 class="text-sm font-bold text-gray-900 dark:text-white">Milestone Delivery Roadmap</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Timeline & delivery target schedule across sprints</p>
                    </div>
                    <span class="text-[11px] font-semibold px-2.5 py-1 bg-gray-100 dark:bg-gray-800 rounded-lg text-gray-600 dark:text-gray-300">
                        {{ $minDate->format('M Y') }} – {{ $maxDate->format('M Y') }}
                    </span>
                </div>

                {{-- Roadmap Timeline Container --}}
                <div class="overflow-x-auto">
                    <div class="min-w-[550px] space-y-4">
                        {{-- Timeline Header Scale (Months / Quarters) --}}
                        <div class="flex border-b border-gray-200 dark:border-gray-700 pb-2 text-[11px] font-bold text-gray-400 uppercase tracking-wider">
                            @php
                                $cursor = $minDate->copy();
                                $monthSegments = [];
                                while ($cursor->lte($maxDate)) {
                                    $monthSegments[] = $cursor->copy();
                                    $cursor->addMonth();
                                }
                            @endphp
                            @foreach($monthSegments as $mSeg)
                                <div class="flex-1 text-center border-r border-gray-100 dark:border-gray-800 last:border-r-0">
                                    {{ $mSeg->format('M Y') }}
                                </div>
                            @endforeach
                        </div>

                        {{-- Milestone Roadmap Bars --}}
                        <div class="space-y-3 pt-2">
                            @foreach($allMilestones as $ms)
                                @php
                                    $start = $ms->start_date ?? $minDate;
                                    $end = $ms->due_date ?? $start->copy()->addDays(14);
                                    if ($start->lt($minDate)) $start = $minDate->copy();
                                    if ($end->gt($maxDate)) $end = $maxDate->copy();

                                    $offsetDays = max(0, $minDate->diffInDays($start));
                                    $durationDays = max(1, $start->diffInDays($end));
                                    $leftPercent = round(($offsetDays / $totalRoadmapDays) * 100, 2);
                                    $widthPercent = max(6, min(100 - $leftPercent, round(($durationDays / $totalRoadmapDays) * 100, 2)));
                                    $mPct = $ms->taskProgressPercent();
                                    $statusStyle = $ms->statusStyle();
                                @endphp
                                <div class="space-y-1">
                                    <div class="flex items-center justify-between text-xs">
                                        <div class="flex items-center gap-1.5">
                                            <span class="font-extrabold text-[10px] font-mono text-blue-600 dark:text-blue-400">{{ $ms->code ?? ('MS-' . $ms->id) }}</span>
                                            <span class="font-bold text-gray-800 dark:text-gray-200 truncate max-w-[200px]">{{ $ms->title }}</span>
                                        </div>
                                        <span class="text-[10px] font-bold text-gray-500">{{ $start->format('d M') }} – {{ $end->format('d M') }} ({{ $mPct }}%)</span>
                                    </div>

                                    {{-- Bar Track --}}
                                    <div class="relative w-full h-8 bg-gray-50 dark:bg-gray-800/60 rounded-xl overflow-hidden border border-gray-100 dark:border-gray-750">
                                        {{-- Milestone Bar --}}
                                        <div class="absolute top-1 bottom-1 rounded-lg shadow-2xs flex items-center px-2.5 text-[11px] font-bold text-white transition-all duration-300 {{ $ms->status === 'completed' ? 'bg-emerald-600' : ($ms->isOverdue() ? 'bg-rose-600' : 'bg-gradient-to-r from-blue-600 to-indigo-600') }}"
                                            style="left: {{ $leftPercent }}%; width: {{ $widthPercent }}%">
                                            <span class="truncate">{{ $ms->title }}</span>
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
             5. VIEW 2: HIERARCHY TABLE VIEW
             ============================================================ --}}
        <div x-show="activeMilestoneView === 'hierarchy_table'" x-cloak
            class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 shadow-2xs overflow-hidden">
            
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-750 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Multi-Level Hierarchy Breakdown</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Milestones &rarr; Sprints &rarr; Tasks & Standalone Activities</p>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="expandAllHierarchy()" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition">
                        Expand All
                    </button>
                    <button type="button" @click="collapseAllHierarchy()" class="px-3 py-1 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 text-xs font-semibold rounded-lg transition">
                        Collapse All
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-xs">
                    <thead class="bg-gray-50/75 dark:bg-gray-800/60 font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5 text-left min-w-[280px]">Item / Deliverable</th>
                            <th class="px-4 py-3.5 text-left">PIC / Lead</th>
                            <th class="px-4 py-3.5 text-left">Status</th>
                            <th class="px-4 py-3.5 text-left">Priority</th>
                            <th class="px-4 py-3.5 text-left">Progress</th>
                            <th class="px-4 py-3.5 text-left">Timeline</th>
                            <th class="px-4 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800 font-medium">
                        @foreach($allMilestones as $ms)
                            @php
                                $mPct = $ms->taskProgressPercent();
                                $statusStyle = $ms->statusStyle();
                                $priorityStyle = $ms->priorityStyle();
                            @endphp
                            {{-- LEVEL 1: MILESTONE ROW --}}
                            <tr class="bg-blue-50/30 dark:bg-blue-950/20 hover:bg-blue-50/60 dark:hover:bg-blue-950/40 transition">
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center gap-2.5">
                                        <button type="button" @click="toggleHierarchy({{ $ms->id }})" class="p-1 text-gray-500 hover:text-blue-600 rounded transition">
                                            <svg class="w-4 h-4 transform transition-transform" :class="isHierarchyOpen({{ $ms->id }}) ? 'rotate-90 text-blue-600' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                            </svg>
                                        </button>
                                        <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-mono">
                                            {{ $ms->code ?? ('MS-' . $ms->id) }}
                                        </span>
                                        <span @click="window.filterTasksByMilestone({{ $ms->id }}, '{{ addslashes($ms->title) }}')"
                                            class="font-bold text-gray-900 dark:text-white text-sm hover:text-blue-600 cursor-pointer transition"
                                            title="View tasks for this milestone">{{ $ms->title }}</span>
                                        @if($ms->release_target)
                                            <span class="text-[10px] px-1.5 py-0.2 rounded bg-purple-50 text-purple-700 border border-purple-200">{{ $ms->release_target }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-800 dark:text-gray-200">
                                    <div class="flex items-center gap-1.5">
                                        @if($ms->assignee)
                                            @if($ms->assignee->avatar)
                                                <img src="{{ Storage::url($ms->assignee->avatar) }}" alt="{{ $ms->assignee->name }}" class="w-5 h-5 rounded-full object-cover">
                                            @else
                                                <div class="w-5 h-5 rounded-full ring-1 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[9px] font-bold shadow-2xs"
                                                     style="background-color: {{ $ms->assignee->avatarColor() }};"
                                                     title="{{ $ms->assignee->name }}">
                                                    {{ $ms->assignee->initials() }}
                                                </div>
                                            @endif
                                            <span class="text-xs font-semibold">{{ $ms->assignee->name }}</span>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold {{ $statusStyle['pill'] }}">
                                        {{ $statusStyle['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-bold {{ $priorityStyle['pill'] }}">
                                        {{ $priorityStyle['label'] }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2 min-w-[100px]">
                                        <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                            <div class="h-1.5 rounded-full bg-blue-600" style="width: {{ $mPct }}%"></div>
                                        </div>
                                        <span class="font-extrabold text-blue-600">{{ $mPct }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 text-gray-600 dark:text-gray-300">
                                    {{ $ms->start_date ? $ms->start_date->format('d M') : '—' }} → {{ $ms->due_date ? $ms->due_date->format('d M Y') : '—' }}
                                </td>
                                <td class="px-4 py-3.5 text-right space-x-1">
                                    @if(!auth()->user()->hasRole('client'))
                                        <button type="button" @click="openAssignMilestoneModal({{ json_encode($ms) }}, {{ json_encode($ms->sprints->pluck('id')) }}, {{ json_encode($ms->standaloneTasks->pluck('id')) }})"
                                            class="p-1.5 text-purple-600 hover:bg-purple-50 rounded-lg" title="Assign Items">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="openEditMilestoneModal({{ json_encode($ms) }})"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-lg" title="Edit">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 11l6-6 3 3-6 6H9v-3z" />
                                            </svg>
                                        </button>
                                        <button type="button" @click="promptDeleteMilestone('{{ route('milestones.destroy', [$project, $ms]) }}', '{{ addslashes($ms->title) }}')"
                                            class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg" title="Delete">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7h6m2 0a1 1 0 00-1-1h-4a1 1 0 00-1 1H5" />
                                            </svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            {{-- LEVEL 2: SPRINTS & STANDALONE TASKS (Shown when hierarchy expanded) --}}
                            @foreach($ms->sprints as $sp)
                                @php
                                    $spPct = $sp->taskProgressPercent();
                                @endphp
                                <tr x-show="isHierarchyOpen({{ $ms->id }})" class="bg-gray-50/50 dark:bg-gray-800/30 hover:bg-gray-100/50 transition">
                                    <td class="px-6 py-2.5 pl-12">
                                        <div class="flex items-center gap-2">
                                            <span class="text-indigo-400">&lfloor;</span>
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-extrabold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-mono">
                                                {{ $sp->code ?? ('SP-' . $sp->id) }}
                                            </span>
                                            <button type="button" @click="window.filterTasksBySprint({{ $sp->id }}, '{{ addslashes($sp->name) }}')"
                                                class="font-bold text-gray-800 dark:text-gray-200 hover:text-blue-600 cursor-pointer transition text-left"
                                                title="View tasks for this sprint">
                                                {{ $sp->name }}
                                            </button>
                                            <span class="text-[10px] text-gray-400">({{ $sp->tasks->count() }} tasks)</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-700 dark:text-gray-300">
                                        <div class="flex items-center gap-1.5">
                                            @if($sp->lead)
                                                @if($sp->lead->avatar)
                                                    <img src="{{ Storage::url($sp->lead->avatar) }}" alt="{{ $sp->lead->name }}" class="w-5 h-5 rounded-full object-cover">
                                                @else
                                                    <div class="w-5 h-5 rounded-full ring-1 ring-white dark:ring-gray-800 text-white flex items-center justify-center text-[9px] font-bold shadow-2xs"
                                                         style="background-color: {{ $sp->lead->avatarColor() }};"
                                                         title="{{ $sp->lead->name }}">
                                                        {{ $sp->lead->initials() }}
                                                    </div>
                                                @endif
                                                <span class="text-xs font-medium">{{ $sp->lead->name }}</span>
                                            @else
                                                <span class="text-xs text-gray-400">—</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sp->statusStyle()['pill'] }}">{{ $sp->statusLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-semibold {{ $sp->priorityStyle()['pill'] }}">{{ $sp->priorityLabel() }}</span>
                                    </td>
                                    <td class="px-4 py-2.5">
                                        <div class="flex items-center gap-2 min-w-[80px]">
                                            <div class="flex-1 bg-gray-200 dark:bg-gray-700 rounded-full h-1 overflow-hidden">
                                                <div class="h-1 rounded-full bg-indigo-600" style="width: {{ $spPct }}%"></div>
                                            </div>
                                            <span class="font-bold text-indigo-600 text-[11px]">{{ $spPct }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-500">
                                        {{ $sp->start_date ? $sp->start_date->format('d M') : '—' }} → {{ $sp->end_date ? $sp->end_date->format('d M') : '—' }}
                                    </td>
                                    <td class="px-4 py-2.5 text-right">
                                        <button type="button" @click="window.filterTasksBySprint({{ $sp->id }}, '{{ addslashes($sp->name) }}')"
                                            class="text-blue-600 hover:underline font-semibold text-[11px] cursor-pointer"
                                            title="View tasks for this sprint">View Board &rarr;</button>
                                    </td>
                                </tr>

                                {{-- LEVEL 3: TASKS IN SPRINT --}}
                                @foreach($sp->tasks as $t)
                                    <tr x-show="isHierarchyOpen({{ $ms->id }})"
                                        class="hover:bg-gray-50 dark:hover:bg-gray-800/40 transition cursor-pointer"
                                        @click="openTaskModal({{ $t->id }})">
                                        <td class="px-6 py-2 pl-20 text-gray-600 dark:text-gray-400">
                                            <div class="flex items-center gap-2">
                                                <span class="text-gray-300">&bull;</span>
                                                @if($t->code)
                                                    <span class="font-mono text-[10px] text-gray-400">{{ $t->code }}</span>
                                                @endif
                                                <span class="hover:text-blue-600 transition">{{ $t->title }}</span>
                                            </div>
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
                                        <td class="px-4 py-2 text-right text-gray-400">Details &rarr;</td>
                                    </tr>
                                @endforeach
                            @endforeach

                            {{-- LEVEL 2b: STANDALONE TASKS UNDER MILESTONE --}}
                            @foreach($ms->standaloneTasks as $t)
                                <tr x-show="isHierarchyOpen({{ $ms->id }})"
                                    class="bg-emerald-50/20 hover:bg-emerald-50/40 transition cursor-pointer"
                                    @click="openTaskModal({{ $t->id }})">
                                    <td class="px-6 py-2.5 pl-12">
                                        <div class="flex items-center gap-2">
                                            <span class="text-emerald-500">&lfloor;</span>
                                            <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-100 text-emerald-700">Direct Task</span>
                                            @if($t->code)
                                                <span class="font-mono text-[10px] text-gray-400">{{ $t->code }}</span>
                                            @endif
                                            <span class="font-medium text-gray-800 dark:text-gray-200">{{ $t->title }}</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
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
                                    <td class="px-4 py-2.5">
                                        <span class="text-[10px] px-2 py-0.5 rounded font-bold {{ $t->status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                            {{ $t->boardColumn?->name ?? ucfirst($t->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-2.5 capitalize text-[10px] text-gray-500">{{ $t->priority ?? 'normal' }}</td>
                                    <td class="px-4 py-2.5 text-gray-400">{{ $t->status === 'done' ? '100%' : '0%' }}</td>
                                    <td class="px-4 py-2.5 text-gray-400">{{ $t->due_date ? \Carbon\Carbon::parse($t->due_date)->format('d M') : '—' }}</td>
                                    <td class="px-4 py-2.5 text-right text-gray-400">Details &rarr;</td>
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- ============================================================
             6. VIEW 3: HIERARCHY DIAGRAM VIEW (Interactive Tree Graph)
             ============================================================ --}}
        <div x-show="activeMilestoneView === 'hierarchy_diagram'" x-cloak
            class="bg-white dark:bg-gray-850 rounded-2xl border border-gray-200/90 dark:border-gray-700/80 p-6 shadow-2xs overflow-x-auto">
            
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h4 class="text-sm font-bold text-gray-900 dark:text-white">Milestone & Delivery Tree Architecture</h4>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Visual mapping of milestones, sprints, and attached work items</p>
                </div>
            </div>

            <div class="min-w-[700px] flex flex-col items-center space-y-8 py-4">
                {{-- ROOT NODE: PROJECT --}}
                <div class="flex flex-col items-center">
                    <div class="px-6 py-3 rounded-2xl bg-gradient-to-r from-blue-600 via-indigo-600 to-purple-600 text-white shadow-md text-center">
                        <div class="text-[10px] font-extrabold uppercase tracking-wider text-blue-200">PROJECT ROOT</div>
                        <div class="text-sm font-black">{{ $project->name }}</div>
                    </div>
                    {{-- Vertical Stem Line --}}
                    <div class="w-0.5 h-8 bg-blue-300 dark:bg-blue-800"></div>
                </div>

                {{-- MILESTONE NODES (HORIZONTAL SPREAD) --}}
                <div class="w-full flex items-start justify-center gap-8 flex-wrap">
                    @foreach($allMilestones as $ms)
                        @php
                            $mPct = $ms->taskProgressPercent();
                        @endphp
                        <div class="flex-1 min-w-[260px] max-w-[340px] flex flex-col items-center">
                            {{-- Milestone Box --}}
                            <div class="w-full bg-white dark:bg-gray-800 rounded-2xl border-2 border-blue-200 dark:border-blue-800/80 p-4 shadow-sm space-y-2.5">
                                <div class="flex items-center justify-between">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-extrabold bg-blue-100 text-blue-700 font-mono">
                                        {{ $ms->code ?? ('MS-' . $ms->id) }}
                                    </span>
                                    <span class="text-xs font-black text-blue-600">{{ $mPct }}%</span>
                                </div>
                                <h5 class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $ms->title }}</h5>
                                <div class="w-full bg-gray-100 dark:bg-gray-700 rounded-full h-1.5 overflow-hidden">
                                    <div class="h-1.5 rounded-full bg-blue-600" style="width: {{ $mPct }}%"></div>
                                </div>
                            </div>

                            {{-- Stem to Children --}}
                            @if($ms->sprints->isNotEmpty() || $ms->standaloneTasks->isNotEmpty())
                                <div class="w-0.5 h-6 bg-indigo-200 dark:bg-indigo-800"></div>

                                {{-- Children Nodes (Sprints & Tasks) --}}
                                <div class="w-full space-y-2.5">
                                    {{-- Sprints --}}
                                    @foreach($ms->sprints as $sp)
                                        <div class="p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-200/80 dark:border-indigo-800/60 shadow-2xs space-y-2">
                                            <div class="flex items-center justify-between">
                                                <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-mono">{{ $sp->code ?? ('SP-' . $sp->id) }}</span>
                                                <span class="text-[10px] font-bold text-indigo-600 dark:text-indigo-400">{{ $sp->taskProgressPercent() }}%</span>
                                            </div>
                                            <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate">{{ $sp->name }}</p>

                                            {{-- Sprint Tasks List --}}
                                            @if($sp->tasks->isNotEmpty())
                                                <div class="pt-2 border-t border-indigo-100 dark:border-indigo-800/60 space-y-1.5">
                                                    <div class="flex items-center justify-between text-[10px] font-bold text-indigo-600 dark:text-indigo-400">
                                                        <span>Tasks ({{ $sp->tasks->count() }})</span>
                                                        <span class="text-[9px] text-gray-400 font-normal">Click to edit</span>
                                                    </div>
                                                    <div class="space-y-1 max-h-48 overflow-y-auto pr-0.5 custom-scrollbar">
                                                        @foreach($sp->tasks as $spt)
                                                            <div class="p-1.5 px-2 rounded-lg bg-white dark:bg-gray-800 border border-indigo-100/80 dark:border-indigo-800/60 shadow-2xs flex items-center justify-between gap-1.5 cursor-pointer hover:border-indigo-400 hover:shadow-xs transition group"
                                                                @click.stop="openTaskModal({{ $spt->id }})"
                                                                title="Click to view/edit {{ $spt->code ?? ('TSK-' . $spt->id) }}">
                                                                <div class="flex items-center gap-1.5 min-w-0">
                                                                    @if($spt->assignee)
                                                                        @if($spt->assignee->avatar)
                                                                            <img src="{{ Storage::url($spt->assignee->avatar) }}" alt="{{ $spt->assignee->name }}" class="w-4 h-4 rounded-full object-cover shrink-0">
                                                                        @else
                                                                            <div class="w-4 h-4 rounded-full text-white flex items-center justify-center text-[8px] font-bold shrink-0 shadow-2xs"
                                                                                style="background-color: {{ $spt->assignee->avatarColor() }};"
                                                                                title="{{ $spt->assignee->name }}">
                                                                                {{ $spt->assignee->initials() }}
                                                                            </div>
                                                                        @endif
                                                                    @else
                                                                        <div class="w-4 h-4 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-400 flex items-center justify-center text-[8px] shrink-0">
                                                                            -
                                                                        </div>
                                                                    @endif
                                                                    <div class="min-w-0">
                                                                        <p class="text-[11px] font-medium text-gray-800 dark:text-gray-200 truncate group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition">
                                                                            {{ $spt->title }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <span class="shrink-0 text-[8px] px-1.5 py-0.5 rounded font-bold {{ $spt->status === 'done' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300' }}">
                                                                    {{ $spt->status === 'done' ? 'Done' : 'Open' }}
                                                                </span>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @else
                                                <p class="text-[10px] text-gray-400 italic">No tasks in this sprint</p>
                                            @endif
                                        </div>
                                    @endforeach

                                    {{-- Standalone Tasks --}}
                                    @foreach($ms->standaloneTasks as $st)
                                        <div class="p-2.5 rounded-xl bg-emerald-50/70 dark:bg-emerald-950/40 border border-emerald-200/80 dark:border-emerald-800/60 shadow-2xs flex items-center justify-between cursor-pointer hover:border-emerald-400 transition group"
                                            @click="openTaskModal({{ $st->id }})"
                                            title="Click to view/edit {{ $st->code ?? ('TSK-' . $st->id) }}">
                                            <div class="flex items-center gap-2 min-w-0">
                                                @if($st->assignee)
                                                    @if($st->assignee->avatar)
                                                        <img src="{{ Storage::url($st->assignee->avatar) }}" alt="{{ $st->assignee->name }}" class="w-4 h-4 rounded-full object-cover shrink-0">
                                                    @else
                                                        <div class="w-4 h-4 rounded-full text-white flex items-center justify-center text-[9px] font-bold shrink-0 shadow-2xs"
                                                            style="background-color: {{ $st->assignee->avatarColor() }};"
                                                            title="{{ $st->assignee->name }}">
                                                            {{ $st->assignee->initials() }}
                                                        </div>
                                                    @endif
                                                @endif
                                                <div class="min-w-0">
                                                    <p class="text-xs font-semibold text-gray-800 dark:text-gray-200 truncate group-hover:text-emerald-600 dark:group-hover:text-emerald-400 transition">{{ $st->title }}</p>
                                                    <span class="text-[9px] text-emerald-600 dark:text-emerald-400 font-bold">Direct Task</span>
                                                </div>
                                            </div>
                                            <span class="shrink-0 text-[9px] px-1.5 py-0.5 rounded font-bold {{ $st->status === 'done' ? 'bg-emerald-200 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                                {{ $st->status === 'done' ? 'Done' : 'Open' }}
                                            </span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

    @endif

    {{-- ============================================================
         7. MODAL INCLUDES
         ============================================================ --}}
    @include('projects.milestones._create_edit_modal')
    @include('projects.milestones._assign_modal')
    @include('projects.milestones._delete_modal')

</div>

{{-- Alpine.js Component Script --}}
<script>
function milestonesDashboardData() {
    return {
        activeMilestoneView: 'card_roadmap',
        searchQuery: '',
        filterStatus: '',
        filterPriority: '',
        openHierarchyIds: [],

        // Create / Edit Modal State
        showMilestoneModal: false,
        milestoneModalMode: 'create',
        milestoneEditActionUrl: '',
        milestoneForm: {
            code: '',
            title: '',
            description: '',
            start_date: '',
            due_date: '',
            assigned_to: '',
            priority: 'low',
            release_target: '',
            status: 'pending',
        },
        isSubmittingMilestone: false,
        isSubmittingAssign: false,

        // Delete Modal State (Task-style)
        deleteConfirmOpen: false,
        deleteConfirmChecked: false,
        deleteMilestoneUrl: '',
        deleteMilestoneTitle: '',
        isDeletingMilestone: false,

        // Assign Sprints & Tasks Modal State
        showMilestoneAssignModal: false,
        assignMilestoneId: null,
        assignMilestoneCode: '',
        assignMilestoneTitle: '',
        assignMilestoneActionUrl: '',
        assignSearchQuery: '',
        selectedSprintIds: [],
        selectedTaskIds: [],

        initMilestonesDashboard() {
            // Expand first milestone by default in table view
            @if($allMilestones->isNotEmpty())
                this.openHierarchyIds.push({{ $allMilestones->first()->id }});
            @endif
        },

        matchesFilter(titleAndCode, status, priority) {
            if (this.searchQuery && !titleAndCode.includes(this.searchQuery.toLowerCase())) return false;
            if (this.filterStatus && status !== this.filterStatus) return false;
            if (this.filterPriority && priority !== this.filterPriority) return false;
            return true;
        },

        // Hierarchy Table methods
        toggleHierarchy(id) {
            const idx = this.openHierarchyIds.indexOf(id);
            if (idx > -1) {
                this.openHierarchyIds.splice(idx, 1);
            } else {
                this.openHierarchyIds.push(id);
            }
        },
        isHierarchyOpen(id) {
            return this.openHierarchyIds.includes(id);
        },
        expandAllHierarchy() {
            this.openHierarchyIds = [
                @foreach($allMilestones as $m)
                    {{ $m->id }},
                @endforeach
            ];
        },
        collapseAllHierarchy() {
            this.openHierarchyIds = [];
        },

        // Create / Edit Modal methods
        openCreateMilestoneModal() {
            this.milestoneModalMode = 'create';
            this.milestoneForm = {
                code: 'MS-' + String({{ $allMilestones->count() + 1 }}).padStart(2, '0'),
                title: '',
                description: '',
                start_date: '',
                due_date: '',
                assigned_to: '',
                priority: 'low',
                release_target: '',
                status: 'pending',
            };
            this.showMilestoneModal = true;
        },

        openEditMilestoneModal(ms) {
            this.milestoneModalMode = 'edit';
            this.milestoneEditActionUrl = '{{ url('projects/' . $project->id . '/milestones') }}/' + ms.id;
            this.milestoneForm = {
                code: ms.code || ('MS-' + ms.id),
                title: ms.title || '',
                description: ms.description || '',
                start_date: ms.start_date ? ms.start_date.substring(0, 10) : '',
                due_date: ms.due_date ? ms.due_date.substring(0, 10) : '',
                assigned_to: ms.assigned_to || '',
                priority: ms.priority || 'low',
                release_target: ms.release_target || '',
                status: ms.status || 'planning',
            };
            this.showMilestoneModal = true;
        },

        closeMilestoneModal() {
            this.showMilestoneModal = false;
        },

        // Assign Modal methods
        openAssignMilestoneModal(ms, sprintIds, taskIds) {
            this.assignMilestoneId = ms.id;
            this.assignMilestoneCode = ms.code || ('MS-' + ms.id);
            this.assignMilestoneTitle = ms.title;
            this.assignMilestoneActionUrl = '{{ url('projects/' . $project->id . '/milestones') }}/' + ms.id + '/assign';
            this.assignSearchQuery = '';
            this.selectedSprintIds = (sprintIds ? [...sprintIds] : []).map(Number);
            this.selectedTaskIds = (taskIds ? [...taskIds] : []).map(Number);
            this.showMilestoneAssignModal = true;
        },

        closeMilestoneAssignModal() {
            this.showMilestoneAssignModal = false;
        },

        toggleSprintSelection(id) {
            const numId = Number(id);
            const idx = this.selectedSprintIds.indexOf(numId);
            if (idx > -1) {
                this.selectedSprintIds.splice(idx, 1);
            } else {
                this.selectedSprintIds.push(numId);
            }
        },

        toggleTaskSelection(id) {
            const numId = Number(id);
            const idx = this.selectedTaskIds.indexOf(numId);
            if (idx > -1) {
                this.selectedTaskIds.splice(idx, 1);
            } else {
                this.selectedTaskIds.push(numId);
            }
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

        async submitMilestoneForm(event) {
            if (this.isSubmittingMilestone) return;
            this.isSubmittingMilestone = true;

            const form = event.target;
            const url = this.milestoneModalMode === 'edit' ? this.milestoneEditActionUrl : form.action;
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
                    this.closeMilestoneModal();
                    if (window.showToast) {
                        window.showToast('Changes saved successfully');
                    }
                    await this.refreshMilestonesDOM();
                } else {
                    let errorMsg = (data && data.message) ? data.message : 'Gagal menyimpan milestone.';
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
                console.error('Milestone submission error:', err);
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
                this.isSubmittingMilestone = false;
            }
        },

        async submitAssignForm(event) {
            if (this.isSubmittingAssign) return;
            this.isSubmittingAssign = true;

            const form = event.target;
            const url = this.assignMilestoneActionUrl || form.action;
            const formData = new FormData();
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || form.querySelector('input[name="_token"]')?.value
                || '{{ csrf_token() }}';
            formData.append('_token', csrfToken);
            (this.selectedSprintIds || []).forEach(id => {
                formData.append('sprint_ids[]', id);
            });
            (this.selectedTaskIds || []).forEach(id => {
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
                    this.closeMilestoneAssignModal();
                    if (window.showToast) {
                        window.showToast('Changes saved successfully');
                    }

                    // Synchronize DOM elements on Tasks tab (Kanban cards and list rows)
                    const targetMsId = this.assignMilestoneId ? this.assignMilestoneId.toString() : '';
                    const selectedTaskIds = (this.selectedTaskIds || []).map(id => id.toString());
                    const selectedSprintIds = (this.selectedSprintIds || []).map(id => id.toString());

                    // 1. Reset milestoneId on tasks previously in this milestone that were unselected
                    document.querySelectorAll(`.kanban-card[data-milestone-id="${targetMsId}"], .list-task-row[data-milestone-id="${targetMsId}"], tr[data-milestone-id="${targetMsId}"]`).forEach(el => {
                        const isInSelectedTasks = selectedTaskIds.includes(el.dataset.taskId);
                        const isInSelectedSprints = el.dataset.sprintId && selectedSprintIds.includes(el.dataset.sprintId);
                        if (!isInSelectedTasks && !isInSelectedSprints) {
                            el.dataset.milestoneId = '';
                        }
                    });

                    // 2. Set milestoneId on all newly selected standalone tasks
                    selectedTaskIds.forEach(id => {
                        document.querySelectorAll(`.kanban-card[data-task-id="${id}"], .list-task-row[data-task-id="${id}"], tr[data-task-id="${id}"]`).forEach(el => {
                            el.dataset.milestoneId = targetMsId;
                        });
                    });

                    // 3. Set milestoneId on all tasks that belong to selected sprints
                    selectedSprintIds.forEach(spId => {
                        document.querySelectorAll(`.kanban-card[data-sprint-id="${spId}"], .list-task-row[data-sprint-id="${spId}"], tr[data-sprint-id="${spId}"]`).forEach(el => {
                            el.dataset.milestoneId = targetMsId;
                        });
                    });

                    // 4. Re-apply task filters if active
                    const alpineEl = document.querySelector('[x-data*="projectPageData"]');
                    if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                        alpineEl._x_dataStack[0].applyFilters();
                    }

                    await this.refreshMilestonesDOM();
                } else {
                    let errorMsg = (data && data.message) ? data.message : 'Gagal menyimpan assignment.';
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
                console.error('Assign submission error:', err);
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
                this.isSubmittingAssign = false;
            }
        },

        promptDeleteMilestone(url, title) {
            this.deleteMilestoneUrl = url;
            this.deleteMilestoneTitle = title;
            this.deleteConfirmChecked = false;
            this.deleteConfirmOpen = true;
        },

        async confirmDeleteMilestone() {
            if (!this.deleteConfirmChecked || this.isDeletingMilestone) return;
            this.isDeletingMilestone = true;

            try {
                const res = await fetch(this.deleteMilestoneUrl, {
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
                    await this.refreshMilestonesDOM();
                } else {
                    let errorMsg = (data && data.message) ? data.message : 'Gagal menghapus milestone.';
                    if (window.showToast) {
                        window.showToast(errorMsg, 'error');
                    } else if (window.Swal) {
                        Swal.fire({ icon: 'error', title: 'Error', text: errorMsg });
                    } else {
                        alert(errorMsg);
                    }
                }
            } catch (err) {
                console.error('Delete milestone error:', err);
                if (window.showToast) {
                    window.showToast('A system error occurred', 'error');
                } else if (window.Swal) {
                    Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan jaringan.' });
                }
            } finally {
                this.isDeletingMilestone = false;
            }
        },

        async refreshMilestonesDOM() {
            try {
                const res = await fetch(window.location.href);
                const html = await res.text();
                const parser = new DOMParser();
                const doc = parser.parseFromString(html, 'text/html');

                const newContainer = doc.querySelector('#milestones-dashboard-root');
                const currentContainer = document.querySelector('#milestones-dashboard-root');

                if (newContainer && currentContainer) {
                    if (window.Alpine && typeof window.Alpine.destroyTree === 'function') {
                        window.Alpine.destroyTree(currentContainer);
                    }
                    currentContainer.outerHTML = newContainer.outerHTML;
                    const refreshed = document.querySelector('#milestones-dashboard-root');
                    if (refreshed && window.Alpine && typeof window.Alpine.initTree === 'function') {
                        window.Alpine.initTree(refreshed);
                    }
                }
            } catch (e) {
                console.error('Failed to refresh milestones DOM:', e);
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
