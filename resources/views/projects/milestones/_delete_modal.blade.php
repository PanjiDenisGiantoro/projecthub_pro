{{-- Modal Delete Milestone Confirmation (Task-style) --}}
<div x-show="deleteConfirmOpen" x-cloak class="relative z-[9999]">
    {{-- Standalone Fullscreen Backdrop with Blur --}}
    <div x-show="deleteConfirmOpen" x-cloak
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 w-screen h-screen bg-slate-900/60 backdrop-blur-sm z-[9998]"
        @click="deleteConfirmOpen = false; deleteConfirmChecked = false"></div>

    {{-- Confirmation Dialog Wrapper --}}
    <div x-show="deleteConfirmOpen" x-cloak
        class="fixed inset-0 z-[9999] overflow-y-auto p-4 sm:p-6 flex items-center justify-center pointer-events-none"
        @keydown.escape.window="deleteConfirmOpen = false; deleteConfirmChecked = false">

        <div class="relative w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-2xl border border-slate-200 dark:border-slate-800 overflow-hidden z-10 pointer-events-auto my-8"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 scale-95 translate-y-2"
            x-transition:enter-end="opacity-100 scale-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 scale-100 translate-y-0"
            x-transition:leave-end="opacity-0 scale-95 translate-y-2">

            <div class="p-6 space-y-4">
                {{-- Warning Triangle Icon + Title --}}
                <div class="flex items-start gap-3.5">
                    <div class="w-11 h-11 rounded-full bg-red-100 dark:bg-red-950/50 flex items-center justify-center shrink-0">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-white">Delete This Milestone?</h3>
                        <p class="text-xs sm:text-sm text-slate-500 dark:text-slate-400 mt-1 leading-relaxed">
                            This action will permanently delete <strong class="text-slate-800 dark:text-slate-200 font-semibold" x-text="deleteMilestoneTitle"></strong>. Sprints and tasks in this milestone will be unlinked.
                        </p>
                    </div>
                </div>

                {{-- Pink/Red Checkbox Confirmation Box --}}
                <label class="flex items-start gap-2.5 p-3.5 bg-red-50/80 dark:bg-red-950/30 border border-red-200 dark:border-red-900/50 rounded-xl cursor-pointer">
                    <input type="checkbox" x-model="deleteConfirmChecked"
                        class="w-4 h-4 rounded text-red-600 focus:ring-red-500 border-red-300 mt-0.5">
                    <span class="text-xs text-red-800 dark:text-red-300 leading-relaxed font-medium">
                        I understand that this action is permanent and cannot be undone.
                    </span>
                </label>
            </div>

            {{-- Action Buttons --}}
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 bg-slate-50/50 dark:bg-slate-900/50 flex items-center justify-end gap-2.5">
                <button type="button" @click="deleteConfirmOpen = false; deleteConfirmChecked = false"
                    class="px-5 py-2 text-xs font-semibold text-slate-700 dark:text-slate-300 bg-white dark:bg-slate-800 border border-slate-300 dark:border-slate-700 rounded-xl hover:bg-slate-50 transition shadow-2xs">
                    Cancel
                </button>
                <button type="button" @click="confirmDeleteMilestone()"
                    :disabled="!deleteConfirmChecked || isDeletingMilestone"
                    class="px-5 py-2 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-xl transition shadow-sm flex items-center gap-1.5 disabled:opacity-50 disabled:cursor-not-allowed">
                    <template x-if="isDeletingMilestone">
                        <svg class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </template>
                    <template x-if="!isDeletingMilestone">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </template>
                    <span x-text="isDeletingMilestone ? 'Deleting...' : 'Yes, Delete Milestone'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
