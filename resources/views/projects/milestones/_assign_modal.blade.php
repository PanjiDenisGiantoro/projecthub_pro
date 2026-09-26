{{-- Modal Assign Sprints & Tasks to Milestone --}}
<div x-show="showMilestoneAssignModal" x-cloak class="relative z-[9999]">
    {{-- Standalone Fullscreen Backdrop --}}
    <div x-show="showMilestoneAssignModal" x-cloak
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm z-[9998]"
        @click="closeMilestoneAssignModal()"></div>

    {{-- Modal Wrapper --}}
    <div x-show="showMilestoneAssignModal" x-cloak
        class="fixed inset-0 z-[9999] overflow-y-auto p-4 sm:p-6 flex items-center justify-center pointer-events-none"
        @keydown.escape.window="closeMilestoneAssignModal()">

        <div class="relative w-full max-w-3xl bg-white dark:bg-gray-850 rounded-2xl shadow-2xl border border-gray-200/90 dark:border-gray-700/80 overflow-hidden transform transition-all my-8 pointer-events-auto"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2">

        {{-- Modal Header --}}
        <div class="flex items-center justify-between px-6 py-4.5 border-b border-gray-100 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800/40">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-purple-50 dark:bg-purple-950/60 border border-purple-200/60 dark:border-purple-800/60 flex items-center justify-center text-purple-600 dark:text-purple-400 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <div class="flex items-center gap-2">
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Assign Sprints & Tasks</h3>
                        <span class="px-2 py-0.5 rounded-md text-xs font-extrabold bg-blue-100 dark:bg-blue-900/60 text-blue-700 dark:text-blue-300 font-mono" x-text="assignMilestoneCode"></span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400" x-text="'Target Milestone: ' + assignMilestoneTitle"></p>
                </div>
            </div>
            <button type="button" @click="closeMilestoneAssignModal()" class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-750 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        {{-- Form --}}
        <form :action="assignMilestoneActionUrl" method="POST" @submit.prevent="submitAssignForm($event)">
            @csrf

            <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                {{-- Helper Note --}}
                <div class="flex items-start gap-3 p-3.5 rounded-xl bg-blue-50/70 dark:bg-blue-950/40 border border-blue-200/70 dark:border-blue-800/60 text-xs text-blue-800 dark:text-blue-300">
                    <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <p class="font-bold">Flexible Milestone Structure</p>
                        <p class="text-blue-700/80 dark:text-blue-400/90 text-[11px] mt-0.5">Select entire Sprints to roll up into this milestone, or select Standalone Tasks directly. Unchecking an item removes it from this milestone.</p>
                    </div>
                </div>

                {{-- Search Filter in Modal --}}
                <div class="relative">
                    <input type="text" x-model="assignSearchQuery" placeholder="Search sprints or tasks..."
                        class="w-full pl-9 pr-4 py-2.5 bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-xs font-medium text-slate-800 dark:text-slate-200 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                    <svg class="w-4 h-4 text-slate-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                {{-- SECTION 1: SPRINTS --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-indigo-500"></span>
                            <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider">
                                Project Sprints ({{ $project->sprints->count() }})
                            </h4>
                        </div>
                        <span class="text-[11px] text-gray-400">Select sprints associated with this milestone</span>
                    </div>

                    <div class="space-y-2 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-2 bg-gray-50/40 dark:bg-gray-800/30 max-h-56 overflow-y-auto">
                        @forelse($project->sprints as $sp)
                            <label class="flex items-center justify-between p-2.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 hover:border-blue-200 dark:hover:border-blue-800/60 transition cursor-pointer group"
                                x-show="!assignSearchQuery || '{{ strtolower(addslashes($sp->name . ' ' . $sp->code)) }}'.includes(assignSearchQuery.toLowerCase())">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input type="checkbox" name="sprint_ids[]" value="{{ $sp->id }}"
                                        :checked="selectedSprintIds.includes({{ $sp->id }})"
                                        @change="toggleSprintSelection({{ $sp->id }})"
                                        class="w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-gray-600 focus:ring-blue-500">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            <span class="text-[10px] font-extrabold px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 font-mono">{{ $sp->code ?? ('SP-' . $sp->id) }}</span>
                                            <span class="text-xs font-bold text-gray-900 dark:text-white truncate">{{ $sp->name }}</span>
                                            <span class="text-[10px] px-1.5 py-0.5 rounded-full {{ $sp->statusStyle()['pill'] }} font-semibold">{{ $sp->statusLabel() }}</span>
                                        </div>
                                        <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                            {{ $sp->tasks->count() }} tasks &bull;
                                            {{ $sp->start_date ? $sp->start_date->format('d M') : '—' }} – {{ $sp->end_date ? $sp->end_date->format('d M Y') : '—' }}
                                            @if($sp->lead)
                                                &bull; Lead: <span class="font-medium text-gray-600 dark:text-gray-300">{{ $sp->lead->name }}</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                <div class="shrink-0 text-right">
                                    <span class="text-xs font-extrabold text-blue-600 dark:text-blue-400">{{ $sp->taskProgressPercent() }}%</span>
                                </div>
                            </label>
                        @empty
                            <p class="text-xs text-center text-gray-400 py-3">No sprints created in this project yet.</p>
                        @endforelse
                    </div>
                </div>

                {{-- SECTION 2: STANDALONE TASKS (TASKS WITHOUT SPRINT OR ALL TASKS) --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span>
                            <h4 class="text-xs font-black text-gray-900 dark:text-white uppercase tracking-wider">
                                Standalone / Project Tasks ({{ $project->tasks->count() }})
                            </h4>
                        </div>
                        <span class="text-[11px] text-gray-400">Directly assign tasks to this milestone</span>
                    </div>

                    <div class="space-y-2 border border-gray-200/80 dark:border-gray-700/80 rounded-xl p-2 bg-gray-50/40 dark:bg-gray-800/30 max-h-64 overflow-y-auto">
                        @forelse($project->tasks as $task)
                            <label class="flex items-center justify-between p-2.5 rounded-lg bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700/60 hover:border-blue-200 dark:hover:border-blue-800/60 transition cursor-pointer group"
                                x-show="!assignSearchQuery || '{{ strtolower(addslashes($task->title . ' ' . ($task->code ?? ''))) }}'.includes(assignSearchQuery.toLowerCase())">
                                <div class="flex items-center gap-3 min-w-0">
                                    <input type="checkbox" name="task_ids[]" value="{{ $task->id }}"
                                        :checked="selectedTaskIds.includes({{ $task->id }})"
                                        @change="toggleTaskSelection({{ $task->id }})"
                                        class="w-4 h-4 text-emerald-600 rounded border-gray-300 dark:border-gray-600 focus:ring-emerald-500">
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-2 flex-wrap">
                                            @if($task->code)
                                                <span class="text-[10px] font-bold px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono">{{ $task->code }}</span>
                                            @endif
                                            <span class="text-xs font-semibold text-gray-900 dark:text-white truncate">{{ $task->title }}</span>
                                            @if($task->sprint)
                                                <span class="text-[9px] px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-400 border border-indigo-200/50">In {{ $task->sprint->name }}</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 text-[11px] text-gray-400 dark:text-gray-500 mt-0.5">
                                            <span>Column: {{ $task->boardColumn?->name ?? ucfirst($task->status) }}</span>
                                            @if($task->assignee)
                                                &bull; <span>PIC: {{ $task->assignee->name }}</span>
                                            @endif
                                            @if($task->priority)
                                                &bull; <span class="capitalize">{{ $task->priority }} Priority</span>
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
                            <p class="text-xs text-center text-gray-400 py-3">No tasks created in this project yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-gray-100 dark:border-gray-750 bg-gray-50/50 dark:bg-gray-800/40">
                <div class="text-xs text-gray-500 dark:text-gray-400">
                    Selected: <strong class="text-gray-900 dark:text-white font-bold" x-text="selectedSprintIds.length"></strong> sprints,
                    <strong class="text-gray-900 dark:text-white font-bold" x-text="selectedTaskIds.length"></strong> tasks
                </div>
                <div class="flex items-center gap-3">
                    <button type="button" @click="closeMilestoneAssignModal()"
                        class="px-4 py-2 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-650 hover:bg-gray-50 dark:hover:bg-gray-750 text-gray-700 dark:text-gray-300 text-xs font-bold rounded-xl transition shadow-2xs">
                        Cancel
                    </button>
                    <button type="submit"
                        :disabled="isSubmittingAssign"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs disabled:opacity-60 disabled:cursor-not-allowed">
                        <template x-if="isSubmittingAssign">
                            <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                            </svg>
                        </template>
                        <template x-if="!isSubmittingAssign">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                            </svg>
                        </template>
                        <span x-text="isSubmittingAssign ? 'Saving...' : 'Save Assignments'"></span>
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
</div>
