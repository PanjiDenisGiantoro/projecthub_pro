{{-- Modal Assign Tasks to Sprint --}}
<div x-show="showSprintAssignModal" x-cloak class="relative z-[9999]">
    {{-- Standalone Fullscreen Backdrop --}}
    <div x-show="showSprintAssignModal" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm z-[9998]"
        @click="closeSprintAssignModal()"></div>

    {{-- Modal Wrapper --}}
    <div x-show="showSprintAssignModal" x-cloak
        class="fixed inset-0 z-[9999] overflow-y-auto p-4 sm:p-6 flex items-center justify-center pointer-events-none"
        @keydown.escape.window="closeSprintAssignModal()">

        <div class="relative w-full max-w-2xl bg-white dark:bg-gray-850 rounded-2xl shadow-2xl border border-gray-200/90 dark:border-gray-700/80 overflow-hidden transform transition-all my-8 pointer-events-auto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4.5 border-b border-gray-100 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200/60 dark:border-indigo-800/60 flex items-center justify-center text-indigo-600 dark:text-indigo-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Assign Tasks to Sprint</h3>
                        <span class="px-2 py-0.5 rounded-md text-xs font-extrabold bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-mono" x-text="assignSprintCode"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'Target Sprint: ' + assignSprintTitle"></p>
                </div>
            </div>
            <button type="button" @click="closeSprintAssignModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form :action="assignSprintActionUrl" method="POST" @submit.prevent="submitSprintAssignForm($event)">
            @csrf

            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                {{-- Search Filter in Modal --}}
                <div class="relative">
                    <input type="text" x-model="assignSprintSearchQuery" placeholder="Search tasks..."
                        class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- Task Checkboxes List --}}
                <div class="space-y-2 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-2 bg-gray-50/40 dark:bg-gray-800/30 max-h-80 overflow-y-auto">
                    @forelse($project->tasks as $task)
                        <label class="flex items-center justify-between p-2.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 hover:border-blue-200 dark:hover:border-blue-800/60 transition cursor-pointer group"
                            x-show="!assignSprintSearchQuery || '{{ strtolower(addslashes($task->title . ' ' . ($task->code ?? ''))) }}'.includes(assignSprintSearchQuery.toLowerCase())">
                            <div class="flex items-center gap-3 min-w-0">
                                <input type="checkbox" name="task_ids[]" value="{{ $task->id }}"
                                    :checked="selectedSprintTaskIds.includes({{ $task->id }})"
                                    @change="toggleSprintTaskSelection({{ $task->id }})"
                                    class="w-4 h-4 text-indigo-600 rounded border-gray-300 dark:border-gray-600 focus:ring-indigo-500">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($task->code)
                                            <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono">{{ $task->code }}</span>
                                        @endif
                                        <span class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ $task->title }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                        <span>Column: {{ $task->boardColumn?->name ?? ucfirst($task->status) }}</span>
                                        @if($task->assignee)
                                            &bull; <span>PIC: {{ $task->assignee->name }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $task->status === 'done' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $task->status === 'done' ? 'Done' : 'Active' }}
                                </span>
                            </div>
                        </label>
                    @empty
                        <p class="text-xs text-center text-gray-400 py-3">No tasks found in project.</p>
                    @endforelse
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800/40">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Selected: <strong class="text-gray-900 dark:text-white font-bold" x-text="selectedSprintTaskIds.length"></strong> tasks
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="closeSprintAssignModal()"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-650 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                        :disabled="isSubmittingSprintAssign"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="isSubmittingSprintAssign">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <template x-if="!isSubmittingSprintAssign">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <span x-text="isSubmittingSprintAssign ? 'Saving...' : 'Save Tasks'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
