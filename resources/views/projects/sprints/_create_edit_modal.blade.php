{{-- Modal Create / Edit Sprint --}}
<div x-show="showSprintModal" x-cloak class="relative z-[9999]">
    {{-- Standalone Fullscreen Backdrop --}}
    <div x-show="showSprintModal" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm z-[9998]"
        @click="closeSprintModal()"></div>

    {{-- Modal Wrapper --}}
    <div x-show="showSprintModal" x-cloak
        class="fixed inset-0 z-[9999] overflow-y-auto p-4 sm:p-6 flex items-center justify-center pointer-events-none"
        @keydown.escape.window="closeSprintModal()">

        <div class="relative w-full max-w-2xl bg-white dark:bg-gray-850 rounded-2xl shadow-2xl border border-gray-200/90 dark:border-gray-700/80 overflow-hidden transform transition-all my-8 pointer-events-auto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Modal Header --}}
        <div
            class="flex items-center justify-between px-6 py-4.5 border-b border-gray-100 dark:border-gray-750 bg-white dark:bg-gray-800/40">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/60 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white"
                        x-text="sprintModalMode === 'edit' ? 'Edit Sprint' : 'Create New Sprint'"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"
                        x-text="sprintModalMode === 'edit' ? 'Update sprint parameters and timeline' : 'Plan an iteration cycle with target deliverables'">
                    </p>
                </div>
            </div>
            <button type="button" @click="closeSprintModal()"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form :action="sprintModalMode === 'edit' ? sprintEditActionUrl : '{{ route('sprints.store', $project) }}'"
            method="POST"
            @submit.prevent="submitSprintForm($event)">
            @csrf
            <template x-if="sprintModalMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>

            {{-- Hidden Inputs for Custom Dropdowns --}}
            <input type="hidden" name="milestone_id" :value="sprintForm.milestone_id">
            <input type="hidden" name="assigned_to" :value="sprintForm.assigned_to">
            <input type="hidden" name="priority" :value="sprintForm.priority">
            <input type="hidden" name="status" :value="sprintForm.status">

            <div class="p-6 space-y-4.5 max-h-[75vh] overflow-y-auto">
                {{-- Code & Name --}}
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Sprint ID
                        </label>
                        <input type="text" name="code" x-model="sprintForm.code" placeholder="SP-01"
                            class="w-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200 placeholder-slate-400">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Sprint Name <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text" name="name" x-model="sprintForm.name" required
                            placeholder="e.g. Sprint 1 — User Authentication & Onboarding"
                            class="w-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-900 dark:text-white placeholder-slate-400">
                    </div>
                </div>

                {{-- Sprint Goal --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                        Sprint Goal
                    </label>
                    <input type="text" name="goal" x-model="sprintForm.goal"
                        placeholder="e.g. Deliver functional login, signup, and user role permission base"
                        class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200 placeholder-slate-400">
                </div>

                {{-- Priority & Status (Custom Popovers) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    {{-- Custom Popover 3: Priority (Exact Task Priority Match) --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Priority
                        </label>
                        <div class="relative" x-data="{ openSprintPriority: false }">
                            <button type="button" @click="openSprintPriority = !openSprintPriority"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border rounded-xl text-xs font-medium hover:border-slate-300 transition shadow-2xs"
                                :class="{
                                    'border-red-300 bg-red-50/30 dark:border-red-800': sprintForm.priority === 'urgent',
                                    'border-amber-300 bg-amber-50/30 dark:border-amber-800': sprintForm.priority === 'high',
                                    'border-slate-200 dark:border-slate-700': sprintForm.priority !== 'urgent' && sprintForm.priority !== 'high'
                                }">
                                <span class="flex items-center gap-2.5">
                                    {{-- Urgent Icon --}}
                                    <span x-show="sprintForm.priority === 'urgent'" class="shrink-0">
                                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                                        </svg>
                                    </span>
                                    {{-- High Icon --}}
                                    <span x-show="sprintForm.priority === 'high'" class="shrink-0">
                                        <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                    </span>
                                    {{-- Medium / Normal Icon --}}
                                    <span x-show="sprintForm.priority === 'normal' || sprintForm.priority === 'medium'"
                                        class="shrink-0">
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M4 9h16M4 15h16" />
                                        </svg>
                                    </span>
                                    {{-- Low Icon --}}
                                    <span x-show="sprintForm.priority === 'low'" class="shrink-0">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                    </span>

                                    <span class="font-medium" :class="{
                                            'text-red-600 dark:text-red-400 font-semibold': sprintForm.priority === 'urgent',
                                            'text-amber-700 dark:text-amber-400 font-semibold': sprintForm.priority === 'high',
                                            'text-slate-800 dark:text-slate-200': sprintForm.priority !== 'urgent' && sprintForm.priority !== 'high'
                                        }" x-text="{
                                            'urgent': 'Urgent',
                                            'high': 'High',
                                            'normal': 'Normal',
                                            'medium': 'Normal',
                                            'low': 'Low'
                                        }[sprintForm.priority] || 'Normal'"></span>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Priority Popover Panel --}}
                            <div x-show="openSprintPriority" @click.outside="openSprintPriority = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    TASK PRIORITY
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5">
                                    {{-- Urgent --}}
                                    <button type="button"
                                        @click="sprintForm.priority = 'urgent'; openSprintPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="sprintForm.priority === 'urgent' ? 'bg-red-50/80 dark:bg-red-950/40 text-red-700 dark:text-red-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                                            </svg>
                                            <span>Urgent</span>
                                            <span
                                                class="px-1.5 py-0.5 bg-red-100 text-red-700 border border-red-200 text-[9px] font-bold rounded uppercase tracking-wider">CRITICAL</span>
                                        </span>
                                        <svg x-show="sprintForm.priority === 'urgent'" class="w-4 h-4 text-red-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    {{-- High --}}
                                    <button type="button"
                                        @click="sprintForm.priority = 'high'; openSprintPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="sprintForm.priority === 'high' ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            <span>High</span>
                                        </span>
                                        <svg x-show="sprintForm.priority === 'high'" class="w-4 h-4 text-amber-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    {{-- Normal --}}
                                    <button type="button"
                                        @click="sprintForm.priority = 'normal'; openSprintPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="(sprintForm.priority === 'normal' || sprintForm.priority === 'medium') ? 'bg-slate-100 dark:bg-slate-700 font-semibold text-slate-800 dark:text-slate-100' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M4 9h16M4 15h16" />
                                            </svg>
                                            <span>Normal</span>
                                        </span>
                                        <svg x-show="sprintForm.priority === 'normal' || sprintForm.priority === 'medium'"
                                            class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    {{-- Low --}}
                                    <button type="button"
                                        @click="sprintForm.priority = 'low'; openSprintPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="sprintForm.priority === 'low' ? 'bg-emerald-50/80 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            <span>Low</span>
                                        </span>
                                        <svg x-show="sprintForm.priority === 'low'" class="w-4 h-4 text-emerald-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Popover 4: Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Status
                        </label>
                        <div class="relative" x-data="{ openSprintStatus: false }">
                            <button type="button" @click="openSprintStatus = !openSprintStatus"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                                <span class="flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="{
                                            'bg-slate-400': sprintForm.status === 'planning',
                                            'bg-blue-500': sprintForm.status === 'active',
                                            'bg-emerald-500': sprintForm.status === 'completed',
                                            'bg-rose-500': sprintForm.status === 'at_risk',
                                            'bg-slate-500': sprintForm.status === 'not_started'
                                        }"></span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="{
                                            'planning': 'Planning',
                                            'active': 'Active (In Progress)',
                                            'completed': 'Completed',
                                            'at_risk': 'At Risk / Delayed',
                                            'not_started': 'Not Started'
                                        }[sprintForm.status] || 'Planning'"></span>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Status Popover Panel --}}
                            <div x-show="openSprintStatus" @click.outside="openSprintStatus = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    SPRINT STATUS
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5">
                                    @php
                                        $sprintStatuses = [
                                            ['value' => 'planning', 'label' => 'Planning', 'dot' => 'bg-slate-400'],
                                            ['value' => 'active', 'label' => 'Active (In Progress)', 'dot' => 'bg-blue-500'],
                                            ['value' => 'completed', 'label' => 'Completed', 'dot' => 'bg-emerald-500'],
                                            ['value' => 'at_risk', 'label' => 'At Risk / Delayed', 'dot' => 'bg-rose-500'],
                                            ['value' => 'not_started', 'label' => 'Not Started', 'dot' => 'bg-slate-500'],
                                        ];
                                    @endphp
                                    @foreach($sprintStatuses as $st)
                                        <button type="button"
                                            @click="sprintForm.status = '{{ $st['value'] }}'; openSprintStatus = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                            :class="sprintForm.status === '{{ $st['value'] }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                            <span class="flex items-center gap-2.5">
                                                <span class="w-2.5 h-2.5 rounded-full {{ $st['dot'] }} shrink-0"></span>
                                                <span>{{ $st['label'] }}</span>
                                            </span>
                                            <svg x-show="sprintForm.status === '{{ $st['value'] }}'"
                                                class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Parent Milestone & Sprint Lead (Custom Popovers) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    {{-- Custom Popover 1: Parent Milestone --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Parent Milestone (Optional)
                        </label>
                        <div class="relative" x-data="{ openMilestone: false }">
                            <button type="button" @click="openMilestone = !openMilestone"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                                <span class="flex items-center gap-2 min-w-0 truncate">
                                    <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                                    </svg>
                                    @foreach($project->milestones as $m)
                                        <template x-if="sprintForm.milestone_id == '{{ $m->id }}'">
                                            <span class="truncate flex items-center gap-1.5">
                                                <span
                                                    class="font-extrabold text-blue-600 dark:text-blue-400 font-mono">[{{ $m->code ?? ('MS-' . $m->id) }}]</span>
                                                <span
                                                    class="truncate text-slate-800 dark:text-slate-200 font-medium">{{ $m->title }}</span>
                                            </span>
                                        </template>
                                    @endforeach
                                    <template x-if="!sprintForm.milestone_id">
                                        <span class="text-slate-400 truncate">— No Milestone (Independent) —</span>
                                    </template>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 ml-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Parent Milestone Popover Panel --}}
                            <div x-show="openMilestone" @click.outside="openMilestone = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    PARENT MILESTONE
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5 max-h-52 overflow-y-auto">
                                    {{-- Option: No Milestone --}}
                                    <button type="button" @click="sprintForm.milestone_id = ''; openMilestone = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="!sprintForm.milestone_id ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="text-slate-500 dark:text-slate-400">— No Milestone (Independent
                                            Sprint) —</span>
                                        <svg x-show="!sprintForm.milestone_id" class="w-4 h-4 text-blue-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    @foreach($project->milestones as $m)
                                        <button type="button"
                                            @click="sprintForm.milestone_id = '{{ $m->id }}'; openMilestone = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                            :class="sprintForm.milestone_id == '{{ $m->id }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span
                                                    class="px-1.5 py-0.5 rounded bg-blue-50 dark:bg-blue-900/40 text-blue-600 dark:text-blue-300 font-mono text-[10px] font-bold shrink-0">
                                                    {{ $m->code ?? ('MS-' . $m->id) }}
                                                </span>
                                                <span class="truncate">{{ $m->title }}</span>
                                            </span>
                                            <svg x-show="sprintForm.milestone_id == '{{ $m->id }}'"
                                                class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Custom Popover 2: Sprint Lead --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Sprint Lead / PIC
                        </label>
                        <div class="relative" x-data="{ openLead: false }">
                            <button type="button" @click="openLead = !openLead"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                                <span class="flex items-center gap-2 min-w-0 truncate">
                                    @foreach($assignableUsers as $u)
                                        <template x-if="sprintForm.assigned_to == '{{ $u->id }}'">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span
                                                    class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center font-bold shrink-0">
                                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                                </span>
                                                <span
                                                    class="truncate text-slate-800 dark:text-slate-200 font-medium">{{ $u->name }}</span>
                                            </span>
                                        </template>
                                    @endforeach
                                    <template x-if="!sprintForm.assigned_to">
                                        <span class="flex items-center gap-2 text-slate-400">
                                            <span
                                                class="w-5 h-5 rounded-full border border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-[10px]">?</span>
                                            <span>— Select Sprint Lead —</span>
                                        </span>
                                    </template>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 ml-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Lead Popover Panel --}}
                            <div x-show="openLead" @click.outside="openLead = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    SELECT SPRINT LEAD
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5 max-h-52 overflow-y-auto">
                                    {{-- Option: Unassigned --}}
                                    <button type="button" @click="sprintForm.assigned_to = ''; openLead = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="!sprintForm.assigned_to ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2">
                                            <span
                                                class="w-5 h-5 rounded-full border border-dashed border-slate-300 flex items-center justify-center text-[10px] text-slate-400">?</span>
                                            <span>— Unassigned —</span>
                                        </span>
                                        <svg x-show="!sprintForm.assigned_to" class="w-4 h-4 text-blue-600" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    @foreach($assignableUsers as $u)
                                        <button type="button"
                                            @click="sprintForm.assigned_to = '{{ $u->id }}'; openLead = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                            :class="sprintForm.assigned_to == '{{ $u->id }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span
                                                    class="w-5 h-5 rounded-full bg-indigo-600 text-white text-[10px] flex items-center justify-center font-bold shrink-0">
                                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                                </span>
                                                <span class="truncate">{{ $u->name }}</span>
                                            </span>
                                            <svg x-show="sprintForm.assigned_to == '{{ $u->id }}'"
                                                class="w-4 h-4 text-blue-600 shrink-0" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Start Date & End Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Start Date
                        </label>
                        <input type="date" name="start_date" x-model="sprintForm.start_date"
                            class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            End Date
                        </label>
                        <input type="date" name="end_date" x-model="sprintForm.end_date"
                            class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200">
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div
                class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-750 bg-white dark:bg-gray-800/40">
                <button type="button" @click="closeSprintModal()"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-650 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition shadow-2xs">
                    Cancel
                </button>
                <button type="submit"
                    :disabled="isSubmittingSprint"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">
                    <template x-if="isSubmittingSprint">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </template>
                    <template x-if="!isSubmittingSprint">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <span x-text="isSubmittingSprint ? 'Saving...' : (sprintModalMode === 'edit' ? 'Save Changes' : 'Create Sprint')"></span>
                </button>
            </div>
        </form>
    </div>
</div>
</div>