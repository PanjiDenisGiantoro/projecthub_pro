{{-- Modal Create / Edit Milestone --}}
<div x-show="showMilestoneModal" x-cloak class="relative z-[9999]">
    {{-- Standalone Fullscreen Backdrop --}}
    <div x-show="showMilestoneModal" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm z-[9998]"
        @click="closeMilestoneModal()"></div>

    {{-- Modal Wrapper --}}
    <div x-show="showMilestoneModal" x-cloak
        class="fixed inset-0 z-[9999] overflow-y-auto p-4 sm:p-6 flex items-center justify-center pointer-events-none"
        @keydown.escape.window="closeMilestoneModal()">

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
                    class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 dark:border-blue-800/60 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 21v-4m0 0V5a2 2 0 012-2h6.5l1 1H21l-3 6 3 6H9.5l-1-1H5a2 2 0 00-2 2zm9-13.5V9" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white"
                        x-text="milestoneModalMode === 'edit' ? 'Edit Milestone' : 'Create New Milestone'"></h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400"
                        x-text="milestoneModalMode === 'edit' ? 'Update milestone target and timeline' : 'Define a major goal or delivery phase for this project'">
                    </p>
                </div>
            </div>
            <button type="button" @click="closeMilestoneModal()"
                class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form
            :action="milestoneModalMode === 'edit' ? milestoneEditActionUrl : '{{ route('milestones.store', $project) }}'"
            method="POST"
            @submit.prevent="submitMilestoneForm($event)">
            @csrf
            <template x-if="milestoneModalMode === 'edit'">
                <input type="hidden" name="_method" value="PUT">
            </template>

            {{-- Hidden Inputs for Custom Dropdowns --}}
            <input type="hidden" name="assigned_to" :value="milestoneForm.assigned_to">
            <input type="hidden" name="priority" :value="milestoneForm.priority">
            <input type="hidden" name="status" :value="milestoneForm.status">

            <div class="p-6 space-y-4.5 max-h-[75vh] overflow-y-auto">
                {{-- ID & Title --}}
                <div class="grid grid-cols-1 sm:grid-cols-4 gap-3.5">
                    <div class="sm:col-span-1">
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Milestone ID
                        </label>
                        <input type="text" name="code" x-model="milestoneForm.code" placeholder="MS-01"
                            class="w-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200 placeholder-slate-400">
                    </div>
                    <div class="sm:col-span-3">
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Milestone Title <span class="text-rose-500 font-bold">*</span>
                        </label>
                        <input type="text" name="title" x-model="milestoneForm.title" required
                            placeholder="e.g. Go-Live Beta v1.0, Core MVP Release"
                            class="w-full text-xs font-semibold bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-900 dark:text-white placeholder-slate-400">
                    </div>
                </div>

                {{-- Start Date & Due Date --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Start Date
                        </label>
                        <div class="relative">
                            <input type="date" name="start_date" x-model="milestoneForm.start_date"
                                class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200">
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Due Date / Target End
                        </label>
                        <div class="relative">
                            <input type="date" name="due_date" x-model="milestoneForm.due_date"
                                class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200">
                        </div>
                    </div>
                </div>

                {{-- Assignee (PIC/Lead) & Priority (Custom Popovers) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    {{-- Custom Popover 1: Assignee --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Assignee (PIC / Lead)
                        </label>
                        <div class="relative" x-data="{ openAssignee: false }">
                            <button type="button" @click="openAssignee = !openAssignee"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                                <span class="flex items-center gap-2 min-w-0 truncate">
                                    @foreach($assignableUsers as $u)
                                        <template x-if="milestoneForm.assigned_to == '{{ $u->id }}'">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span
                                                    class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-bold shrink-0">
                                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                                </span>
                                                <span
                                                    class="truncate text-slate-800 dark:text-slate-200 font-medium">{{ $u->name }}</span>
                                            </span>
                                        </template>
                                    @endforeach
                                    <template x-if="!milestoneForm.assigned_to">
                                        <span class="flex items-center gap-2 text-slate-400">
                                            <span
                                                class="w-5 h-5 rounded-full border border-dashed border-slate-300 dark:border-slate-600 flex items-center justify-center text-[10px]">?</span>
                                            <span>— Select Assignee / PIC —</span>
                                        </span>
                                    </template>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0 ml-2" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Assignee Popover Panel --}}
                            <div x-show="openAssignee" @click.outside="openAssignee = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    SELECT ASSIGNEE / PIC
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5 max-h-52 overflow-y-auto">
                                    {{-- Option: Unassigned --}}
                                    <button type="button" @click="milestoneForm.assigned_to = ''; openAssignee = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="!milestoneForm.assigned_to ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2">
                                            <span
                                                class="w-5 h-5 rounded-full border border-dashed border-slate-300 flex items-center justify-center text-[10px] text-slate-400">?</span>
                                            <span>— Unassigned —</span>
                                        </span>
                                        <svg x-show="!milestoneForm.assigned_to" class="w-4 h-4 text-blue-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    @foreach($assignableUsers as $u)
                                        <button type="button"
                                            @click="milestoneForm.assigned_to = '{{ $u->id }}'; openAssignee = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                            :class="milestoneForm.assigned_to == '{{ $u->id }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                            <span class="flex items-center gap-2 min-w-0">
                                                <span
                                                    class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-bold shrink-0">
                                                    {{ strtoupper(substr($u->name, 0, 2)) }}
                                                </span>
                                                <span class="truncate">{{ $u->name }}</span>
                                            </span>
                                            <svg x-show="milestoneForm.assigned_to == '{{ $u->id }}'"
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

                    {{-- Custom Popover 2: Priority (Exact Task Priority Match) --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Priority
                        </label>
                        <div class="relative" x-data="{ openPriority: false }">
                            <button type="button" @click="openPriority = !openPriority"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border rounded-xl text-xs font-medium hover:border-slate-300 transition shadow-2xs"
                                :class="{
                                    'border-red-300 bg-red-50/30 dark:border-red-800': milestoneForm.priority === 'urgent',
                                    'border-amber-300 bg-amber-50/30 dark:border-amber-800': milestoneForm.priority === 'high',
                                    'border-slate-200 dark:border-slate-700': milestoneForm.priority !== 'urgent' && milestoneForm.priority !== 'high'
                                }">
                                <span class="flex items-center gap-2.5">
                                    {{-- Urgent Icon --}}
                                    <span x-show="milestoneForm.priority === 'urgent'" class="shrink-0">
                                        <svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 11l7-7 7 7M5 19l7-7 7 7" />
                                        </svg>
                                    </span>
                                    {{-- High Icon --}}
                                    <span x-show="milestoneForm.priority === 'high'" class="shrink-0">
                                        <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                        </svg>
                                    </span>
                                    {{-- Medium Icon --}}
                                    <span x-show="milestoneForm.priority === 'medium'" class="shrink-0">
                                        <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M4 9h16M4 15h16" />
                                        </svg>
                                    </span>
                                    {{-- Low Icon --}}
                                    <span x-show="milestoneForm.priority === 'low'" class="shrink-0">
                                        <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                        </svg>
                                    </span>

                                    <span class="font-medium" :class="{
                                            'text-red-600 dark:text-red-400 font-semibold': milestoneForm.priority === 'urgent',
                                            'text-amber-700 dark:text-amber-400 font-semibold': milestoneForm.priority === 'high',
                                            'text-slate-800 dark:text-slate-200': milestoneForm.priority !== 'urgent' && milestoneForm.priority !== 'high'
                                        }" x-text="{
                                            'urgent': 'Urgent',
                                            'high': 'High',
                                            'medium': 'Medium',
                                            'low': 'Low'
                                        }[milestoneForm.priority] || 'Low'"></span>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Priority Popover Panel --}}
                            <div x-show="openPriority" @click.outside="openPriority = false" x-cloak
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
                                        @click="milestoneForm.priority = 'urgent'; openPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="milestoneForm.priority === 'urgent' ? 'bg-red-50/80 dark:bg-red-950/40 text-red-700 dark:text-red-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
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
                                        <svg x-show="milestoneForm.priority === 'urgent'" class="w-4 h-4 text-red-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    {{-- High --}}
                                    <button type="button" @click="milestoneForm.priority = 'high'; openPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="milestoneForm.priority === 'high' ? 'bg-amber-50/80 dark:bg-amber-950/40 text-amber-800 dark:text-amber-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-orange-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M5 10l7-7m0 0l7 7m-7-7v18" />
                                            </svg>
                                            <span>High</span>
                                        </span>
                                        <svg x-show="milestoneForm.priority === 'high'" class="w-4 h-4 text-amber-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    {{-- Medium --}}
                                    <button type="button"
                                        @click="milestoneForm.priority = 'medium'; openPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="milestoneForm.priority === 'medium' ? 'bg-slate-100 dark:bg-slate-700 font-semibold text-slate-800 dark:text-slate-100' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M4 9h16M4 15h16" />
                                            </svg>
                                            <span>Medium</span>
                                        </span>
                                        <svg x-show="milestoneForm.priority === 'medium'" class="w-4 h-4 text-blue-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>

                                    {{-- Low --}}
                                    <button type="button" @click="milestoneForm.priority = 'low'; openPriority = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="milestoneForm.priority === 'low' ? 'bg-emerald-50/80 dark:bg-emerald-950/40 text-emerald-800 dark:text-emerald-300 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                    d="M19 14l-7 7m0 0l-7-7m7 7V3" />
                                            </svg>
                                            <span>Low</span>
                                        </span>
                                        <svg x-show="milestoneForm.priority === 'low'" class="w-4 h-4 text-emerald-600"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Release Target & Status --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                    {{-- Release Target Combobox Popover --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Release Target
                        </label>
                        <div class="relative" x-data="{ openRelease: false }">
                            <div class="relative flex items-center">
                                <input type="text" name="release_target" x-model="milestoneForm.release_target"
                                    placeholder="e.g. v1.0.0 (MVP Release)"
                                    class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl pl-3.5 pr-9 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200 placeholder-slate-400">
                                <button type="button" @click="openRelease = !openRelease"
                                    class="absolute right-2 p-1 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 rounded-lg transition"
                                    title="Choose preset release target">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 9l-7 7-7-7" />
                                    </svg>
                                </button>
                            </div>

                            {{-- Release Target Presets Popover --}}
                            <div x-show="openRelease" @click.outside="openRelease = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    SUGGESTED RELEASE TARGETS
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5 max-h-48 overflow-y-auto">
                                    @php
                                        $releasePresets = [
                                            'v1.0.0 (MVP Release)',
                                            'v1.1.0 (Feature Update)',
                                            'v2.0.0 (Major Release)',
                                            'Beta Release',
                                            'Production Release',
                                            'Sprint 1 Target',
                                            'Sprint 2 Target',
                                            'Q3 Milestone',
                                            'Q4 Milestone',
                                        ];
                                    @endphp
                                    @foreach($releasePresets as $rp)
                                        <button type="button"
                                            @click="milestoneForm.release_target = '{{ $rp }}'; openRelease = false"
                                            class="w-full text-left px-3 py-1.5 text-xs flex items-center justify-between rounded-lg transition"
                                            :class="milestoneForm.release_target === '{{ $rp }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                            <span>{{ $rp }}</span>
                                            <svg x-show="milestoneForm.release_target === '{{ $rp }}'"
                                                class="w-3.5 h-3.5 text-blue-600" fill="none" stroke="currentColor"
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

                    {{-- Custom Popover 3: Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                            Status
                        </label>
                        <div class="relative" x-data="{ openStatus: false }">
                            <button type="button" @click="openStatus = !openStatus"
                                class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                                <span class="flex items-center gap-2.5">
                                    <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="{
                                            'bg-slate-400': milestoneForm.status === 'planning',
                                            'bg-blue-500': milestoneForm.status === 'in_progress',
                                            'bg-emerald-500': milestoneForm.status === 'completed',
                                            'bg-rose-500': milestoneForm.status === 'at_risk',
                                            'bg-amber-500': milestoneForm.status === 'pending'
                                        }"></span>
                                    <span class="font-medium text-slate-800 dark:text-slate-200" x-text="{
                                            'planning': 'Planning Phase',
                                            'in_progress': 'In Progress',
                                            'completed': 'Completed',
                                            'at_risk': 'At Risk / Issue',
                                            'pending': 'Pending'
                                        }[milestoneForm.status] || 'Planning Phase'"></span>
                                </span>
                                <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>

                            {{-- Status Popover Panel --}}
                            <div x-show="openStatus" @click.outside="openStatus = false" x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                <div class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                    MILESTONE STATUS
                                </div>
                                <div class="space-y-0.5 mt-1 px-1.5">
                                    @php
                                        $milestoneStatuses = [
                                            ['value' => 'planning', 'label' => 'Planning Phase', 'dot' => 'bg-slate-400'],
                                            ['value' => 'in_progress', 'label' => 'In Progress', 'dot' => 'bg-blue-500'],
                                            ['value' => 'completed', 'label' => 'Completed', 'dot' => 'bg-emerald-500'],
                                            ['value' => 'at_risk', 'label' => 'At Risk / Issue', 'dot' => 'bg-rose-500'],
                                            ['value' => 'pending', 'label' => 'Pending', 'dot' => 'bg-amber-500'],
                                        ];
                                    @endphp
                                    @foreach($milestoneStatuses as $st)
                                        <button type="button"
                                            @click="milestoneForm.status = '{{ $st['value'] }}'; openStatus = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                            :class="milestoneForm.status === '{{ $st['value'] }}' ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                            <span class="flex items-center gap-2.5">
                                                <span class="w-2.5 h-2.5 rounded-full {{ $st['dot'] }} shrink-0"></span>
                                                <span>{{ $st['label'] }}</span>
                                            </span>
                                            <svg x-show="milestoneForm.status === '{{ $st['value'] }}'"
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

                {{-- Description / Deliverables --}}
                <div>
                    <label class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">
                        Description / Deliverables
                    </label>
                    <textarea name="description" x-model="milestoneForm.description" rows="3"
                        placeholder="Explain the milestone scope, major acceptance criteria, or deliverables..."
                        class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs text-slate-800 dark:text-slate-200 placeholder-slate-400 resize-none"></textarea>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div
                class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 dark:border-gray-750 bg-white dark:bg-gray-800/40">
                <button type="button" @click="closeMilestoneModal()"
                    class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-650 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition shadow-2xs">
                    Cancel
                </button>
                <button type="submit"
                    :disabled="isSubmittingMilestone"
                    class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">
                    <template x-if="isSubmittingMilestone">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </template>
                    <template x-if="!isSubmittingMilestone">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </template>
                    <span x-text="isSubmittingMilestone ? 'Saving...' : (milestoneModalMode === 'edit' ? 'Save Changes' : 'Create Milestone')"></span>
                </button>
            </div>
        </form>
    </div>
</div>
</div>