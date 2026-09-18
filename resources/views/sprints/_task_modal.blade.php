{{-- Task Detail Modal — Planner & Enterprise Hub UI (Refined & Complete) --}}
<div x-data="taskModalComponent()" x-init="initModal()" @open-task-modal.window="openModal($event.detail.taskId)"
    @keydown.escape.window="handleEscape()" class="relative z-50">

    {{-- ============================================================
    BACKDROP
    ============================================================ --}}
    <div x-show="isOpen" x-cloak x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    {{-- ============================================================
    FLOATING TOAST NOTIFICATION
    ============================================================ --}}
    <div x-show="toast.show" x-cloak x-transition:enter="transition ease-out duration-250"
        x-transition:enter-start="opacity-0 -translate-y-4 scale-95"
        x-transition:enter-end="opacity-100 translate-y-0 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 translate-y-0 scale-100"
        x-transition:leave-end="opacity-0 -translate-y-4 scale-95"
        class="fixed top-6 left-1/2 -translate-x-1/2 z-[80] flex items-center gap-2 px-4 py-2.5 rounded-xl shadow-xl text-xs font-semibold"
        :class="toast.type === 'error' ? 'bg-red-600 text-white' : (toast.type === 'warning' ? 'bg-amber-600 text-white' : 'bg-slate-900 dark:bg-white text-white dark:text-slate-900')">
        <svg x-show="toast.type === 'success' || !toast.type" class="w-4 h-4 text-emerald-400" fill="none"
            stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
        </svg>
        <svg x-show="toast.type === 'error'" class="w-4 h-4 text-white" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
        </svg>
        <svg x-show="toast.type === 'warning'" class="w-4 h-4 text-white" fill="none" stroke="currentColor"
            viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
        </svg>
        <span x-text="toast.message"></span>
    </div>

    {{-- ============================================================
    MODAL WRAPPER
    ============================================================ --}}
    <div x-show="isOpen" x-cloak @click.self="attemptClose()"
        class="fixed inset-0 z-10 overflow-y-auto p-2 sm:p-4 md:p-6 flex items-center justify-center">

        {{-- Modal Dialog --}}
        <div x-show="isOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="relative w-full max-w-6xl max-h-[92vh] flex flex-col bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200/90 dark:border-slate-800 overflow-hidden text-slate-800 dark:text-slate-100"
            style="font-family: 'Inter', system-ui, -apple-system, sans-serif;">

            {{-- ============================================================
            TOP HEADER BAR
            ============================================================ --}}
            <div
                class="px-5 py-2.5 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between gap-3 bg-white dark:bg-slate-900 shrink-0">
                <div class="flex items-center gap-3 flex-wrap min-w-0">
                    {{-- Status Pill Dropdown (Card 01 Trigger) --}}
                    <div class="relative"
                        x-data="{ open: false, isCreatingStatus: false, newStatusName: '', newStatusColor: 'indigo' }">
                        <button type="button" @click="open = !open"
                            class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all duration-150 shadow-2xs"
                            :class="statusPillClass()">
                            <span class="w-2 h-2 rounded-full shrink-0" :class="statusDotClass()"></span>
                            <span x-text="statusLabel()"></span>
                            <svg class="w-3.5 h-3.5 opacity-60 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Card 01: Status Dropdown Popover --}}
                        <div x-show="open" @click.outside="open = false; isCreatingStatus = false" x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                            class="absolute left-0 top-full mt-1.5 w-60 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                            <div
                                class="px-3.5 py-1 text-[10px] font-bold text-slate-400 dark:text-slate-500 uppercase tracking-widest">
                                STATUS TRIGGER</div>
                            <div class="space-y-0.5 mt-1 px-1.5 max-h-52 overflow-y-auto">
                                <template x-for="s in statusOptions" :key="s.value">
                                    <button type="button" @click="setStatus(s.value); open = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                        :class="task.status === s.value ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                        <span class="flex items-center gap-2.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0" :class="s.dotClass"></span>
                                            <span x-text="s.label"></span>
                                        </span>
                                        <svg x-show="task.status === s.value"
                                            class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none"
                                            stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                                d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </template>
                            </div>

                            {{-- Inline Create Custom Status Form --}}
                            <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700/60 px-2">
                                <template x-if="!isCreatingStatus">
                                    <button type="button" @click="isCreatingStatus = true"
                                        class="w-full text-left text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium py-1 px-2 flex items-center gap-1.5 rounded-lg hover:bg-blue-50/50 dark:hover:bg-blue-950/30 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Add custom status
                                    </button>
                                </template>

                                <template x-if="isCreatingStatus">
                                    <div class="space-y-2 p-1.5 bg-slate-50 dark:bg-slate-900 rounded-lg">
                                        <input type="text" x-model="newStatusName" placeholder="New status name..."
                                            class="w-full text-xs px-2.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <template
                                                x-for="c in ['indigo', 'purple', 'blue', 'teal', 'green', 'amber', 'orange', 'red']"
                                                :key="c">
                                                <button type="button" @click="newStatusColor = c"
                                                    class="w-4 h-4 rounded-full transition-transform"
                                                    :class="[getDotBg(c), newStatusColor === c ? 'ring-2 ring-blue-500 ring-offset-1 scale-110' : 'opacity-80']"></button>
                                            </template>
                                        </div>
                                        <div class="flex items-center justify-end gap-1.5 pt-1">
                                            <button type="button" @click="isCreatingStatus = false; newStatusName = ''"
                                                class="px-2 py-1 text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                                            <button type="button"
                                                @click="createCustomStatus(newStatusName, newStatusColor); isCreatingStatus = false; newStatusName = ''; open = false"
                                                class="px-2.5 py-1 text-xs bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Save</button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>

                    {{-- Divider --}}
                    <span class="text-slate-200 dark:text-slate-700 font-light">|</span>

                    {{-- Task ID + Project Name --}}
                    <div class="flex items-center gap-2 text-xs text-slate-500 dark:text-slate-400 font-medium">
                        <span
                            class="px-2 py-0.5 bg-slate-100 dark:bg-slate-800 rounded-md text-[11px] font-mono font-bold text-slate-600 dark:text-slate-300 tracking-tight"
                            x-text="'TASK-' + (task.id || '...')"></span>
                        <span>•</span>
                        <span
                            class="truncate max-w-[220px] font-medium text-slate-600 dark:text-slate-300">{{ $project->name }}</span>
                    </div>

                    {{-- Unsaved indicator --}}
                    <span x-show="isDirty" x-cloak
                        class="inline-flex items-center gap-1.5 text-[11px] font-medium text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2.5 py-0.5 rounded-full border border-amber-200 dark:border-amber-900/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Unsaved changes
                    </span>
                </div>

                {{-- Header Actions --}}
                <div class="flex items-center gap-2 shrink-0">
                    {{-- Google Meet Button --}}
                    <template x-if="task.google_meet_link">
                        <a :href="task.google_meet_link" target="_blank"
                            class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:border-blue-300 hover:text-blue-600 transition shadow-2xs">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                xmlns="http://www.w3.org/2000/svg">
                                <path fill="#00AC47"
                                    d="M15.5 10.5V6.5C15.5 5.4 14.6 4.5 13.5 4.5H4.5C3.4 4.5 2.5 5.4 2.5 6.5V17.5C2.5 18.6 3.4 19.5 4.5 19.5H13.5C14.6 19.5 15.5 18.6 15.5 17.5V13.5L20.5 17.5V6.5L15.5 10.5Z" />
                                <path fill="#EA4335"
                                    d="M13.5 4.5H4.5C3.4 4.5 2.5 5.4 2.5 6.5V11H15.5V6.5C15.5 5.4 14.6 4.5 13.5 4.5Z" />
                                <path fill="#FFBA00" d="M2.5 6.5C2.5 5.4 3.4 4.5 4.5 4.5H8V11H2.5V6.5Z" />
                                <path fill="#0066DA"
                                    d="M15.5 13H2.5V17.5C2.5 18.6 3.4 19.5 4.5 19.5H13.5C14.6 19.5 15.5 18.6 15.5 17.5V13Z" />
                                <path fill="#2684FC" d="M2.5 13H8V19.5H4.5C3.4 19.5 2.5 18.6 2.5 17.5V13Z" />
                                <path fill="#00832D" d="M15.5 10.5L20.5 6.5V17.5L15.5 13.5V10.5Z" />
                                <path fill="#EA4335" d="M15.5 10.5L20.5 6.5V10.5L15.5 12V10.5Z" />
                                <path fill="#0066DA" d="M15.5 12L20.5 13.5V17.5L15.5 13.5V12Z" />
                            </svg>
                            Join Google Meet
                        </a>
                    </template>
                    <template x-if="!task.google_meet_link">
                        @if(!auth()->user()->hasRole('client') && $project->google_meet_enabled)
                            <button type="button" @click="createMeeting()" :disabled="meetingCreating"
                                class="inline-flex items-center gap-2 px-3 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-semibold text-slate-700 dark:text-slate-200 hover:border-blue-300 hover:text-blue-600 transition shadow-2xs">
                                <svg class="w-4 h-4 shrink-0" viewBox="0 0 24 24" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill="#00AC47"
                                        d="M15.5 10.5V6.5C15.5 5.4 14.6 4.5 13.5 4.5H4.5C3.4 4.5 2.5 5.4 2.5 6.5V17.5C2.5 18.6 3.4 19.5 4.5 19.5H13.5C14.6 19.5 15.5 18.6 15.5 17.5V13.5L20.5 17.5V6.5L15.5 10.5Z" />
                                    <path fill="#EA4335"
                                        d="M13.5 4.5H4.5C3.4 4.5 2.5 5.4 2.5 6.5V11H15.5V6.5C15.5 5.4 14.6 4.5 13.5 4.5Z" />
                                    <path fill="#FFBA00" d="M2.5 6.5C2.5 5.4 3.4 4.5 4.5 4.5H8V11H2.5V6.5Z" />
                                    <path fill="#0066DA"
                                        d="M15.5 13H2.5V17.5C2.5 18.6 3.4 19.5 4.5 19.5H13.5C14.6 19.5 15.5 18.6 15.5 17.5V13Z" />
                                    <path fill="#2684FC" d="M2.5 13H8V19.5H4.5C3.4 19.5 2.5 18.6 2.5 17.5V13Z" />
                                    <path fill="#00832D" d="M15.5 10.5L20.5 6.5V17.5L15.5 13.5V10.5Z" />
                                    <path fill="#EA4335" d="M15.5 10.5L20.5 6.5V10.5L15.5 12V10.5Z" />
                                    <path fill="#0066DA" d="M15.5 12L20.5 13.5V17.5L15.5 13.5V12Z" />
                                </svg>
                                <span x-text="meetingCreating ? 'Creating...' : 'Create Google Meet'"></span>
                            </button>
                        @endif
                    </template>

                    {{-- Toggle Panel --}}
                    <button type="button" @click="rightPanelOpen = !rightPanelOpen"
                        :title="rightPanelOpen ? 'Hide Panel' : 'Show Panel'"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>

                    {{-- Full page link --}}
                    <template x-if="task.id">
                        <a :href="'/projects/{{ $project->id }}/tasks/' + task.id" target="_blank"
                            title="Open full page"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                            </svg>
                        </a>
                    </template>

                    {{-- Close --}}
                    <button type="button" @click="attemptClose()"
                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Loading State --}}
            <div x-show="loading" class="py-20 flex flex-col items-center justify-center">
                <img src="{{ asset('flovig_loading_white.gif') }}" alt="Loading..." class="w-44 h-auto object-contain">
            </div>

            {{-- ============================================================
            MAIN BODY (Split: Left 70% / Right 30%)
            ============================================================ --}}
            <div x-show="!loading" class="flex-1 flex overflow-hidden">

                {{-- ========== LEFT COLUMN ========== --}}
                <div class="flex-1 overflow-y-auto">

                    {{-- Cover Image Section --}}
                    <div class="relative">
                        <template x-if="task.cover_image_path">
                            <div
                                class="relative group h-44 sm:h-52 w-full overflow-hidden bg-gradient-to-br from-blue-600 via-indigo-600 to-blue-800">
                                <img :src="coverUrl" alt="Cover" class="w-full h-full object-cover">
                                {{-- Gradient Overlay --}}
                                <div
                                    class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent">
                                </div>

                                {{-- Sprint/Bucket badge bottom-right --}}
                                <div class="absolute bottom-3 right-4">
                                    <span
                                        class="px-3 py-1 bg-white/90 dark:bg-slate-900/90 backdrop-blur-md rounded-lg text-xs font-semibold text-slate-700 dark:text-slate-200 shadow-sm"
                                        x-text="task.sprint ? task.sprint.name : (currentColumn ? currentColumn.name : 'Sprint Backlog v2')"></span>
                                </div>

                                {{-- Bottom-left Action: Change Cover Image --}}
                                <div class="absolute bottom-3 left-4 flex items-center gap-2">
                                    <label
                                        class="cursor-pointer px-3 py-1.5 bg-slate-900/80 backdrop-blur-md hover:bg-slate-900 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                        </svg>
                                        Change Cover
                                        <input type="file" accept="image/*" class="hidden"
                                            @change="uploadCover($event)">
                                    </label>
                                    <button type="button" @click="removeCover()"
                                        class="px-2.5 py-1.5 bg-red-600/80 backdrop-blur-md hover:bg-red-600 text-white rounded-lg text-xs font-semibold shadow-sm transition flex items-center gap-1">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </template>

                        {{-- Empty Cover State --}}
                        <template x-if="!task.cover_image_path">
                            <div class="mx-6 mt-5 mb-1">
                                <label
                                    class="cursor-pointer flex items-center justify-center gap-2 py-7 border-2 border-dashed border-slate-200 dark:border-slate-700/80 rounded-xl text-slate-400 dark:text-slate-500 hover:text-blue-600 hover:border-blue-300 dark:hover:border-blue-600 transition-all duration-200 bg-slate-50/50 dark:bg-slate-800/30">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                    </svg>
                                    <span class="text-xs sm:text-sm font-medium">+ Add cover image</span>
                                    <input type="file" accept="image/*" class="hidden" @change="uploadCover($event)">
                                </label>
                            </div>
                        </template>
                    </div>

                    {{-- Title & Header Info --}}
                    <div class="px-6 pt-4 pb-5 space-y-4">

                        {{-- Title Row --}}
                        <div class="flex items-start gap-3">
                            <button type="button" @click="toggleComplete()"
                                class="mt-1 w-7 h-7 rounded-full border-2 flex items-center justify-center shrink-0 transition-all duration-200 shadow-2xs"
                                :class="task.status === 'done' ? 'border-emerald-500 bg-emerald-500 text-white' : 'border-slate-300 dark:border-slate-600 hover:border-blue-500'">
                                <svg x-show="task.status === 'done'" class="w-4 h-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                        d="M5 13l4 4L19 7" />
                                </svg>
                            </button>

                            <div class="flex-1 flex items-center gap-2.5 flex-wrap min-w-0">
                                <template x-if="task.recurring_definition_id && recurringFrequency !== 'none'">
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider bg-blue-50 text-blue-600 dark:bg-blue-950/60 dark:text-blue-400 border border-blue-200/80 dark:border-blue-800/80 shrink-0 select-none shadow-2xs">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                        </svg>
                                        Recurring
                                    </span>
                                </template>
                                <input type="text" x-model="task.title" @input="markDirty()" placeholder="Task title..."
                                    class="flex-1 min-w-[200px] text-lg sm:text-xl md:text-2xl font-bold text-slate-900 dark:text-white bg-transparent border-0 border-none outline-none focus:outline-none focus-visible:outline-none focus:ring-0 focus:border-none focus:shadow-none shadow-none ring-0 px-2 py-1 -ml-2 rounded-xl transition-all duration-150 hover:bg-slate-100/60 dark:hover:bg-slate-800/50 focus:bg-slate-50 dark:focus:bg-slate-800/80 placeholder:text-slate-300 dark:placeholder:text-slate-600 leading-tight">
                            </div>
                        </div>

                        {{-- Labels Row --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            <template x-for="l in task.labels" :key="l.id">
                                <span
                                    class="inline-flex items-center gap-1.5 pl-2.5 pr-1.5 py-0.5 rounded-md text-xs font-semibold"
                                    :class="labelClasses(l.color).badge">
                                    <span x-text="l.name"></span>
                                    <button type="button" @click="removeLabel(l.id)"
                                        class="p-0.5 rounded-full hover:bg-black/10 transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            {{-- Add Label Dropdown --}}
                            <div class="relative"
                                x-data="{ open: false, newName: '', newColor: 'blue', isCreating: false }">
                                <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium border border-dashed border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:border-blue-400 hover:text-blue-600 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Label
                                </button>

                                <div x-show="open" @click.outside="open = false; isCreating = false" x-cloak
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="opacity-0 scale-95"
                                    x-transition:enter-end="opacity-100 scale-100"
                                    class="absolute left-0 top-full mt-1.5 w-64 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 p-2 z-50">
                                    <div
                                        class="px-2 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        Project Labels</div>
                                    <div class="max-h-44 overflow-y-auto space-y-0.5">
                                        <template x-for="lbl in projectLabels" :key="lbl.id">
                                            <button type="button" @click="toggleLabel(lbl); open = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                                <span class="flex items-center gap-2">
                                                    <span class="w-2.5 h-2.5 rounded-full"
                                                        :class="labelClasses(lbl.color).dot"></span>
                                                    <span x-text="lbl.name"
                                                        class="font-medium text-slate-700 dark:text-slate-300"></span>
                                                </span>
                                                <svg x-show="isLabelAttached(lbl.id)" class="w-4 h-4 text-blue-600"
                                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2" d="M5 13l4 4L19 7" />
                                                </svg>
                                            </button>
                                        </template>
                                    </div>
                                    {{-- Inline Create New Label --}}
                                    <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700/60">
                                        <template x-if="!isCreating">
                                            <button type="button" @click="isCreating = true"
                                                class="w-full text-left text-xs text-blue-600 hover:text-blue-700 font-medium py-1 px-2 flex items-center gap-1">
                                                + Create new label
                                            </button>
                                        </template>
                                        <template x-if="isCreating">
                                            <div class="space-y-2 p-1">
                                                <input type="text" x-model="newName" placeholder="Label name..."
                                                    class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                <div class="flex items-center gap-1.5 flex-wrap">
                                                    <template
                                                        x-for="c in ['blue', 'green', 'red', 'yellow', 'orange', 'purple', 'teal']"
                                                        :key="c">
                                                        <button type="button" @click="newColor = c"
                                                            class="w-5 h-5 rounded-full transition-transform"
                                                            :class="[labelClasses(c).dot, newColor === c ? 'ring-2 ring-offset-1 ring-blue-500 scale-110' : 'opacity-80']"></button>
                                                    </template>
                                                </div>
                                                <div class="flex items-center justify-end gap-1 pt-1">
                                                    <button type="button" @click="isCreating = false; newName = ''"
                                                        class="px-2 py-1 text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                                                    <button type="button"
                                                        @click="createNewLabel(newName, newColor); isCreating = false; newName = ''; open = false"
                                                        class="px-2.5 py-1 text-xs bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Save</button>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Members Row --}}
                        <div class="flex items-center gap-2 flex-wrap">
                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                            <template x-for="m in task.members" :key="m.id">
                                <span
                                    class="inline-flex items-center gap-1.5 pl-1.5 pr-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 dark:bg-slate-800 border border-slate-200/80 dark:border-slate-700">
                                    <template x-if="m.avatar">
                                        <img :src="'/storage/' + m.avatar" class="w-5 h-5 rounded-full object-cover">
                                    </template>
                                    <template x-if="!m.avatar">
                                        <span
                                            class="w-5 h-5 rounded-full text-white text-[10px] flex items-center justify-center font-bold"
                                            :style="'background-color:' + getAvatarColor(m.name)"
                                            x-text="getInitials(m.name)"></span>
                                    </template>
                                    <span class="text-slate-700 dark:text-slate-200" x-text="m.name"></span>
                                    <button type="button" @click="removeMember(m.id)"
                                        class="text-slate-400 hover:text-red-500 p-0.5 rounded-full transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </span>
                            </template>

                            {{-- Add Assignee Dropdown --}}
                            <div class="relative" x-data="{ open: false }">
                                <button type="button" @click="open = !open"
                                    class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border border-dashed border-slate-300 dark:border-slate-600 text-slate-500 dark:text-slate-400 hover:border-blue-400 hover:text-blue-600 transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Assignee
                                </button>
                                <div x-show="open" @click.outside="open = false" x-cloak
                                    class="absolute left-0 top-full mt-1.5 w-60 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-1 z-50 max-h-60 overflow-y-auto">
                                    <div
                                        class="px-3 py-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                        Select Team Member</div>
                                    <template x-for="user in assignableUsers" :key="user.id">
                                        <button type="button" @click="toggleMember(user); open = false"
                                            class="w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                            <span class="flex items-center gap-2">
                                                <span
                                                    class="w-5 h-5 rounded-full text-white text-[10px] flex items-center justify-center font-bold"
                                                    :style="'background-color:' + getAvatarColor(user.name)"
                                                    x-text="getInitials(user.name)"></span>
                                                <span class="text-slate-700 dark:text-slate-300"
                                                    x-text="user.name"></span>
                                            </span>
                                            <svg x-show="isMemberAssigned(user.id)" class="w-4 h-4 text-blue-600"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        {{-- Tab Switcher: Task Details | Attachments --}}
                        <div class="flex items-center gap-1 bg-slate-100/90 dark:bg-slate-800/80 rounded-xl p-1 w-fit">
                            <button type="button" @click="activeTab = 'details'"
                                class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200"
                                :class="activeTab === 'details' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                                Task Details
                            </button>
                            <button type="button" @click="activeTab = 'attachments'"
                                class="flex items-center gap-2 px-4 py-1.5 rounded-lg text-xs font-semibold transition-all duration-200"
                                :class="activeTab === 'attachments' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 dark:text-slate-400 hover:text-slate-700 dark:hover:text-slate-200'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                </svg>
                                Attachments
                                <span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold"
                                    :class="activeTab === 'attachments' ? 'bg-blue-100 text-blue-700 dark:bg-blue-900/50 dark:text-blue-300' : 'bg-slate-200 dark:bg-slate-600 text-slate-600 dark:text-slate-300'"
                                    x-text="(task.attachments || []).length"></span>
                            </button>
                        </div>
                    </div>

                    {{-- ========== TAB 1: TASK DETAILS ========== --}}
                    <div x-show="activeTab === 'details'" class="px-6 pb-6 space-y-6">

                        {{-- Metadata 2-Column Grid --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-4">

                            {{-- Field 1: Status Dropdown (Card 01) --}}
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Status</label>
                                <div class="relative"
                                    x-data="{ open: false, isCreatingStatus: false, newStatusName: '', newStatusColor: 'indigo' }">
                                    <button type="button" @click="open = !open"
                                        class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 dark:hover:border-slate-600 transition shadow-2xs">
                                        <span class="flex items-center gap-2.5">
                                            <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                                :class="statusDotClass()"></span>
                                            <span class="text-slate-800 dark:text-slate-200 font-medium"
                                                x-text="statusLabel()"></span>
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.outside="open = false; isCreatingStatus = false" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                        <div
                                            class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            STATUS TRIGGER</div>
                                        <div class="space-y-0.5 mt-1 px-1.5 max-h-52 overflow-y-auto">
                                            <template x-for="s in statusOptions" :key="s.value">
                                                <button type="button" @click="setStatus(s.value); open = false"
                                                    class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                                    :class="task.status === s.value ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-600 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                                    <span class="flex items-center gap-2.5">
                                                        <span class="w-2.5 h-2.5 rounded-full shrink-0"
                                                            :class="s.dotClass"></span>
                                                        <span x-text="s.label"></span>
                                                    </span>
                                                    <svg x-show="task.status === s.value" class="w-4 h-4 text-blue-600"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                        <div class="mt-2 pt-2 border-t border-slate-100 dark:border-slate-700/60 px-2">
                                            <template x-if="!isCreatingStatus">
                                                <button type="button" @click="isCreatingStatus = true"
                                                    class="w-full text-left text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium py-1 px-2 flex items-center gap-1.5 rounded-lg hover:bg-blue-50/50 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add custom status
                                                </button>
                                            </template>
                                            <template x-if="isCreatingStatus">
                                                <div class="space-y-2 p-1.5 bg-slate-50 dark:bg-slate-900 rounded-lg">
                                                    <input type="text" x-model="newStatusName"
                                                        placeholder="New status name..."
                                                        class="w-full text-xs px-2.5 py-1.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <template
                                                            x-for="c in ['indigo', 'purple', 'blue', 'teal', 'green', 'amber', 'orange', 'red']"
                                                            :key="c">
                                                            <button type="button" @click="newStatusColor = c"
                                                                class="w-4 h-4 rounded-full transition-transform"
                                                                :class="[getDotBg(c), newStatusColor === c ? 'ring-2 ring-blue-500 ring-offset-1 scale-110' : 'opacity-80']"></button>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1.5 pt-1">
                                                        <button type="button"
                                                            @click="isCreatingStatus = false; newStatusName = ''"
                                                            class="px-2 py-1 text-xs text-slate-500 hover:text-slate-700">Cancel</button>
                                                        <button type="button"
                                                            @click="createCustomStatus(newStatusName, newStatusColor); isCreatingStatus = false; newStatusName = ''; open = false"
                                                            class="px-2.5 py-1 text-xs bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Save</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Field 2: Priority Dropdown (Card 02 Badged Options) --}}
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Priority</label>
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button" @click="open = !open"
                                        class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border rounded-xl text-xs font-medium hover:border-slate-300 transition shadow-2xs"
                                        :class="priorityTriggerClass()">
                                        <span class="flex items-center gap-2.5">
                                            <span x-html="priorityIcon()"></span>
                                            <span class="font-medium"
                                                :class="task.priority === 'urgent' ? 'text-red-600 dark:text-red-400 font-semibold' : (task.priority === 'high' ? 'text-amber-700 dark:text-amber-400 font-semibold' : 'text-slate-800 dark:text-slate-200')"
                                                x-text="priorityLabel()"></span>
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    <div x-show="open" @click.outside="open = false" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        class="absolute left-0 top-full mt-1.5 w-full bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-2 z-50">
                                        <div
                                            class="px-3.5 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                            TASK PRIORITY</div>
                                        <div class="space-y-0.5 mt-1 px-1.5">
                                            <template x-for="p in priorityOptions" :key="p.value">
                                                <button type="button"
                                                    @click="task.priority = p.value; markDirty(); open = false"
                                                    class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-lg transition"
                                                    :class="task.priority === p.value ? (p.value === 'urgent' ? 'bg-red-50/80 text-red-700 font-semibold' : (p.value === 'high' ? 'bg-amber-50/80 text-amber-800 font-semibold' : 'bg-slate-50 font-semibold text-slate-800')) : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                                    <span class="flex items-center gap-2.5">
                                                        <span x-html="p.icon"></span>
                                                        <span x-text="p.label"></span>
                                                        <template x-if="p.value === 'urgent'">
                                                            <span
                                                                class="px-1.5 py-0.5 bg-red-100 text-red-700 border border-red-200 text-[9px] font-bold rounded uppercase tracking-wider">CRITICAL</span>
                                                        </template>
                                                    </span>
                                                    <svg x-show="task.priority === p.value" class="w-4 h-4"
                                                        :class="p.value === 'urgent' ? 'text-red-600' : (p.value === 'high' ? 'text-amber-600' : 'text-blue-600')"
                                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Field 3: Start Date --}}
                            <div>
                                <label
                                    class="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1.5">Start
                                    date</label>
                                <div class="relative">
                                    <input type="date" x-model="task.start_date" @input="markDirty()"
                                        class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-2xs"
                                        placeholder="dd/mm/yyyy">
                                </div>
                            </div>

                            {{-- Field 4: Due Date (with On Track / Overdue badge) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="text-xs font-semibold text-slate-500 dark:text-slate-400">Due
                                        date</label>
                                    <template x-if="task.due_date && !isOverdue()">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] font-bold text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                            On Track
                                        </span>
                                    </template>
                                    <template x-if="task.due_date && isOverdue()">
                                        <span
                                            class="inline-flex items-center gap-1.5 text-[10px] font-bold text-red-600 bg-red-50 px-2 py-0.5 rounded-full border border-red-100">
                                            <span class="w-1.5 h-1.5 rounded-full bg-red-500 animate-pulse"></span>
                                            Overdue
                                        </span>
                                    </template>
                                </div>
                                <div class="relative">
                                    <input type="date" x-model="task.due_date" @input="markDirty()"
                                        class="w-full text-xs font-medium bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl px-3.5 py-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition shadow-2xs">
                                </div>
                            </div>

                            {{-- Field 5: Repeat (Card 04 Recurring Rule Scheduler) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1">
                                        <label
                                            class="text-xs font-semibold text-slate-500 dark:text-slate-400">Repeat</label>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <div class="relative" x-data="{ repeatOpen: false }">
                                    <button type="button" @click="repeatOpen = !repeatOpen"
                                        class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 transition shadow-2xs">
                                        <span class="flex items-center gap-2 min-w-0">
                                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                            </svg>
                                            <span class="truncate text-slate-800 dark:text-slate-200 font-medium"
                                                x-text="recurringSummaryText"></span>
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {{-- Card 04: Recurring Rule Popover --}}
                                    <div x-show="repeatOpen" @click.outside="repeatOpen = false" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        class="absolute left-0 sm:left-auto sm:right-0 top-full mt-1.5 w-84 bg-white dark:bg-slate-800 rounded-2xl shadow-2xl border border-slate-100 dark:border-slate-700 p-4 z-50 space-y-4">

                                        {{-- Frequency Label --}}
                                        <div>
                                            <label
                                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">RECURRING
                                                FREQUENCY</label>
                                            <div
                                                class="grid grid-cols-5 gap-1 bg-slate-100 dark:bg-slate-700/50 p-1 rounded-xl">
                                                <button type="button" @click="recurringFrequency = 'none'"
                                                    class="py-1.5 text-[11px] font-semibold rounded-lg transition"
                                                    :class="recurringFrequency === 'none' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                                                    None
                                                </button>
                                                <button type="button" @click="recurringFrequency = 'daily'"
                                                    class="py-1.5 text-[11px] font-semibold rounded-lg transition"
                                                    :class="recurringFrequency === 'daily' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                                                    Daily
                                                </button>
                                                <button type="button" @click="recurringFrequency = 'weekly'"
                                                    class="py-1.5 text-[11px] font-semibold rounded-lg transition"
                                                    :class="recurringFrequency === 'weekly' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                                                    Weekly
                                                </button>
                                                <button type="button" @click="recurringFrequency = 'monthly'"
                                                    class="py-1.5 text-[11px] font-semibold rounded-lg transition"
                                                    :class="recurringFrequency === 'monthly' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                                                    Monthly
                                                </button>
                                                <button type="button" @click="recurringFrequency = 'custom'"
                                                    class="py-1.5 text-[11px] font-semibold rounded-lg transition"
                                                    :class="recurringFrequency === 'custom' ? 'bg-white dark:bg-slate-800 text-blue-600 shadow-sm' : 'text-slate-600 dark:text-slate-300 hover:text-slate-900'">
                                                    Custom
                                                </button>
                                            </div>
                                        </div>

                                        {{-- When 'none' is selected --}}
                                        <div x-show="recurringFrequency === 'none'"
                                            class="p-3 bg-slate-50 dark:bg-slate-900/50 rounded-xl text-center">
                                            <p class="text-xs text-slate-500 dark:text-slate-400">This task is not set
                                                to repeat (one-time task).</p>
                                        </div>

                                        {{-- Choose Day (When Weekly or Custom) --}}
                                        <div
                                            x-show="recurringFrequency === 'weekly' || recurringFrequency === 'custom'">
                                            <label
                                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">CHOOSE
                                                DAY</label>
                                            <div class="flex items-center justify-between gap-1">
                                                <template
                                                    x-for="day in ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']"
                                                    :key="day">
                                                    <button type="button" @click="toggleRecurringDay(day)"
                                                        class="w-8 h-8 rounded-full text-xs font-semibold flex items-center justify-center transition-all shadow-2xs"
                                                        :class="recurringDays.includes(day) ? 'bg-blue-600 text-white shadow-sm ring-2 ring-blue-400/30' : 'bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 hover:bg-slate-200'"
                                                        x-text="day"></button>
                                                </template>
                                            </div>
                                        </div>

                                        {{-- End Date --}}
                                        <div x-show="recurringFrequency !== 'none'">
                                            <label
                                                class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">ENDS
                                                ON (EXPIRATION DATE)</label>
                                            <input type="date" x-model="recurringEndDate"
                                                class="w-full text-xs px-3 py-2 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-1 focus:ring-blue-500">
                                        </div>

                                        {{-- Footer Actions --}}
                                        <div
                                            class="flex items-center justify-between pt-2 border-t border-slate-100 dark:border-slate-700/60">
                                            <button type="button" @click="deleteRecurringRule(); repeatOpen = false"
                                                class="text-xs font-semibold text-red-500 hover:text-red-700 transition">
                                                Delete
                                            </button>
                                            <button type="button" @click="saveRecurringRule(); repeatOpen = false"
                                                class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                                                Submit
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Field 6: Bucket (Card 03 Bucket Kanban Selector) --}}
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-1">
                                        <label
                                            class="text-xs font-semibold text-slate-500 dark:text-slate-400">Bucket</label>
                                    </div>
                                    <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>

                                <div class="relative" x-data="{ bucketOpen: false }">
                                    <button type="button" @click="bucketOpen = !bucketOpen"
                                        class="w-full flex items-center justify-between px-3.5 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium hover:border-slate-300 transition shadow-2xs">
                                        <span class="flex items-center gap-2.5">
                                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M4 6h16M4 12h16M4 18h16" />
                                            </svg>
                                            <span class="text-slate-800 dark:text-slate-200 font-medium"
                                                x-text="currentColumn ? currentColumn.name : 'Select Bucket'"></span>
                                        </span>
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor"
                                            viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </button>

                                    {{-- Card 03: Bucket Kanban Selector Popover --}}
                                    <div x-show="bucketOpen" @click.outside="bucketOpen = false" x-cloak
                                        x-transition:enter="transition ease-out duration-150"
                                        x-transition:enter-start="opacity-0 scale-95"
                                        x-transition:enter-end="opacity-100 scale-100"
                                        class="absolute left-0 sm:left-auto sm:right-0 top-full mt-1.5 w-72 bg-white dark:bg-slate-800 rounded-2xl shadow-xl border border-slate-100 dark:border-slate-700 p-3 z-50 space-y-2.5">

                                        {{-- Header --}}
                                        <div class="flex items-center justify-between px-1">
                                            <span
                                                class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">SELECT
                                                COLUMN / BUCKET</span>
                                            <span class="text-[10px] text-slate-400">Kanban Column</span>
                                        </div>

                                        {{-- Search Box --}}
                                        <div class="relative">
                                            <svg class="w-3.5 h-3.5 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <input type="text" x-model="bucketSearch" placeholder="Search bucket..."
                                                class="w-full text-xs pl-8 pr-3 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-1 focus:ring-blue-500">
                                        </div>

                                        {{-- Column List --}}
                                        <div class="max-h-48 overflow-y-auto space-y-1">
                                            <template x-for="col in filteredColumns" :key="col.id">
                                                <button type="button" @click="setBucket(col.id); bucketOpen = false"
                                                    class="w-full text-left px-3 py-2 text-xs flex items-center justify-between rounded-xl transition"
                                                    :class="task.board_column_id === col.id ? 'bg-blue-50 dark:bg-blue-950/50 text-blue-700 dark:text-blue-400 font-semibold' : 'text-slate-700 dark:text-slate-300 hover:bg-slate-50 dark:hover:bg-slate-700/50'">
                                                    <span class="flex items-center gap-2 min-w-0">
                                                        <span class="w-2 h-2 rounded-full shrink-0"
                                                            :style="'background-color:' + (col.color || '#3b82f6')"></span>
                                                        <span class="truncate" x-text="col.name"></span>
                                                    </span>
                                                    <div class="flex items-center gap-2 shrink-0">
                                                        <span
                                                            class="px-2 py-0.5 rounded-full text-[10px] bg-slate-100 dark:bg-slate-700 text-slate-500 dark:text-slate-400 font-medium"
                                                            x-text="getColumnTaskCount(col.id) + ' tasks'"></span>
                                                        <svg x-show="task.board_column_id === col.id"
                                                            class="w-4 h-4 text-blue-600" fill="none"
                                                            stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                stroke-width="2.5" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                </button>
                                            </template>
                                        </div>

                                        {{-- Add New Bucket Inline Form --}}
                                        <div class="pt-2 border-t border-slate-100 dark:border-slate-700/60">
                                            <template x-if="!isCreatingBucket">
                                                <button type="button" @click="isCreatingBucket = true"
                                                    class="w-full text-left text-xs text-blue-600 dark:text-blue-400 hover:text-blue-700 font-medium py-1 px-1 flex items-center gap-1.5 rounded-lg hover:bg-blue-50/50 transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M12 4v16m8-8H4" />
                                                    </svg>
                                                    Add new bucket
                                                </button>
                                            </template>
                                            <template x-if="isCreatingBucket">
                                                <div class="space-y-2 p-1">
                                                    <input type="text" x-model="newBucketName"
                                                        placeholder="New bucket name..."
                                                        class="w-full text-xs px-2.5 py-1.5 bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-lg focus:ring-1 focus:ring-blue-500">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <template
                                                            x-for="c in ['blue', 'green', 'red', 'purple', 'amber', 'pink', 'indigo']"
                                                            :key="c">
                                                            <button type="button" @click="newBucketColor = c"
                                                                class="w-4 h-4 rounded-full transition-transform"
                                                                :class="[labelClasses(c).dot, newBucketColor === c ? 'ring-2 ring-blue-500 scale-110' : 'opacity-80']"></button>
                                                        </template>
                                                    </div>
                                                    <div class="flex justify-end gap-1.5 pt-1">
                                                        <button type="button"
                                                            @click="isCreatingBucket = false; newBucketName = ''"
                                                            class="px-2 py-1 text-xs text-slate-500">Cancel</button>
                                                        <button type="button"
                                                            @click="createNewBucket(); bucketOpen = false"
                                                            class="px-2.5 py-1 text-xs bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700">Save</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-slate-100 dark:border-slate-800"></div>

                        {{-- ========== Definition of Done / Checklist Section ========== --}}
                        <div class="space-y-6">
                            {{-- Top Header / Create New Checklist Group Button --}}
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">CHECKLISTS</span>
                                <button type="button" @click="isCreatingChecklistGroup = true"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Add Checklist Group
                                </button>
                            </div>

                            {{-- Inline Create Group Form (Clean UI) --}}
                            <div x-show="isCreatingChecklistGroup" x-cloak
                                class="p-3.5 bg-white dark:bg-slate-900 border border-slate-200/90 dark:border-slate-800 rounded-xl space-y-2.5 shadow-xs">
                                <div class="flex items-center justify-between">
                                    <label class="block text-xs font-bold text-slate-700 dark:text-slate-200">New
                                        Checklist Group Name</label>
                                    <button type="button"
                                        @click="isCreatingChecklistGroup = false; newChecklistGroupTitle = ''"
                                        class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 text-xs font-semibold">✕</button>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="text" x-model="newChecklistGroupTitle"
                                        placeholder="e.g. Definition of Done, QA Testing, Client Review..."
                                        @keydown.enter.prevent="submitNewChecklistGroup()"
                                        class="flex-1 text-xs px-3 py-2 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg focus:outline-none focus:ring-1 focus:ring-blue-500 text-slate-800 dark:text-slate-100 placeholder:text-slate-400">
                                    <button type="button"
                                        @click="isCreatingChecklistGroup = false; newChecklistGroupTitle = ''"
                                        class="px-3 py-2 text-xs text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200 font-medium">Cancel</button>
                                    <button type="button" @click="submitNewChecklistGroup()"
                                        class="px-3.5 py-2 text-xs bg-slate-900 hover:bg-slate-800 dark:bg-blue-600 dark:hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-xs">
                                        + Add Group
                                    </button>
                                </div>
                            </div>

                            {{-- Checklist Groups Loop --}}
                            <template x-for="(cl, cIndex) in task.checklists" :key="cl.id || cIndex">
                                <div
                                    class="space-y-3 p-4 bg-white dark:bg-slate-900 rounded-2xl border border-slate-200/80 dark:border-slate-800 shadow-2xs">
                                    {{-- Group Header --}}
                                    <div class="flex items-center justify-between gap-2 flex-wrap">
                                        <div class="flex items-center gap-3">
                                            {{-- Progress Circle Ring (Enlarged w-11 h-11) --}}
                                            <div class="relative w-11 h-11 shrink-0">
                                                <svg class="w-11 h-11 -rotate-90" viewBox="0 0 44 44">
                                                    <circle cx="22" cy="22" r="18" fill="none" stroke="currentColor"
                                                        class="text-slate-200 dark:text-slate-700" stroke-width="3.5" />
                                                    <circle cx="22" cy="22" r="18" fill="none"
                                                        :stroke="getChecklistProgress(cl).pct === 100 ? '#10b981' : '#3b82f6'"
                                                        stroke-width="3.5" stroke-linecap="round"
                                                        :stroke-dasharray="'113.1'"
                                                        :stroke-dashoffset="113.1 - (113.1 * getChecklistProgress(cl).pct / 100)"
                                                        class="transition-all duration-300" />
                                                </svg>
                                                <span
                                                    class="absolute inset-0 flex items-center justify-center text-[10px] font-black text-slate-800 dark:text-slate-100"
                                                    x-text="getChecklistProgress(cl).pct + '%'"></span>
                                            </div>

                                            {{-- Editable Title --}}
                                            <div class="flex items-center gap-1.5">
                                                <input type="text" x-model="cl.title" @change="updateChecklistGroup(cl)"
                                                    class="font-bold text-sm text-slate-800 dark:text-slate-100 bg-transparent border-0 border-none outline-none focus:outline-none focus-visible:outline-none focus:ring-0 focus:border-none focus:shadow-none shadow-none ring-0 focus:bg-white dark:focus:bg-slate-800 rounded px-1.5 py-0.5 transition"
                                                    :id="'cl-title-' + cIndex">
                                                <button type="button"
                                                    @click="document.getElementById('cl-title-' + cIndex).focus()"
                                                    title="Edit checklist title"
                                                    class="text-slate-400 hover:text-slate-600 p-1 rounded-md transition">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                                    </svg>
                                                </button>
                                            </div>

                                            <span
                                                class="px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-blue-50 text-blue-600 dark:bg-blue-950/40 dark:text-blue-400 border border-blue-100 dark:border-blue-900/50"
                                                x-text="getChecklistProgress(cl).text"></span>
                                        </div>

                                        <div class="flex items-center gap-2">
                                            <button type="button" @click="confirmDeleteChecklistGroup(cl, cIndex)"
                                                title="Delete checklist group"
                                                class="text-slate-400 hover:text-red-500 p-1 rounded transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Checklist Items (Flush alignment with header) --}}
                                    <div class="space-y-1.5 pt-1">
                                        <template x-for="(item, iIndex) in cl.items" :key="item.id || iIndex">
                                            <div
                                                class="group/item flex items-center justify-between gap-2.5 p-2 rounded-xl hover:bg-white dark:hover:bg-slate-800/80 transition border border-transparent hover:border-slate-100 dark:hover:border-slate-700/50">
                                                <div class="flex items-center gap-2.5 flex-1 min-w-0 cursor-pointer"
                                                    @click="toggleChecklistItem(cl, item)">
                                                    {{-- Sleek Custom Checkbox Button --}}
                                                    <div class="w-5 h-5 rounded-lg flex items-center justify-center transition-all duration-200 shrink-0 border"
                                                        :class="item.is_done 
                                                            ? 'bg-emerald-500 border-emerald-500 text-white shadow-xs shadow-emerald-500/20' 
                                                            : 'bg-white dark:bg-slate-900 border-slate-300 dark:border-slate-600 hover:border-emerald-500 dark:hover:border-emerald-500 text-transparent'">
                                                        <svg class="w-3.5 h-3.5 transition-transform"
                                                            :class="item.is_done ? 'scale-100' : 'scale-50 opacity-0'"
                                                            fill="none" stroke="currentColor" stroke-width="2.5"
                                                            viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M5 13l4 4L19 7" />
                                                        </svg>
                                                    </div>
                                                    <span class="text-xs sm:text-sm transition truncate select-none"
                                                        :class="item.is_done ? 'line-through text-slate-400 dark:text-slate-500 font-normal' : 'text-slate-700 dark:text-slate-200 font-medium'"
                                                        x-text="item.title"></span>
                                                </div>
                                                <button type="button" @click="deleteChecklistItem(cl, item, iIndex)"
                                                    class="opacity-0 group-hover/item:opacity-100 text-slate-400 hover:text-red-500 p-1 rounded transition shrink-0">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>

                                        {{-- Inline Quick Add Input --}}
                                        <form @submit.prevent="addChecklistItem(cl, $event)"
                                            class="flex items-center gap-2.5 p-2 bg-white dark:bg-slate-900/60 rounded-xl border border-slate-200/80 dark:border-slate-700/60 focus-within:border-blue-500 focus-within:ring-1 focus-within:ring-blue-500 transition"
                                            :id="'cl-form-' + cIndex">
                                            <svg class="w-4 h-4 text-slate-400 shrink-0" fill="none"
                                                stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M12 4v16m8-8H4" />
                                            </svg>
                                            <input type="text" name="item_title"
                                                placeholder="Add new checklist item (Press Enter)..."
                                                class="flex-1 text-xs sm:text-sm bg-transparent border-0 border-none outline-none focus:outline-none focus:ring-0 focus:border-0 focus:shadow-none p-0 text-slate-700 dark:text-slate-200 placeholder:text-slate-400 dark:placeholder:text-slate-500">
                                        </form>
                                    </div>
                                </div>
                            </template>

                            {{-- When No Checklist Exists (State 1) --}}
                            <template x-if="!task.checklists || task.checklists.length === 0">
                                <div
                                    class="p-6 border-2 border-dashed border-slate-200 dark:border-slate-800 rounded-2xl text-center space-y-2">
                                    <p class="text-xs text-slate-400">No checklist groups or Definition of Done created
                                        for this task yet.</p>
                                    <button type="button" @click="isCreatingChecklistGroup = true"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-600 rounded-lg text-xs font-semibold hover:bg-blue-100 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 4v16m8-8H4" />
                                        </svg>
                                        Create Definition of Done
                                    </button>
                                </div>
                            </template>
                        </div>

                        {{-- Divider --}}
                        <div class="border-t border-slate-100 dark:border-slate-800"></div>

                        {{-- Notes Section (Rich Text / Markdown Editor) --}}
                        <div class="space-y-2">
                            <div class="flex items-center justify-between">
                                <label class="block text-sm font-bold text-slate-800 dark:text-slate-200">Notes &
                                    Description</label>
                                {{-- Write / Preview Switcher --}}
                                <div
                                    class="flex items-center bg-white dark:bg-slate-850 border border-slate-200 dark:border-slate-700 p-0.5 rounded-lg text-xs font-semibold">
                                    <button type="button" @click="noteTab = 'write'"
                                        class="px-3 py-1 rounded-md transition"
                                        :class="noteTab === 'write' ? 'bg-slate-100 dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                                        Write
                                    </button>
                                    <button type="button" @click="noteTab = 'preview'"
                                        class="px-3 py-1 rounded-md transition"
                                        :class="noteTab === 'preview' ? 'bg-slate-100 dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-2xs font-bold' : 'text-slate-500 hover:text-slate-800 dark:hover:text-slate-200'">
                                        Preview
                                    </button>
                                </div>
                            </div>

                            {{-- Editor Container --}}
                            <div
                                class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden bg-white dark:bg-slate-850 focus-within:ring-2 focus-within:ring-blue-500/20 focus-within:border-blue-500 transition">
                                {{-- Formatting Toolbar (Pure White Header, No Grey) --}}
                                <div x-show="noteTab === 'write'"
                                    class="flex items-center gap-1 p-2 bg-white dark:bg-slate-900 border-b border-slate-100 dark:border-slate-800 flex-wrap">
                                    <button type="button" @click="insertNoteFormat('**', '**', 'bold text')"
                                        title="Bold (**text**)"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs transition">
                                        B
                                    </button>
                                    <button type="button" @click="insertNoteFormat('*', '*', 'italic text')"
                                        title="Italic (*text*)"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center italic font-serif text-xs transition">
                                        I
                                    </button>
                                    <button type="button" @click="insertNoteFormat('## ', '', 'Section Title')"
                                        title="Heading (## )"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-xs transition">
                                        H2
                                    </button>
                                    <span class="w-px h-4 bg-slate-200 dark:bg-slate-700 mx-1"></span>
                                    <button type="button" @click="insertNoteFormat('- ', '', 'Bullet item')"
                                        title="Bullet List (- )"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M4 6h16M4 12h16M4 18h16" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="insertNoteFormat('1. ', '', 'Numbered item')"
                                        title="Numbered List (1. )"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-bold text-[11px] transition">
                                        1.
                                    </button>
                                    <button type="button" @click="insertNoteFormat('- [ ] ', '', 'Task item')"
                                        title="Checklist (- [ ] )"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                    </button>
                                    <button type="button" @click="insertNoteFormat('> ', '', 'Important quote...')"
                                        title="Quote (> )"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-serif text-xs transition">
                                        ”
                                    </button>
                                    <button type="button" @click="insertNoteFormat('`', '`', 'code')"
                                        title="Inline Code (`code`)"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center font-mono text-xs transition">
                                        &lt;/&gt;
                                    </button>
                                    <button type="button" @click="insertNoteFormat('[', '](https://)', 'Link Text')"
                                        title="Link ([Title](url))"
                                        class="w-7 h-7 rounded hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 flex items-center justify-center transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Write Textarea --}}
                                <div x-show="noteTab === 'write'">
                                    <textarea x-ref="noteTextarea" x-model="task.description" @input="markDirty()"
                                        rows="4"
                                        placeholder="Add detailed notes, instructions, or markdown checklist here..."
                                        class="w-full text-xs sm:text-sm text-slate-700 dark:text-slate-200 bg-transparent border-0 p-3.5 focus:outline-none focus:ring-0 leading-relaxed placeholder:text-slate-400 resize-y min-h-[110px]"></textarea>
                                </div>

                                {{-- Preview Render --}}
                                <div x-show="noteTab === 'preview'"
                                    class="p-4 min-h-[110px] bg-white dark:bg-slate-900 overflow-y-auto">
                                    <div class="prose dark:prose-invert max-w-none text-xs sm:text-sm leading-relaxed"
                                        x-html="renderNoteMarkdown(task.description)"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Completion Notes (when done) --}}
                        <div x-show="task.status === 'done'"
                            class="p-4 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl space-y-1.5">
                            <label class="block text-xs font-semibold text-emerald-800 dark:text-emerald-400">Completion
                                Notes</label>
                            <textarea x-model="task.completion_notes" @input="markDirty()" rows="2"
                                placeholder="Summary of completed work or handover notes..."
                                class="w-full text-xs text-slate-800 dark:text-slate-200 bg-white dark:bg-slate-900 border border-emerald-300 dark:border-emerald-700 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                        </div>
                    </div>

                    {{-- ========== TAB 2: ATTACHMENTS (Card 05 Panel UI) ========== --}}
                    <div x-show="activeTab === 'attachments'" class="px-6 pb-6 space-y-5">

                        {{-- Top Segmented Switcher --}}
                        <div class="flex items-center gap-1 bg-slate-100 dark:bg-slate-800 p-1 rounded-xl w-fit">
                            <button type="button" @click="attachmentMode = 'file'"
                                class="flex items-center gap-2 px-4 py-1.5 text-xs font-semibold rounded-lg transition shadow-2xs"
                                :class="attachmentMode === 'file' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                                </svg>
                                Upload File
                            </button>
                            <button type="button" @click="attachmentMode = 'link'"
                                class="flex items-center gap-2 px-4 py-1.5 text-xs font-semibold rounded-lg transition shadow-2xs"
                                :class="attachmentMode === 'link' ? 'bg-white dark:bg-slate-700 text-blue-600 dark:text-blue-400 shadow-sm' : 'text-slate-500 hover:text-slate-700'">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                </svg>
                                Link / URL
                            </button>
                        </div>

                        {{-- Drag & Drop File Area --}}
                        <div x-show="attachmentMode === 'file'"
                            class="border-2 border-dashed border-blue-200 dark:border-blue-800/60 bg-blue-50/40 dark:bg-blue-950/20 rounded-2xl p-6 text-center transition-all hover:bg-blue-50/60">
                            <svg class="w-9 h-9 mx-auto text-blue-500 mb-2" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.6"
                                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                            </svg>
                            <p class="text-xs sm:text-sm font-medium text-slate-700 dark:text-slate-200">
                                <label class="text-blue-600 hover:text-blue-700 font-bold underline cursor-pointer">
                                    Choose from computer
                                    <input type="file" class="hidden" x-ref="attachmentFileInput"
                                        @change="uploadAttachmentFile($event)">
                                </label>
                                <span>or drag & drop files</span>
                            </p>
                            <p class="text-[11px] text-slate-400 mt-1">Max 25MB • PDF, PNG, SQL, ZIP</p>
                        </div>

                        {{-- Add Web Link Section --}}
                        <div x-show="attachmentMode === 'link'"
                            class="p-4 bg-slate-50 dark:bg-slate-800/50 rounded-2xl border border-slate-200 dark:border-slate-700 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">OR ADD WEB
                                    LINK</span>
                                <span class="text-[10px] text-slate-400">Web / Cloud Link</span>
                            </div>
                            <input type="url" x-model="linkUrl" placeholder="https://figma.com/file/..."
                                class="w-full text-xs px-3.5 py-2.5 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-1 focus:ring-blue-500">
                            <div class="flex items-center gap-2">
                                <input type="text" x-model="linkName" placeholder="Link Title / Name..."
                                    class="flex-1 text-xs px-3.5 py-2 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl focus:ring-1 focus:ring-blue-500">
                                <button type="button" @click="submitAttachmentLink()"
                                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-sm">
                                    + Save
                                </button>
                            </div>
                        </div>

                        {{-- Attachments List --}}
                        <div class="space-y-2.5">
                            <template x-for="(att, aIndex) in task.attachments" :key="att.id || aIndex">
                                <div
                                    class="flex items-center justify-between gap-3 p-3.5 bg-white dark:bg-slate-800/60 rounded-xl border border-slate-200/80 dark:border-slate-700 hover:border-slate-300 transition group shadow-2xs">
                                    <div class="flex items-center gap-3 min-w-0">
                                        {{-- Filetype Badge --}}
                                        <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0 font-bold text-xs"
                                            :class="att.type === 'link' ? 'bg-purple-50 text-purple-600 dark:bg-purple-950/40' : 'bg-red-50 text-red-600 dark:bg-red-950/40'">
                                            <template x-if="att.type === 'link'">
                                                <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                                </svg>
                                            </template>
                                            <template x-if="att.type === 'file'">
                                                <span class="uppercase text-[10px]"
                                                    x-text="getFileExtension(att.file_name)"></span>
                                            </template>
                                        </div>

                                        {{-- Info --}}
                                        <div class="min-w-0">
                                            <a :href="att.url || ('/storage/' + att.file_path)" target="_blank"
                                                class="text-xs font-semibold text-slate-800 dark:text-slate-100 hover:text-blue-600 dark:hover:text-blue-400 block truncate"
                                                x-text="att.file_name || att.display_name || 'Attachment'"></a>
                                            <div class="flex items-center gap-2 text-[11px] text-slate-400 mt-0.5">
                                                <span
                                                    x-text="att.type === 'link' ? ('Web Link • ' + getDomainFromUrl(att.url)) : (formatFileSize(att.file_size) + ' • Added today')"></span>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Actions --}}
                                    <div class="flex items-center gap-1.5 shrink-0">
                                        <template x-if="isImageAttachment(att)">
                                            <button type="button" @click="setAsCover(att)" title="Set as Cover"
                                                class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template x-if="att.type === 'file'">
                                            <a :href="'/storage/' + att.file_path" download
                                                class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                                </svg>
                                            </a>
                                        </template>
                                        <template x-if="att.type === 'link'">
                                            <a :href="att.url" target="_blank"
                                                class="p-1.5 text-slate-400 hover:text-blue-600 rounded-lg hover:bg-slate-50 transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round"
                                                        stroke-width="2"
                                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                                </svg>
                                            </a>
                                        </template>
                                        <button type="button" @click="confirmDeleteAttachment(att, aIndex)"
                                            title="Delete"
                                            class="p-1.5 text-slate-400 hover:text-red-500 rounded-lg hover:bg-slate-50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                            </template>

                            {{-- Empty State --}}
                            <template x-if="!task.attachments || task.attachments.length === 0">
                                <div class="text-center py-8 text-slate-400 text-xs">
                                    No attachments or links for this task yet.
                                </div>
                            </template>
                        </div>

                        {{-- Bottom Summary Bar --}}
                        <div
                            class="flex items-center justify-between pt-3 border-t border-slate-100 dark:border-slate-800 text-xs">
                            <span class="text-slate-500 font-medium" x-text="attachmentSummaryText"></span>
                            <span class="text-blue-600 font-semibold flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
                                Auto-saved to Cloud
                            </span>
                        </div>
                    </div>
                </div>

                {{-- ========== RIGHT COLUMN: Activity & Comments ========== --}}
                <div x-show="rightPanelOpen" x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 translate-x-4"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    class="w-80 lg:w-[350px] border-l border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-900/60 flex flex-col shrink-0">

                    {{-- Header --}}
                    <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <h4 class="text-xs font-bold text-slate-800 dark:text-slate-200 uppercase tracking-wider">
                                ACTIVITY & COMMENTS</h4>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                            x-text="(task.comments || []).length + (task.activities || []).length"></span>
                    </div>

                    {{-- Filter Tabs (Segmented) --}}
                    <div class="p-3 border-b border-slate-100 dark:border-slate-800">
                        <div class="grid grid-cols-3 gap-1 bg-slate-200/70 dark:bg-slate-800 p-1 rounded-xl">
                            <button type="button" @click="activityTab = 'all'"
                                class="py-1 text-xs font-semibold rounded-lg transition"
                                :class="activityTab === 'all' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800'">
                                All
                            </button>
                            <button type="button" @click="activityTab = 'comments'"
                                class="py-1 text-xs font-semibold rounded-lg transition"
                                :class="activityTab === 'comments' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800'">
                                Comments
                            </button>
                            <button type="button" @click="activityTab = 'log'"
                                class="py-1 text-xs font-semibold rounded-lg transition"
                                :class="activityTab === 'log' ? 'bg-white dark:bg-slate-700 text-slate-900 dark:text-white shadow-2xs' : 'text-slate-500 hover:text-slate-800'">
                                Activity Log
                            </button>
                        </div>
                    </div>

                    {{-- Activity / Comments Feed --}}
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs">

                        {{-- Feed Items --}}
                        <template x-for="item in visibleFeedItems" :key="item.feedId">
                            <div>
                                {{-- Activity Log Row with Descriptive Context --}}
                                <template x-if="item.feedType === 'activity'">
                                    <div class="flex items-start gap-2.5 py-1">
                                        <div class="w-6 h-6 rounded-full text-white flex items-center justify-center font-bold text-[9px] shrink-0"
                                            :style="'background-color:' + getAvatarColor(item.user_name)"
                                            x-text="getInitials(item.user_name)"></div>
                                        <div class="flex-1 min-w-0">
                                            <p class="text-slate-700 dark:text-slate-300 leading-snug">
                                                <span class="font-bold text-slate-900 dark:text-white"
                                                    x-text="item.user_name"></span>
                                                <span
                                                    x-html="' ' + (item.formatted_text || item.description || 'updated this task.')"></span>
                                            </p>
                                            <span class="text-[10px] text-slate-400 mt-0.5 block"
                                                x-text="formatRelativeTime(item.created_at)"></span>
                                        </div>
                                    </div>
                                </template>

                                {{-- Comment Card with Reactions --}}
                                <template x-if="item.feedType === 'comment'">
                                    <div class="flex items-start gap-2.5">
                                        <div class="w-8 h-8 rounded-full text-white flex items-center justify-center font-bold text-[10px] shrink-0 shadow-sm"
                                            :style="'background-color:' + getAvatarColor(item.user ? item.user.name : 'U')"
                                            x-text="getInitials(item.user ? item.user.name : 'U')"></div>
                                        <div class="flex-1 space-y-1.5 min-w-0">
                                            <div class="flex items-center justify-between gap-1">
                                                <span class="font-bold text-slate-800 dark:text-slate-200"
                                                    x-text="item.user ? item.user.name : 'User'"></span>
                                                <span class="text-[10px] text-slate-400 whitespace-nowrap"
                                                    x-text="formatRelativeTime(item.created_at)"></span>
                                            </div>
                                            <div
                                                class="bg-white dark:bg-slate-800 rounded-xl p-3 border border-slate-200/80 dark:border-slate-700 shadow-2xs">
                                                <p class="text-slate-700 dark:text-slate-300 leading-relaxed whitespace-pre-wrap text-xs"
                                                    x-html="renderCommentBody(item.body)"></p>
                                            </div>

                                            {{-- Reactions Bar & Reply --}}
                                            <div
                                                class="flex items-center gap-2 text-xs text-slate-400 pl-0.5 flex-wrap">
                                                {{-- Quick Reactions --}}
                                                <div class="flex items-center gap-1">
                                                    <template x-for="em in ['👍', '❤️', '🔥', '🎉', '🚀']" :key="em">
                                                        <button type="button" @click="toggleCommentReaction(item, em)"
                                                            class="px-1.5 py-0.5 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-xs transition"
                                                            :class="hasReaction(item, em) ? 'bg-blue-50 dark:bg-blue-950/60 border border-blue-200 dark:border-blue-800' : ''">
                                                            <span x-text="em"></span>
                                                            <span x-show="getReactionCount(item, em) > 0"
                                                                class="text-[10px] font-bold text-slate-600 dark:text-slate-300 ml-0.5"
                                                                x-text="getReactionCount(item, em)"></span>
                                                        </button>
                                                    </template>
                                                </div>

                                                <span>•</span>
                                                <button type="button" @click="replyComment(item)"
                                                    class="text-xs font-medium text-slate-500 hover:text-blue-600 transition">Reply</button>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>

                        {{-- Empty State (State 1) --}}
                        <template x-if="visibleFeedItems.length === 0">
                            <div
                                class="flex flex-col items-center justify-center py-14 text-slate-400 text-center px-4">
                                <div
                                    class="w-12 h-12 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center mb-3">
                                    <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                                    </svg>
                                </div>
                                <p class="font-bold text-sm text-slate-700 dark:text-slate-300 mb-1">No activity yet
                                </p>
                                <p class="text-xs text-slate-400 leading-relaxed">Be the first to leave a comment or
                                    instructions for your team on this task.</p>
                            </div>
                        </template>
                    </div>

                    {{-- Add Comment Box with Mentions & Formatting --}}
                    <div class="p-3.5 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-850"
                        x-data="{ mentionMenuOpen: false }">
                        <form @submit.prevent="postComment($event)" class="space-y-2.5">
                            <textarea name="body" x-ref="commentInput" rows="3" required
                                placeholder="Write a comment or response..."
                                class="w-full text-xs bg-slate-50 dark:bg-slate-900 border border-slate-200 dark:border-slate-700 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition placeholder:text-slate-400 resize-none"></textarea>

                            <div class="flex items-center justify-between relative">
                                <div class="flex items-center gap-1.5">
                                    {{-- Bold Button --}}
                                    <button type="button" @click="insertFormat('**', '**')"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 flex items-center justify-center font-bold text-xs transition"
                                        title="Bold (**text**)">
                                        B
                                    </button>

                                    {{-- Mention Button with Popover --}}
                                    <div class="relative">
                                        <button type="button" @click="mentionMenuOpen = !mentionMenuOpen"
                                            class="w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 flex items-center justify-center font-bold text-xs transition"
                                            title="Mention team member">
                                            @
                                        </button>
                                        <div x-show="mentionMenuOpen" @click.outside="mentionMenuOpen = false" x-cloak
                                            class="absolute left-0 bottom-full mb-2 w-48 bg-white dark:bg-slate-800 rounded-xl shadow-xl border border-slate-100 dark:border-slate-700 py-1 z-50 max-h-48 overflow-y-auto">
                                            <div
                                                class="px-3 py-1 text-[10px] font-bold text-slate-400 uppercase tracking-widest">
                                                Select Member</div>
                                            <template x-for="user in assignableUsers" :key="user.id">
                                                <button type="button"
                                                    @click="insertMention(user.name); mentionMenuOpen = false"
                                                    class="w-full text-left px-3 py-1.5 text-xs flex items-center gap-2 hover:bg-slate-50 dark:hover:bg-slate-700/50 transition">
                                                    <span
                                                        class="w-4 h-4 rounded-full text-white text-[9px] flex items-center justify-center font-bold"
                                                        :style="'background-color:' + getAvatarColor(user.name)"
                                                        x-text="getInitials(user.name)"></span>
                                                    <span class="truncate" x-text="user.name"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </div>

                                    {{-- Attach Button --}}
                                    <button type="button"
                                        @click="activeTab = 'attachments'; $refs.attachmentFileInput?.click()"
                                        class="w-7 h-7 rounded-lg hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-500 flex items-center justify-center transition"
                                        title="Attach File">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13" />
                                        </svg>
                                    </button>
                                </div>

                                <button type="submit" :disabled="commentPosting"
                                    class="px-4 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl text-xs transition disabled:opacity-50 shadow-sm">
                                    <span x-text="commentPosting ? 'Sending...' : 'Send Comment'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ============================================================
            MODAL FOOTER
            ============================================================ --}}
            <div
                class="px-6 py-3.5 border-t border-slate-100 dark:border-slate-800 bg-white dark:bg-slate-900 flex items-center justify-between shrink-0">
                <div>
                    @if(!auth()->user()->hasRole('client'))
                        <button type="button" @click="deleteConfirmOpen = true"
                            class="text-xs text-red-500 hover:text-red-700 font-semibold flex items-center gap-1.5 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Task
                        </button>
                    @endif
                </div>

                <div class="flex items-center gap-3">
                    <button type="button" @click="attemptClose()"
                        class="px-5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 dark:hover:bg-slate-700 transition shadow-2xs">
                        Cancel
                    </button>

                    <button type="button" @click="saveTask()" :disabled="saving"
                        class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-xl shadow-sm transition flex items-center gap-2 disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                            </circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                        <svg x-show="!saving" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5"
                                d="M5 13l4 4L19 7" />
                        </svg>
                        <span x-text="saving ? 'Saving...' : 'Save Changes'"></span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CUSTOM MODAL: UNSAVED CHANGES CONFIRMATION
    ============================================================ --}}
    <div x-show="unsavedConfirmOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="unsavedConfirmOpen = false"></div>
        <div
            class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10 p-6 space-y-4">
            <div class="flex items-start gap-3.5">
                <div
                    class="w-11 h-11 rounded-full bg-amber-100 dark:bg-amber-950/50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Unsaved Changes</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        You have unsaved changes. Are you sure you want to discard them and close?
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="unsavedConfirmOpen = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                    Keep Editing
                </button>
                <button type="button" @click="unsavedConfirmOpen = false; forceClose()"
                    class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm">
                    Discard Changes
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CUSTOM MODAL: DELETE CHECKLIST GROUP CONFIRMATION
    ============================================================ --}}
    <div x-show="deleteChecklistGroupConfirmOpen" x-cloak
        class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="deleteChecklistGroupConfirmOpen = false">
        </div>
        <div
            class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10 p-6 space-y-4">
            <div class="flex items-start gap-3.5">
                <div
                    class="w-11 h-11 rounded-full bg-red-100 dark:bg-red-950/50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Delete This Checklist
                        Group?</h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        All items within this checklist group will be permanently deleted.
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="deleteChecklistGroupConfirmOpen = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="button" @click="executeDeleteChecklistGroup()"
                    class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm">
                    Yes, Delete Group
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CUSTOM MODAL: DELETE ATTACHMENT CONFIRMATION
    ============================================================ --}}
    <div x-show="deleteAttachmentConfirmOpen" x-cloak class="fixed inset-0 z-[70] flex items-center justify-center p-4">
        <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="deleteAttachmentConfirmOpen = false"></div>
        <div
            class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10 p-6 space-y-4">
            <div class="flex items-start gap-3.5">
                <div
                    class="w-11 h-11 rounded-full bg-red-100 dark:bg-red-950/50 flex items-center justify-center shrink-0">
                    <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Delete This Attachment?
                    </h3>
                    <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                        This attachment will be permanently removed from the task.
                    </p>
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 pt-2">
                <button type="button" @click="deleteAttachmentConfirmOpen = false"
                    class="px-4 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                    Cancel
                </button>
                <button type="button" @click="executeDeleteAttachment()"
                    class="px-4 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm">
                    Yes, Delete Attachment
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
    CARD 06: KONFIRMASI HAPUS TASK MODAL (Destructive)
    ============================================================ --}}
    <div x-show="deleteConfirmOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4">
        {{-- Sub-backdrop --}}
        <div x-show="deleteConfirmOpen" x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
            class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm" @click="deleteConfirmOpen = false"></div>

        {{-- Confirmation Dialog --}}
        <div x-show="deleteConfirmOpen" x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
            class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10">

            <div class="p-6 space-y-4">
                {{-- Warning Triangle Icon + Title --}}
                <div class="flex items-start gap-3.5">
                    <div
                        class="w-11 h-11 rounded-full bg-red-100 dark:bg-red-950/50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Delete This Task?</h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            This action will permanently delete the Definition of Done checklist,
                            <span x-text="(task.attachments || []).length"></span> attachments, and all discussion
                            history.
                        </p>
                    </div>
                </div>

                {{-- Pink/Red Checkbox Confirmation Box --}}
                <label
                    class="flex items-start gap-2.5 p-3.5 bg-red-50/80 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-xl cursor-pointer">
                    <input type="checkbox" x-model="deleteConfirmChecked"
                        class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-red-300 mt-0.5">
                    <span class="text-xs text-red-800 dark:text-red-300 leading-relaxed font-medium">
                        I understand that this action is permanent and cannot be undone.
                    </span>
                </label>
            </div>

            {{-- Action Buttons --}}
            <div
                class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end gap-2.5">
                <button type="button" @click="deleteConfirmOpen = false; deleteConfirmChecked = false"
                    class="px-5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition shadow-2xs">
                    Cancel
                </button>
                <button type="button" @click="if(deleteConfirmChecked) { deleteTask(); deleteConfirmOpen = false; }"
                    :disabled="!deleteConfirmChecked"
                    class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    Yes, Delete Task
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    function taskModalComponent() {
        return {
            isOpen: false,
            loading: false,
            saving: false,
            isDirty: false,
            rightPanelOpen: true,
            activeTab: 'details',
            activityTab: 'all',
            meetingCreating: false,
            commentPosting: false,

            // Confirmation Modals State
            deleteConfirmOpen: false,
            deleteConfirmChecked: false,
            unsavedConfirmOpen: false,
            deleteChecklistGroupConfirmOpen: false,
            targetChecklistGroup: null,
            targetChecklistGroupIndex: null,
            deleteAttachmentConfirmOpen: false,
            targetAttachment: null,
            targetAttachmentIndex: null,

            // Toast State
            toast: { show: false, message: '', type: 'success' },

            // Data Models
            task: {},
            columns: @json($columns ?? []),
            projectLabels: @json($projectLabels ?? []),
            assignableUsers: @json($assignableUsers ?? []),
            milestones: @json($milestones ?? []),
            columnCounts: {},

            // Bucket Popover
            bucketSearch: '',
            isCreatingBucket: false,
            newBucketName: '',
            newBucketColor: 'blue',

            // Recurring Rule Popover (Default 'none' = Does not repeat)
            recurringFrequency: 'none',
            recurringDays: ['Mon', 'Wed', 'Fri'],
            recurringEndDate: '',

            // Group Checklist State
            isCreatingChecklistGroup: false,
            newChecklistGroupTitle: '',

            // Attachments Panel
            attachmentMode: 'file',
            linkUrl: '',
            linkName: '',

            // Notes Editor Mode
            noteTab: 'write',

            // Options
            statusOptions: [
                { value: 'todo', label: 'To Do', dotClass: 'bg-slate-400' },
                { value: 'in_progress', label: 'In Progress', dotClass: 'bg-blue-500' },
                { value: 'review', label: 'Review', dotClass: 'bg-amber-500' },
                { value: 'done', label: 'Completed', dotClass: 'bg-emerald-500' },
            ],

            priorityOptions: [
                { value: 'urgent', label: 'Urgent', color: '#ef4444', icon: '<svg class="w-4 h-4 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7M5 9l7-7 7 7"/></svg>' },
                { value: 'high', label: 'High', color: '#f97316', icon: '<svg class="w-4 h-4 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>' },
                { value: 'medium', label: 'Medium', color: '#f59e0b', icon: '<svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 9h16M4 15h16"/></svg>' },
                { value: 'low', label: 'Low', color: '#10b981', icon: '<svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>' },
            ],

            get currentColumn() {
                return this.columns.find(c => c.id === this.task.board_column_id);
            },

            get coverUrl() {
                if (!this.task.cover_image_path) return '';
                return '/storage/' + this.task.cover_image_path;
            },

            get filteredColumns() {
                if (!this.bucketSearch.trim()) return this.columns;
                const q = this.bucketSearch.toLowerCase();
                return this.columns.filter(c => c.name.toLowerCase().includes(q));
            },

            get recurringSummaryText() {
                if (!this.task.recurring_definition_id || this.recurringFrequency === 'none') {
                    return 'Does not repeat';
                }
                if (this.recurringFrequency === 'weekly') {
                    return `Weekly (${this.recurringDays.join(', ')})`;
                }
                if (this.recurringFrequency === 'daily') return 'Daily';
                if (this.recurringFrequency === 'monthly') return 'Monthly';
                if (this.recurringFrequency === 'custom') return 'Custom';
                return 'Recurring (Active)';
            },

            get attachmentSummaryText() {
                const atts = this.task.attachments || [];
                const files = atts.filter(a => a.type === 'file');
                const links = atts.filter(a => a.type === 'link');
                const totalBytes = files.reduce((acc, f) => acc + (f.file_size || 0), 0);
                return `Total: ${files.length} File${files.length !== 1 ? 's' : ''} (${this.formatFileSize(totalBytes)}) + ${links.length} Link${links.length !== 1 ? 's' : ''}`;
            },

            get visibleFeedItems() {
                const comments = (this.task.comments || []).map(c => ({ ...c, feedType: 'comment', feedId: 'c_' + c.id }));
                const activities = (this.task.activities || []).map(a => ({ ...a, feedType: 'activity', feedId: 'a_' + a.id }));

                let items = [];
                if (this.activityTab === 'comments') {
                    items = comments;
                } else if (this.activityTab === 'log') {
                    items = activities;
                } else {
                    items = [...comments, ...activities];
                }

                return items.sort((a, b) => new Date(b.created_at) - new Date(a.created_at));
            },

            initModal() {
                window.openTaskModal = (taskId) => {
                    this.openModal(taskId);
                };
            },

            showToast(message, type = 'success') {
                this.toast.message = message;
                this.toast.type = type;
                this.toast.show = true;
                setTimeout(() => {
                    this.toast.show = false;
                }, 2500);
            },

            handleEscape() {
                if (this.deleteConfirmOpen) {
                    this.deleteConfirmOpen = false;
                    return;
                }
                if (this.unsavedConfirmOpen) {
                    this.unsavedConfirmOpen = false;
                    return;
                }
                if (this.deleteChecklistGroupConfirmOpen) {
                    this.deleteChecklistGroupConfirmOpen = false;
                    return;
                }
                if (this.deleteAttachmentConfirmOpen) {
                    this.deleteAttachmentConfirmOpen = false;
                    return;
                }
                this.attemptClose();
            },

            // --- Status & Priority ---
            statusLabel() {
                const opt = this.statusOptions.find(s => s.value === this.task.status);
                return opt ? opt.label : 'Not Started';
            },
            statusDotClass() {
                const opt = this.statusOptions.find(s => s.value === this.task.status);
                return opt ? opt.dotClass : 'bg-slate-400';
            },
            statusPillClass() {
                const map = {
                    'todo': 'bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700',
                    'in_progress': 'bg-blue-50 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 border-blue-200 dark:border-blue-800',
                    'review': 'bg-amber-50 dark:bg-amber-950/40 text-amber-700 dark:text-amber-300 border-amber-200 dark:border-amber-800',
                    'done': 'bg-emerald-50 dark:bg-emerald-950/40 text-emerald-700 dark:text-emerald-300 border-emerald-200 dark:border-emerald-800',
                };
                return map[this.task.status] || map['todo'];
            },
            priorityLabel() {
                const opt = this.priorityOptions.find(p => p.value === this.task.priority);
                return opt ? opt.label : 'Select Priority';
            },
            priorityIcon() {
                const opt = this.priorityOptions.find(p => p.value === this.task.priority);
                return opt ? opt.icon : '<span class="w-4 h-4 rounded bg-slate-100 text-slate-400 flex items-center justify-center text-[10px]">—</span>';
            },
            priorityTriggerClass() {
                if (this.task.priority === 'urgent') return 'border-red-300 bg-red-50/30 dark:border-red-800';
                if (this.task.priority === 'high') return 'border-amber-300 bg-amber-50/30 dark:border-amber-800';
                return 'border-slate-200 dark:border-slate-700';
            },
            setStatus(status) {
                this.task.status = status;
                const col = this.columns.find(c => c.slug === status);
                if (col) this.task.board_column_id = col.id;
                this.markDirty();
            },
            setBucket(columnId) {
                this.task.board_column_id = columnId;
                const col = this.columns.find(c => c.id === columnId);
                if (col && ['todo', 'in_progress', 'review', 'done'].includes(col.slug)) {
                    this.task.status = col.slug;
                }
                this.markDirty();
            },
            getColumnTaskCount(columnId) {
                return this.columnCounts[columnId] || 0;
            },
            isOverdue() {
                if (!this.task.due_date) return false;
                return new Date(this.task.due_date) < new Date() && this.task.status !== 'done';
            },

            toggleComplete() {
                this.task.status = (this.task.status === 'done') ? 'todo' : 'done';
                this.setStatus(this.task.status);
            },

            createCustomStatus(name, color) {
                if (!name.trim()) return;
                const slug = name.trim().toLowerCase().replace(/\s+/g, '_');
                const dotClass = this.getDotBg(color);
                this.statusOptions.push({
                    value: slug,
                    label: name.trim(),
                    dotClass: dotClass
                });
                this.setStatus(slug);
                this.showToast(`Status "${name.trim()}" added`);
            },

            getDotBg(color) {
                const map = {
                    indigo: 'bg-indigo-500',
                    purple: 'bg-purple-500',
                    blue: 'bg-blue-500',
                    teal: 'bg-teal-500',
                    green: 'bg-emerald-500',
                    amber: 'bg-amber-500',
                    orange: 'bg-orange-500',
                    red: 'bg-red-500'
                };
                return map[color] || 'bg-blue-500';
            },

            // --- Avatar Helpers ---
            getAvatarColor(name) {
                const colors = ['#2563eb', '#dc2626', '#059669', '#d97706', '#7c3aed', '#db2777', '#0891b2', '#ea580c'];
                let hash = 0;
                for (let i = 0; i < (name || '').length; i++) hash = name.charCodeAt(i) + ((hash << 5) - hash);
                return colors[Math.abs(hash) % colors.length];
            },
            getInitials(name) {
                if (!name) return 'U';
                const parts = name.trim().split(' ');
                if (parts.length >= 2) return (parts[0][0] + parts[1][0]).toUpperCase();
                return name.substring(0, 2).toUpperCase();
            },

            // --- Open / Close ---
            async openModal(taskId) {
                this.isOpen = true;
                this.loading = true;
                this.isDirty = false;
                this.deleteConfirmOpen = false;
                this.deleteConfirmChecked = false;
                this.unsavedConfirmOpen = false;
                this.deleteChecklistGroupConfirmOpen = false;
                this.deleteAttachmentConfirmOpen = false;
                this.isCreatingChecklistGroup = false;
                this.activeTab = 'details';
                this.activityTab = 'all';
                this.bucketSearch = '';

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${taskId}/detail`, {
                        headers: { 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error('Failed to fetch task data');
                    const data = await res.json();
                    this.task = data.task;
                    if (this.task.start_date) {
                        this.task.start_date = (typeof this.task.start_date === 'string' && this.task.start_date.includes('T')) ? this.task.start_date.split('T')[0] : this.task.start_date;
                    }
                    if (this.task.due_date) {
                        this.task.due_date = (typeof this.task.due_date === 'string' && this.task.due_date.includes('T')) ? this.task.due_date.split('T')[0] : this.task.due_date;
                    }
                    this.columnCounts = data.columnCounts || {};
                    if (!this.task.members) this.task.members = [];
                    if (!this.task.labels) this.task.labels = [];
                    if (!this.task.checklists) this.task.checklists = [];
                    if (!this.task.attachments) this.task.attachments = [];
                    if (!this.task.comments) this.task.comments = [];
                    this.task.activities = data.activities || [];

                    // Load recurrence state (Default 'none' if not recurring)
                    if (this.task.recurring_definition_id && this.task.recurring_definition) {
                        const rd = this.task.recurring_definition;
                        this.recurringFrequency = rd.frequency || 'weekly';
                        if (rd.day_of_week !== null && rd.day_of_week !== undefined) {
                            const dayMap = { 0: 'Sun', 1: 'Mon', 2: 'Tue', 3: 'Wed', 4: 'Thu', 5: 'Fri', 6: 'Sat' };
                            this.recurringDays = [dayMap[rd.day_of_week] || 'Mon'];
                        }
                    } else {
                        this.recurringFrequency = 'none';
                        this.recurringDays = ['Mon'];
                        this.recurringEndDate = '';
                    }
                } catch (err) {
                    console.error(err);
                    this.showToast('Failed to load task', 'error');
                    this.isOpen = false;
                } finally {
                    this.loading = false;
                }
            },

            attemptClose() {
                if (this.isDirty) {
                    this.unsavedConfirmOpen = true;
                } else {
                    this.forceClose();
                }
            },

            forceClose() {
                this.isOpen = false;
                this.isDirty = false;
                this.task = {};
            },

            markDirty() {
                this.isDirty = true;
            },

            // --- Members ---
            isMemberAssigned(userId) {
                return (this.task.members || []).some(m => m.id === userId);
            },
            toggleMember(user) {
                if (this.isMemberAssigned(user.id)) {
                    this.removeMember(user.id);
                } else {
                    this.task.members.push(user);
                    this.markDirty();
                }
            },
            removeMember(userId) {
                this.task.members = this.task.members.filter(m => m.id !== userId);
                this.markDirty();
            },

            // --- Labels ---
            isLabelAttached(labelId) {
                return (this.task.labels || []).some(l => l.id === labelId);
            },
            toggleLabel(label) {
                if (this.isLabelAttached(label.id)) {
                    this.removeLabel(label.id);
                } else {
                    this.task.labels.push(label);
                    this.markDirty();
                }
            },
            removeLabel(labelId) {
                this.task.labels = this.task.labels.filter(l => l.id !== labelId);
                this.markDirty();
            },
            async createNewLabel(name, color) {
                if (!name.trim()) return;
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/labels`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ name: name.trim(), color })
                    });
                    if (res.ok) {
                        const newLabel = await res.json();
                        this.projectLabels.push(newLabel);
                        this.task.labels.push(newLabel);
                        this.markDirty();
                        this.showToast('Label added successfully');
                    }
                } catch (err) {
                    console.error(err);
                }
            },
            labelClasses(color) {
                const map = {
                    blue: { badge: 'bg-blue-100 text-blue-800 dark:bg-blue-950 dark:text-blue-300', dot: 'bg-blue-500' },
                    green: { badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950 dark:text-emerald-300', dot: 'bg-emerald-500' },
                    red: { badge: 'bg-red-100 text-red-800 dark:bg-red-950 dark:text-red-300', dot: 'bg-red-500' },
                    yellow: { badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-950 dark:text-yellow-300', dot: 'bg-yellow-500' },
                    orange: { badge: 'bg-orange-100 text-orange-800 dark:bg-orange-950 dark:text-orange-300', dot: 'bg-orange-500' },
                    purple: { badge: 'bg-purple-100 text-purple-800 dark:bg-purple-950 dark:text-purple-300', dot: 'bg-purple-500' },
                    teal: { badge: 'bg-teal-100 text-teal-800 dark:bg-teal-950 dark:text-teal-300', dot: 'bg-teal-500' },
                };
                return map[color] || { badge: 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300', dot: 'bg-slate-400' };
            },

            // --- Bucket Creation ---
            async createNewBucket() {
                if (!this.newBucketName.trim()) return;
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/board-columns`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ name: this.newBucketName.trim(), color: this.newBucketColor })
                    });
                    if (res.ok) {
                        const col = await res.json();
                        this.columns.push(col);
                        this.task.board_column_id = col.id;
                        this.isCreatingBucket = false;
                        this.newBucketName = '';
                        this.markDirty();
                        this.showToast('Bucket added successfully');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            // --- Recurrence ---
            toggleRecurringDay(day) {
                if (this.recurringDays.includes(day)) {
                    this.recurringDays = this.recurringDays.filter(d => d !== day);
                } else {
                    this.recurringDays.push(day);
                }
            },
            saveRecurringRule() {
                if (this.recurringFrequency === 'none') {
                    this.task.recurring_definition_id = null;
                    this.showToast('Recurring rule disabled');
                } else {
                    this.task.recurring_definition_id = this.task.recurring_definition_id || 1;
                    this.showToast('Recurring rule updated');
                }
                this.markDirty();
            },
            deleteRecurringRule() {
                this.recurringFrequency = 'none';
                this.task.recurring_definition_id = null;
                this.markDirty();
                this.showToast('Recurring rule removed');
            },

            // --- Checklists & DoD Groups ---
            async submitNewChecklistGroup() {
                const title = (this.newChecklistGroupTitle || '').trim() || 'Definition of Done';
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ title })
                    });
                    if (res.ok) {
                        const cl = await res.json();
                        if (!cl.items) cl.items = [];
                        this.task.checklists.push(cl);
                        this.isCreatingChecklistGroup = false;
                        this.newChecklistGroupTitle = '';
                        this.showToast(`Group "${title}" created successfully`);
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            async updateChecklistGroup(cl) {
                if (!cl.id) return;
                try {
                    await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists/${cl.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ title: cl.title })
                    });
                    this.showToast('Checklist title updated');
                } catch (err) {
                    console.error(err);
                }
            },

            focusChecklistInput(index) {
                const form = document.getElementById('cl-form-' + index);
                if (form) {
                    const input = form.querySelector('input[name="item_title"]');
                    if (input) input.focus();
                }
            },

            confirmDeleteChecklistGroup(cl, index) {
                this.targetChecklistGroup = cl;
                this.targetChecklistGroupIndex = index;
                this.deleteChecklistGroupConfirmOpen = true;
            },

            async executeDeleteChecklistGroup() {
                const cl = this.targetChecklistGroup;
                const index = this.targetChecklistGroupIndex;
                this.deleteChecklistGroupConfirmOpen = false;

                if (!cl || !cl.id) {
                    if (index !== null) this.task.checklists.splice(index, 1);
                    return;
                }

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists/${cl.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        this.task.checklists.splice(index, 1);
                        this.showToast('Checklist group deleted');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            async addChecklistItem(cl, event) {
                const form = event.target.closest ? event.target.closest('form') : event.target;
                const input = form ? form.querySelector('input[name="item_title"]') : null;
                if (!input) return;
                const title = (input.value || '').trim();
                if (!title) return;

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists/${cl.id}/items`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ title })
                    });
                    if (res.ok) {
                        const item = await res.json();
                        cl.items.push(item);
                        input.value = '';
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            async toggleChecklistItem(cl, item) {
                const newState = !item.is_done;
                item.is_done = newState;
                try {
                    await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists/${cl.id}/items/${item.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ is_done: newState })
                    });
                } catch (err) {
                    item.is_done = !newState;
                    console.error(err);
                }
            },

            async deleteChecklistItem(cl, item, index) {
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists/${cl.id}/items/${item.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        cl.items.splice(index, 1);
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            getChecklistProgress(cl) {
                const items = cl.items || [];
                if (items.length === 0) return { pct: 0, text: '0/0 completed' };
                const done = items.filter(i => i.is_done).length;
                const pct = Math.round((done / items.length) * 100);
                return { pct, text: `${done}/${items.length} completed` };
            },

            // --- Cover Image ---
            async uploadCover(event) {
                const file = event.target.files[0];
                if (!file) return;

                const fd = new FormData();
                fd.append('cover', file);

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/cover`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.task.cover_image_path = data.url ? data.url.replace('/storage/', '') : null;
                        this.showToast('Cover updated successfully');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            async removeCover() {
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/cover`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        this.task.cover_image_path = null;
                        this.showToast('Cover removed');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            async setAsCover(att) {
                if (att.file_path) {
                    this.task.cover_image_path = att.file_path;
                    this.markDirty();
                    this.showToast('Cover selected');
                }
            },

            // --- Attachments ---
            async uploadAttachmentFile(event) {
                const file = event.target.files[0];
                if (!file) return;

                const fd = new FormData();
                fd.append('type', 'file');
                fd.append('file', file);

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/attachments`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.task.attachments.unshift(data.attachment);
                        this.showToast('Attachment uploaded successfully');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            async submitAttachmentLink() {
                if (!this.linkUrl.trim()) return;
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/attachments`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({ type: 'link', url: this.linkUrl.trim(), display_name: this.linkName.trim() })
                    });
                    if (res.ok) {
                        const data = await res.json();
                        this.task.attachments.unshift(data.attachment);
                        this.linkUrl = '';
                        this.linkName = '';
                        this.showToast('Link saved successfully');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            confirmDeleteAttachment(att, index) {
                this.targetAttachment = att;
                this.targetAttachmentIndex = index;
                this.deleteAttachmentConfirmOpen = true;
            },

            async executeDeleteAttachment() {
                const att = this.targetAttachment;
                const index = this.targetAttachmentIndex;
                this.deleteAttachmentConfirmOpen = false;
                if (!att) return;

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/attachments/${att.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        this.task.attachments.splice(index, 1);
                        this.showToast('Attachment deleted successfully');
                    }
                } catch (err) {
                    console.error(err);
                }
            },

            isImageAttachment(att) {
                if (!att) return false;
                const ext = (att.file_name || '').split('.').pop().toLowerCase();
                return ['jpg', 'jpeg', 'png', 'gif', 'webp'].includes(ext);
            },
            getFileExtension(fileName) {
                if (!fileName) return 'FILE';
                return fileName.split('.').pop().toUpperCase();
            },
            getDomainFromUrl(url) {
                try {
                    return new URL(url).hostname;
                } catch {
                    return 'link';
                }
            },

            // --- Comments & Reactions ---
            async postComment(event) {
                const form = event.target;
                const textarea = form.querySelector('textarea[name="body"]');
                const body = (textarea.value || '').trim();
                if (!body) return;

                this.commentPosting = true;
                const fd = new FormData();
                fd.append('body', body);

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/comments`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: fd
                    });
                    if (res.ok) {
                        textarea.value = '';
                        this.task.comments.unshift({
                            id: Date.now(),
                            body: body,
                            created_at: new Date().toISOString(),
                            user: { name: '{{ auth()->user()->name }}' },
                            reactions: {}
                        });
                        this.showToast('Comment posted successfully');
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    this.commentPosting = false;
                }
            },

            insertFormat(prefix, suffix) {
                const textarea = this.$refs.commentInput;
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value;
                const selected = text.substring(start, end) || 'bold text';
                textarea.value = text.substring(0, start) + prefix + selected + suffix + text.substring(end);
                textarea.focus();
            },

            insertMention(userName) {
                const textarea = this.$refs.commentInput;
                if (!textarea) return;
                const start = textarea.selectionStart;
                const text = textarea.value;
                const mention = `@${userName} `;
                textarea.value = text.substring(0, start) + mention + text.substring(start);
                textarea.focus();
            },

            replyComment(item) {
                const textarea = this.$refs.commentInput;
                if (textarea) {
                    const name = item.user ? item.user.name : 'User';
                    textarea.value = `@${name} ` + textarea.value;
                    textarea.focus();
                }
            },

            toggleCommentReaction(item, emoji) {
                if (!item.reactions) item.reactions = {};
                const cur = item.reactions[emoji] || 0;
                if (cur > 0) {
                    item.reactions[emoji] = cur - 1;
                } else {
                    item.reactions[emoji] = 1;
                }
            },

            hasReaction(item, emoji) {
                return (item.reactions && item.reactions[emoji] > 0);
            },

            getReactionCount(item, emoji) {
                return item.reactions ? (item.reactions[emoji] || 0) : 0;
            },

            renderCommentBody(text) {
                if (!text) return '';
                let escaped = text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                // Bold **text**
                escaped = escaped.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-white">$1</strong>');

                // Single-pass mention replacement to avoid nested spans / border within border
                const userNames = (this.assignableUsers || [])
                    .map(u => u.name)
                    .filter(Boolean)
                    .sort((a, b) => b.length - a.length);

                const escapedNames = userNames.map(n => n.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'));
                const pattern = escapedNames.length > 0
                    ? new RegExp('@(' + escapedNames.join('|') + '|[a-zA-Z0-9_\\-]+)', 'gi')
                    : /@([a-zA-Z0-9_\-]+)/g;

                escaped = escaped.replace(pattern, (match, p1) => {
                    return `<span class="inline-flex items-center font-semibold text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 dark:border-blue-800/60 px-1.5 py-0.5 rounded-md text-[11px] leading-none">@${p1}</span>`;
                });

                return escaped;
            },

            // --- Notes Editor Helpers ---
            insertNoteFormat(prefix, suffix = '', defaultText = '') {
                const textarea = this.$refs.noteTextarea;
                if (!textarea) return;
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;
                const text = textarea.value || '';
                const selected = text.substring(start, end) || defaultText;
                textarea.value = text.substring(0, start) + prefix + selected + suffix + text.substring(end);
                this.task.description = textarea.value;
                this.markDirty();
                this.$nextTick(() => {
                    textarea.focus();
                    textarea.setSelectionRange(start + prefix.length, start + prefix.length + selected.length);
                });
            },

            renderNoteMarkdown(text) {
                if (!text || !text.trim()) return '<p class="text-slate-400 italic text-xs">No notes added yet.</p>';
                let html = text
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;');

                // Code blocks ```code```
                html = html.replace(/```([\s\S]*?)```/g, '<pre class="bg-slate-900 text-slate-100 p-3 rounded-lg text-xs font-mono overflow-x-auto my-2"><code>$1</code></pre>');

                // Inline code `code`
                html = html.replace(/`([^`]+)`/g, '<code class="bg-slate-100 dark:bg-slate-800 text-pink-600 dark:text-pink-400 px-1.5 py-0.5 rounded text-xs font-mono">$1</code>');

                // Headings # ## ###
                html = html.replace(/^### (.*$)/gim, '<h4 class="text-xs font-bold text-slate-800 dark:text-slate-100 mt-3 mb-1">$1</h4>');
                html = html.replace(/^## (.*$)/gim, '<h3 class="text-sm font-bold text-slate-800 dark:text-slate-100 mt-3 mb-1.5">$1</h3>');
                html = html.replace(/^# (.*$)/gim, '<h2 class="text-base font-bold text-slate-900 dark:text-white mt-4 mb-2">$1</h2>');

                // Bold & Italic
                html = html.replace(/\*\*\*(.*?)\*\*\*/g, '<strong class="font-bold"><em>$1</em></strong>');
                html = html.replace(/\*\*(.*?)\*\*/g, '<strong class="font-bold text-slate-900 dark:text-white">$1</strong>');
                html = html.replace(/\*(.*?)\*/g, '<em class="italic">$1</em>');

                // Blockquote >
                html = html.replace(/^\> (.*$)/gim, '<blockquote class="border-l-4 border-blue-500 pl-3 py-1 my-2 bg-blue-50/50 dark:bg-blue-950/20 text-slate-700 dark:text-slate-300 italic text-xs rounded-r">$1</blockquote>');

                // Task list / checklist - [ ] and - [x]
                html = html.replace(/^- \[x\] (.*$)/gim, '<div class="flex items-center gap-2 my-1 text-slate-400 line-through text-xs"><span class="w-4 h-4 rounded bg-emerald-500 text-white flex items-center justify-center text-[10px]">✓</span> $1</div>');
                html = html.replace(/^- \[ \] (.*$)/gim, '<div class="flex items-center gap-2 my-1 text-slate-700 dark:text-slate-300 text-xs"><span class="w-4 h-4 rounded border border-slate-300 dark:border-slate-600 inline-block"></span> $1</div>');

                // Bullet list - or *
                html = html.replace(/^[-*] (.*$)/gim, '<li class="ml-4 list-disc text-xs text-slate-700 dark:text-slate-300 my-0.5">$1</li>');

                // Links [text](url)
                html = html.replace(/\[(.*?)\]\((.*?)\)/g, '<a href="$2" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline font-medium">$1</a>');

                // Line breaks (convert newlines to <br> if not in list or pre)
                html = html.replace(/\n/g, '<br>');

                return html;
            },

            // --- Google Meet ---
            async createMeeting() {
                this.meetingCreating = true;
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/meeting`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        this.showToast('Google Meet created successfully');
                        this.openModal(this.task.id);
                    } else {
                        this.showToast('Failed to create Google Meet', 'error');
                    }
                } catch (err) {
                    console.error(err);
                } finally {
                    this.meetingCreating = false;
                }
            },

            // --- Save Task ---
            async saveTask() {
                this.saving = true;

                const payload = {
                    title: this.task.title,
                    description: this.task.description,
                    completion_notes: this.task.completion_notes,
                    status: this.task.status,
                    priority: this.task.priority,
                    board_column_id: this.task.board_column_id,
                    milestone_id: this.task.milestone_id,
                    start_date: this.task.start_date ? (typeof this.task.start_date === 'string' && this.task.start_date.includes('T') ? this.task.start_date.split('T')[0] : this.task.start_date) : null,
                    due_date: this.task.due_date ? (typeof this.task.due_date === 'string' && this.task.due_date.includes('T') ? this.task.due_date.split('T')[0] : this.task.due_date) : null,
                    estimated_hours: this.task.estimated_hours,
                    assigned_to: (this.task.members && this.task.members.length > 0) ? this.task.members[0].id : (this.task.assigned_to || null),
                    member_ids: (this.task.members || []).map(m => m.id),
                    label_ids: (this.task.labels || []).map(l => l.id),
                    cover_image_path: this.task.cover_image_path,
                    recurring_definition_id: this.task.recurring_definition_id,
                    recurring_frequency: this.recurringFrequency,
                    recurring_days: this.recurringDays,
                    recurring_end_date: this.recurringEndDate,
                    checklists: this.task.checklists,
                };

                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify(payload)
                    });

                    if (res.ok) {
                        const data = await res.json();
                        const updatedTask = data.task || this.task;
                        this.isDirty = false;
                        this.forceClose();

                        if (window.updateCardInDOM) {
                            window.updateCardInDOM(updatedTask);
                        }
                        this.showToast('Changes saved successfully');
                        window.dispatchEvent(new CustomEvent('task-updated', { detail: { taskId: updatedTask.id, task: updatedTask } }));
                    } else {
                        const errData = await res.json();
                        this.showToast(errData.message || 'Failed to save changes', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    this.showToast('A system error occurred', 'error');
                } finally {
                    this.saving = false;
                }
            },

            // --- Delete Task ---
            async deleteTask() {
                try {
                    const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}`, {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'Accept': 'application/json'
                        }
                    });
                    if (res.ok) {
                        const taskId = this.task.id;
                        this.forceClose();
                        if (window.removeCardFromDOM) {
                            window.removeCardFromDOM(taskId);
                        }
                        this.showToast('Task deleted successfully');
                        window.dispatchEvent(new CustomEvent('task-deleted', { detail: { taskId } }));
                    } else {
                        this.showToast('Failed to delete task', 'error');
                    }
                } catch (err) {
                    console.error(err);
                    this.showToast('A system error occurred', 'error');
                }
            },

            // --- Helpers ---
            formatDate(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
            },

            formatRelativeTime(dateStr) {
                if (!dateStr) return '';
                const d = new Date(dateStr);
                const now = new Date();
                const diff = Math.floor((now - d) / 1000);
                if (diff < 60) return 'Just now';
                if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
                if (diff < 86400) return Math.floor(diff / 3600) + 'h ago';
                if (diff < 604800) return Math.floor(diff / 86400) + 'd ago';
                return d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
            },

            formatFileSize(bytes) {
                if (!bytes || isNaN(bytes)) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
            }
        };
    }

    // Global DOM card real-time updater (Option 2 - Zero Page Reload)
    window.updateCardInDOM = function (task) {
        if (!task || !task.id) return;

        const cards = document.querySelectorAll(`.kanban-card[data-task-id="${task.id}"]`);

        // Label color mappings
        const labelColorMap = {
            'blue': { bg: 'bg-blue-100', text: 'text-blue-700', dot: 'bg-blue-500' },
            'red': { bg: 'bg-red-100', text: 'text-red-700', dot: 'bg-red-500' },
            'green': { bg: 'bg-green-100', text: 'text-green-700', dot: 'bg-green-500' },
            'yellow': { bg: 'bg-yellow-100', text: 'text-yellow-700', dot: 'bg-yellow-500' },
            'orange': { bg: 'bg-orange-100', text: 'text-orange-700', dot: 'bg-orange-500' },
            'purple': { bg: 'bg-purple-100', text: 'text-purple-700', dot: 'bg-purple-500' },
            'pink': { bg: 'bg-pink-100', text: 'text-pink-700', dot: 'bg-pink-500' },
            'gray': { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-500' },
            'teal': { bg: 'bg-teal-100', text: 'text-teal-700', dot: 'bg-teal-500' },
            'indigo': { bg: 'bg-indigo-100', text: 'text-indigo-700', dot: 'bg-indigo-500' },
        };

        const priorityBadges = {
            'urgent': 'bg-red-50 text-red-700 border-red-200 dark:bg-red-950/40 dark:text-red-400 dark:border-red-900/50',
            'high': 'bg-orange-50 text-orange-700 border-orange-200 dark:bg-orange-950/40 dark:text-orange-400 dark:border-orange-900/50',
            'medium': 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950/40 dark:text-amber-400 dark:border-amber-900/50',
            'low': 'bg-emerald-50 text-emerald-700 border-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-400 dark:border-emerald-900/50',
        };

        const priorityIcons = {
            'urgent': '<svg class="w-3 h-3 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 15l7-7 7 7M5 9l7-7 7 7"/></svg>',
            'high': '<svg class="w-3 h-3 text-orange-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg>',
            'medium': '<svg class="w-3 h-3 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 9h16M4 15h16"/></svg>',
            'low': '<svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>',
        };

        cards.forEach(card => {
            // 1. Cover Image Thumbnail
            const coverContainer = card.querySelector('.card-cover-container');
            if (coverContainer) {
                if (task.cover_image_path) {
                    const coverUrl = task.cover_image_path.startsWith('http') || task.cover_image_path.startsWith('/')
                        ? task.cover_image_path
                        : '/storage/' + task.cover_image_path;
                    coverContainer.innerHTML = `
                    <div class="h-28 w-full overflow-hidden bg-gray-100 dark:bg-gray-900 relative border-b border-gray-100 dark:border-gray-700/50">
                        <img src="${coverUrl}"
                             alt="${task.title || ''}"
                             class="w-full h-full object-cover group-hover:scale-[1.02] transition-transform duration-300"
                             loading="lazy">
                        <div class="absolute inset-0 bg-gradient-to-t from-black/20 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity"></div>
                    </div>
                `;
                } else {
                    coverContainer.innerHTML = '';
                }
            }

            // 2. Labels Row
            const labelsContainer = card.querySelector('.card-labels-container');
            if (labelsContainer) {
                if (task.labels && task.labels.length > 0) {
                    const visibleLabels = task.labels.slice(0, 2);
                    const remainingCount = task.labels.length - 2;
                    let labelsHtml = visibleLabels.map(label => {
                        const c = labelColorMap[label.color] || labelColorMap['gray'];
                        return `
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10.5px] font-semibold ${c.bg} ${c.text} tracking-wide">
                            <span class="w-1.5 h-1.5 rounded-full ${c.dot}"></span>
                            ${label.name}
                        </span>
                    `;
                    }).join('');
                    if (remainingCount > 0) {
                        labelsHtml += `
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 shrink-0">
                            +${remainingCount}
                        </span>
                        `;
                    }
                    labelsContainer.innerHTML = labelsHtml;
                } else {
                    labelsContainer.innerHTML = `
                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-medium bg-gray-50 text-gray-500 border border-gray-100">
                            Task
                        </span>
                    `;
                }
            }

            // 3. Title
            const titleEl = card.querySelector('.card-title-text, h4');
            if (titleEl) {
                titleEl.textContent = task.title;
            }

            // 4. Checklist Progress
            const checklistContainer = card.querySelector('.card-checklist-container');
            if (checklistContainer) {
                let doneCount = 0;
                let totalCount = 0;
                if (task.checklists && task.checklists.length > 0) {
                    task.checklists.forEach(group => {
                        if (group.items && group.items.length > 0) {
                            group.items.forEach(item => {
                                totalCount++;
                                if (item.is_done) doneCount++;
                            });
                        }
                    });
                }
                if (totalCount > 0) {
                    const pct = Math.round((doneCount / totalCount) * 100);
                    const isAllDone = pct === 100;
                    checklistContainer.innerHTML = `
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] font-medium text-gray-500 dark:text-gray-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 ${isAllDone ? 'text-emerald-500' : 'text-gray-400'}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                Checklist
                            </span>
                            <span class="${isAllDone ? 'text-emerald-600 font-semibold' : ''}">${doneCount}/${totalCount}</span>
                        </div>
                        <div class="w-full bg-gray-100 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-300 ${isAllDone ? 'bg-emerald-500' : 'bg-blue-500'}"
                                 style="width: ${pct}%"></div>
                        </div>
                    </div>
                `;
                } else {
                    checklistContainer.innerHTML = '';
                }
            }

            // 5. Priority Badge
            const priorityContainer = card.querySelector('.card-priority-container');
            if (priorityContainer) {
                if (task.priority && priorityBadges[task.priority]) {
                    const pLabel = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
                    priorityContainer.innerHTML = `
                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[11px] font-medium border ${priorityBadges[task.priority]}"
                          title="Priority: ${pLabel}">
                        ${priorityIcons[task.priority] || ''}
                        ${pLabel}
                    </span>
                `;
                } else {
                    priorityContainer.innerHTML = '';
                }
            }

            // 6. Due Date Badge
            const dueContainer = card.querySelector('.card-due-container');
            if (dueContainer) {
                if (task.due_date) {
                    const dateClean = (typeof task.due_date === 'string' && task.due_date.includes('T'))
                        ? task.due_date.split('T')[0]
                        : task.due_date;
                    const d = new Date(dateClean + 'T00:00:00');
                    const now = new Date();
                    now.setHours(0, 0, 0, 0);
                    const diffDays = Math.round((d - now) / (1000 * 60 * 60 * 24));
                    const overdue = diffDays < 0;
                    const formattedDate = d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });
                    const fullDate = d.toLocaleDateString('en-US', { day: 'numeric', month: 'short', year: 'numeric' });

                    let dateClass = 'text-gray-500';
                    if (overdue) {
                        dateClass = 'text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-950/40 px-1.5 py-0.5 rounded border border-red-200 dark:border-red-900/50';
                    } else if (diffDays <= 2) {
                        dateClass = 'text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-1.5 py-0.5 rounded';
                    }

                    dueContainer.innerHTML = `
                    <span class="inline-flex items-center gap-1 text-[11px] font-medium ${dateClass}"
                          title="Due date: ${fullDate}">
                        <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        ${formattedDate}
                    </span>
                `;
                } else {
                    dueContainer.innerHTML = '';
                }
            }

            // 7. Attachments Count
            const attachmentsContainer = card.querySelector('.card-attachments-container');
            if (attachmentsContainer) {
                const count = (task.attachments || []).length || (task.attachments_count || 0);
                if (count > 0) {
                    attachmentsContainer.innerHTML = `
                    <span class="inline-flex items-center gap-0.5 text-[11px] text-gray-500 hover:text-gray-700" title="${count} attachment${count !== 1 ? 's' : ''}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        ${count}
                    </span>
                `;
                } else {
                    attachmentsContainer.innerHTML = '';
                }
            }

            // 8. Comments Count
            const commentsContainer = card.querySelector('.card-comments-container');
            if (commentsContainer) {
                const count = (task.comments || []).length || (task.comments_count || 0);
                if (count > 0) {
                    commentsContainer.innerHTML = `
                    <span class="inline-flex items-center gap-0.5 text-[11px] text-gray-500 hover:text-gray-700" title="${count} comment${count !== 1 ? 's' : ''}">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                        ${count}
                    </span>
                `;
                } else {
                    commentsContainer.innerHTML = '';
                }
            }

            // 9. Members Avatars Stack
            const membersContainer = card.querySelector('.card-members-container');
            if (membersContainer) {
                const members = (task.members && task.members.length > 0)
                    ? task.members
                    : (task.assignee ? [task.assignee] : []);

                if (members.length === 0) {
                    membersContainer.innerHTML = `
                    <span class="w-6 h-6 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 flex items-center justify-center text-[10px]" title="Unassigned">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </span>
                `;
                } else {
                    const first3 = members.slice(0, 3);
                    const remaining = members.length - 3;
                    let html = first3.map(m => {
                        if (m.avatar) {
                            const avUrl = m.avatar.startsWith('http') || m.avatar.startsWith('/') ? m.avatar : '/storage/' + m.avatar;
                            return `<img src="${avUrl}" alt="${m.name || ''}" title="${m.name || ''}" class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 object-cover">`;
                        } else {
                            const parts = (m.name || '').trim().split(' ');
                            const initials = ((parts[0] ? parts[0][0] : '') + (parts[1] ? parts[1][0] : '')).toUpperCase() || 'U';
                            return `
                            <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gradient-to-br from-blue-500 to-indigo-600 text-white flex items-center justify-center text-[10px] font-bold"
                                 title="${m.name || ''}">
                                ${initials}
                            </div>
                        `;
                        }
                    }).join('');

                    if (remaining > 0) {
                        html += `
                        <div class="w-6 h-6 rounded-full ring-2 ring-white dark:ring-gray-800 bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300 flex items-center justify-center text-[10px] font-semibold"
                             title="+${remaining} more">
                            +${remaining}
                        </div>
                    `;
                    }
                    membersContainer.innerHTML = html;
                }
            }

            // 10. Column / Bucket Movement
            if (task.board_column_id) {
                const targetContainer = document.querySelector(
                    `#detail-cards-column-${task.board_column_id}, #project-cards-column-${task.board_column_id}, #cards-column-${task.board_column_id}, [data-column-id="${task.board_column_id}"] .project-cards-dropzone, [data-column-id="${task.board_column_id}"] .project-detail-cards-dropzone, [data-column-id="${task.board_column_id}"] .cards-dropzone`
                );
                if (targetContainer && card.parentElement !== targetContainer) {
                    targetContainer.prepend(card);
                }
            }

            // Smooth subtle highlight pulse on the card
            card.classList.add('ring-2', 'ring-blue-500', 'transition-all', 'duration-300');
            setTimeout(() => {
                card.classList.remove('ring-2', 'ring-blue-500');
            }, 1200);
        });

        // Also update any table/list rows matching this task
        document.querySelectorAll(`.list-task-row[data-task-id="${task.id}"], tr[data-task-id="${task.id}"], .task-list-row[data-task-id="${task.id}"]`).forEach(row => {
            const titleEl = row.querySelector('.task-title, span.text-xs.font-bold, a[href*="/tasks/"]');
            if (titleEl) titleEl.textContent = task.title;

            // Priority
            const priorityEl = row.querySelector('.list-priority-container');
            if (priorityEl && task.priority) {
                const pLabel = task.priority.charAt(0).toUpperCase() + task.priority.slice(1);
                priorityEl.className = `col-span-1 list-priority-container flex items-center gap-1.5 text-xs font-bold ${priorityBadges[task.priority] || 'text-blue-600'}`;
                priorityEl.innerHTML = `
                    <svg class="w-3.5 h-3.5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="M5 5a2 2 0 012-2h10a2 2 0 012 2v16l-7-3.5L5 21V5z"/></svg>
                    <span class="truncate">${pLabel}</span>
                `;
            }

            // Labels
            const labelsEl = row.querySelector('.list-labels-container');
            if (labelsEl) {
                if (task.labels && task.labels.length > 0) {
                    const visibleLabels = task.labels.slice(0, 2);
                    const remainingCount = task.labels.length - 2;
                    let labelsHtml = visibleLabels.map(l => {
                        const c = labelColorMap[l.color] || labelColorMap['gray'];
                        return `<span class="inline-flex items-center px-2 py-0.5 rounded-md text-[10.5px] font-bold ${c.bg} ${c.text} truncate max-w-[120px]">${l.name}</span>`;
                    }).join('');
                    if (remainingCount > 0) {
                        labelsHtml += `<span class="inline-flex items-center px-1.5 py-0.5 rounded-md text-[10px] font-bold bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 shrink-0">+${remainingCount}</span>`;
                    }
                    labelsEl.innerHTML = labelsHtml;
                } else {
                    labelsEl.innerHTML = '<span class="text-xs text-gray-300">—</span>';
                }
            }

            // Due Date
            const dueEl = row.querySelector('.list-due-container');
            if (dueEl) {
                if (task.status === 'done' || task.status === 'completed') {
                    dueEl.innerHTML = `<span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-600 dark:text-emerald-400"><svg class="w-3 h-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>Completed</span>`;
                } else if (task.due_date) {
                    const dateClean = (typeof task.due_date === 'string' && task.due_date.includes('T')) ? task.due_date.split('T')[0] : task.due_date;
                    const d = new Date(dateClean + 'T00:00:00');
                    const now = new Date();
                    now.setHours(0, 0, 0, 0);
                    const diffDays = Math.round((d - now) / (1000 * 60 * 60 * 24));
                    const overdue = diffDays < 0;
                    const formattedDate = d.toLocaleDateString('en-US', { day: 'numeric', month: 'short' });

                    if (overdue) {
                        dueEl.innerHTML = `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-600 dark:text-red-400"><span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>${formattedDate} (Overdue)</span>`;
                    } else if (diffDays <= 2) {
                        dueEl.innerHTML = `<span class="inline-flex items-center gap-1 text-[11px] font-bold text-red-500 dark:text-red-400"><span class="w-1.5 h-1.5 rounded-full bg-red-500 shrink-0"></span>Due: ${formattedDate}</span>`;
                    } else {
                        dueEl.innerHTML = `<span class="inline-flex items-center gap-1 text-[11px] font-medium text-gray-500 dark:text-gray-400"><svg class="w-3 h-3 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>${formattedDate}</span>`;
                    }
                } else {
                    dueEl.innerHTML = '<span class="text-[11px] text-gray-300 dark:text-gray-600">—</span>';
                }
            }

            // Checklists
            const checklistEl = row.querySelector('.list-checklist-container');
            if (checklistEl) {
                let doneCount = 0;
                let totalCount = 0;
                if (task.checklists && task.checklists.length > 0) {
                    task.checklists.forEach(g => {
                        if (g.items) {
                            g.items.forEach(i => {
                                totalCount++;
                                if (i.is_done) doneCount++;
                            });
                        }
                    });
                }
                if (totalCount > 0) {
                    const isAll = doneCount === totalCount;
                    checklistEl.innerHTML = `
                        <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md text-[11px] font-semibold ${isAll ? 'bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400' : 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400'}">
                            <svg class="w-3 h-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span>${doneCount}/${totalCount}</span>
                        </span>
                    `;
                } else {
                    checklistEl.innerHTML = '<span class="text-[11px] text-gray-300 dark:text-gray-600">—</span>';
                }
            }

            // Move row in list view if bucket changed
            if (task.board_column_id) {
                const targetListDrop = document.getElementById(`list-tasks-column-${task.board_column_id}`);
                if (targetListDrop && row.parentElement !== targetListDrop) {
                    targetListDrop.prepend(row);
                }
            }
        });

        // Update column counters
        window.updateColumnCounters();
    };

    window.removeCardFromDOM = function (taskId) {
        if (!taskId) return;
        const cards = document.querySelectorAll(`.kanban-card[data-task-id="${taskId}"], tr[data-task-id="${taskId}"], .task-list-row[data-task-id="${taskId}"]`);
        cards.forEach(card => {
            card.style.transition = 'all 0.3s ease-out';
            card.style.opacity = '0';
            card.style.transform = 'scale(0.9)';
            card.style.maxHeight = '0';
            card.style.padding = '0';
            card.style.margin = '0';
            card.style.overflow = 'hidden';
            setTimeout(() => {
                card.remove();
                window.updateColumnCounters();
            }, 300);
        });
    };

    window.updateColumnCounters = function () {
        document.querySelectorAll('.kanban-column, [data-column-id]').forEach(col => {
            const counter = col.querySelector('.column-counter');
            const dropzone = col.querySelector('.project-cards-dropzone, .project-detail-cards-dropzone, .cards-dropzone') || col;
            if (counter && dropzone) {
                const count = dropzone.querySelectorAll('.kanban-card').length;
                counter.textContent = count;
            }
        });
    };
</script>