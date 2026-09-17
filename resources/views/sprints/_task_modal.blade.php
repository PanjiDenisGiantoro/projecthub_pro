{{-- Task Detail Modal (Planner & Trello Inspired 70/30 UI) --}}
<div x-data="taskModalComponent()"
     x-init="initModal()"
     @open-task-modal.window="openModal($event.detail.taskId)"
     @keydown.escape.window="attemptClose()"
     class="relative z-50">

    {{-- Backdrop --}}
    <div x-show="isOpen"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

    {{-- Modal Wrapper --}}
    <div x-show="isOpen"
         x-cloak
         @click.self="attemptClose()"
         class="fixed inset-0 z-10 overflow-y-auto p-2 sm:p-4 md:p-6 flex items-center justify-center">

        {{-- Modal Dialog --}}
        <div x-show="isOpen"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
             x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
             class="relative w-full max-w-6xl max-h-[92vh] flex flex-col bg-white dark:bg-gray-900 rounded-2xl shadow-2xl border border-gray-200 dark:border-gray-800 overflow-hidden text-gray-800 dark:text-gray-100">

            {{-- Top Bar / Header --}}
            <div class="px-5 py-3.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 bg-gray-50/70 dark:bg-gray-900/80 shrink-0">
                <div class="flex items-center gap-2.5 flex-wrap min-w-0">
                    {{-- Bucket Selector Pill --}}
                    <div class="relative" x-data="{ open: false }">
                        <button type="button"
                                @click="open = !open"
                                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600 shadow-2xs transition">
                            <span class="w-2 h-2 rounded-full" :style="'background-color:' + (currentColumn ? currentColumn.color || '#3b82f6' : '#9ca3af')"></span>
                            <span class="text-gray-500 dark:text-gray-400">Kolom:</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="currentColumn ? currentColumn.name : 'Pilih Kolom'"></span>
                            <svg class="w-3.5 h-3.5 text-gray-400 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="open"
                             @click.outside="open = false"
                             x-cloak
                             class="absolute left-0 top-full mt-1 w-48 bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 py-1 z-50">
                            <template x-for="col in columns" :key="col.id">
                                <button type="button"
                                        @click="task.board_column_id = col.id; markDirty(); open = false"
                                        class="w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                    <span class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full" :style="'background-color:' + (col.color || '#3b82f6')"></span>
                                        <span x-text="col.name" :class="task.board_column_id === col.id ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300'"></span>
                                    </span>
                                    <svg x-show="task.board_column_id === col.id" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                </button>
                            </template>
                        </div>
                    </div>

                    {{-- Unsaved indicator --}}
                    <span x-show="isDirty" x-cloak class="inline-flex items-center gap-1 text-[11px] font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950/40 px-2 py-0.5 rounded-full border border-amber-200 dark:border-amber-900/40">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                        Perubahan belum disimpan
                    </span>
                </div>

                <div class="flex items-center gap-2 shrink-0">
                    {{-- Full page link --}}
                    <template x-if="task.id">
                        <a :href="'/projects/{{ $project->id }}/tasks/' + task.id"
                           target="_blank"
                           title="Buka halaman penuh"
                           class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                        </a>
                    </template>

                    {{-- Toggle Right Panel --}}
                    <button type="button"
                            @click="rightPanelOpen = !rightPanelOpen"
                            :title="rightPanelOpen ? 'Sembunyikan Panel Aktivitas' : 'Tampilkan Panel Aktivitas'"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/></svg>
                    </button>

                    {{-- Close Button --}}
                    <button type="button"
                            @click="attemptClose()"
                            class="p-1.5 rounded-lg text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>

            {{-- Loading State --}}
            <div x-show="loading" class="p-12 flex flex-col items-center justify-center space-y-3">
                <div class="w-8 h-8 border-2 border-blue-600 border-t-transparent rounded-full animate-spin"></div>
                <p class="text-xs text-gray-400 font-medium">Memuat rincian task...</p>
            </div>

            {{-- Main Modal Body (Split Layout: 70% Left / 30% Right) --}}
            <div x-show="!loading" class="flex-1 flex overflow-hidden">

                {{-- LEFT COLUMN (Main: 70% or 100% when collapsed) --}}
                <div class="flex-1 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-800">

                    {{-- Cover Image Section --}}
                    <div class="relative bg-gray-50 dark:bg-gray-950">
                        <template x-if="task.cover_image_path">
                            <div class="relative group h-44 sm:h-52 w-full overflow-hidden bg-gray-100 dark:bg-gray-800">
                                <img :src="coverUrl"
                                     alt="Cover"
                                     class="w-full h-full object-cover">
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <label class="cursor-pointer px-3 py-1.5 bg-white/90 hover:bg-white text-gray-800 rounded-lg text-xs font-semibold shadow transition flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Ganti Cover
                                        <input type="file" accept="image/*" class="hidden" @change="uploadCover($event)">
                                    </label>
                                    <button type="button"
                                            @click="removeCover()"
                                            class="px-3 py-1.5 bg-red-600/90 hover:bg-red-600 text-white rounded-lg text-xs font-semibold shadow transition flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        Hapus
                                    </button>
                                </div>
                            </div>
                        </template>
                        <template x-if="!task.cover_image_path">
                            <div class="px-6 py-2.5 flex items-center justify-between border-b border-gray-100 dark:border-gray-800/80">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 text-xs text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 font-medium transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    + Tambah Cover Image
                                    <input type="file" accept="image/*" class="hidden" @change="uploadCover($event)">
                                </label>
                            </div>
                        </template>
                    </div>

                    {{-- Title and Essential Fields --}}
                    <div class="p-6 space-y-6">
                        {{-- Title input --}}
                        <div>
                            <input type="text"
                                   x-model="task.title"
                                   @input="markDirty()"
                                   placeholder="Judul task..."
                                   class="w-full text-xl sm:text-2xl font-bold text-gray-900 dark:text-white bg-transparent border-0 border-b-2 border-transparent focus:border-blue-500 focus:ring-0 p-0 pb-1.5 transition-colors placeholder:text-gray-300 dark:placeholder:text-gray-600">
                        </div>

                        {{-- Metadata Grid (Planner Style) --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 bg-gray-50/60 dark:bg-gray-800/40 p-4 rounded-xl border border-gray-100 dark:border-gray-800 text-sm">

                            {{-- Status --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Status</label>
                                <select x-model="task.status"
                                        @change="markDirty()"
                                        class="w-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 focus:ring-2 focus:ring-blue-500">
                                    <option value="todo">To Do</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="review">Review</option>
                                    <option value="done">Done</option>
                                </select>
                            </div>

                            {{-- Priority --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Prioritas</label>
                                <select x-model="task.priority"
                                        @change="markDirty()"
                                        class="w-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 focus:ring-2 focus:ring-blue-500">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>

                            {{-- Milestone --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Milestone</label>
                                <select x-model="task.milestone_id"
                                        @change="markDirty()"
                                        class="w-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 focus:ring-2 focus:ring-blue-500">
                                    <option value="">— Tanpa Milestone —</option>
                                    <template x-for="m in milestones" :key="m.id">
                                        <option :value="m.id" x-text="m.title" :selected="task.milestone_id == m.id"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Start Date --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Mulai</label>
                                <input type="date"
                                       x-model="task.start_date"
                                       @input="markDirty()"
                                       class="w-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            {{-- Due Date --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Tenggat Waktu</label>
                                <input type="date"
                                       x-model="task.due_date"
                                       @input="markDirty()"
                                       class="w-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>

                            {{-- Estimated Hours --}}
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400 mb-1">Estimasi Jam</label>
                                <input type="number"
                                       x-model="task.estimated_hours"
                                       @input="markDirty()"
                                       min="0"
                                       step="0.5"
                                       placeholder="Misal: 4"
                                       class="w-full text-xs font-medium bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-2.5 py-2 focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        {{-- Members Assignment Row --}}
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">Anggota Tim (Assignees)</label>
                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- Assigned members chips --}}
                                <template x-for="m in task.members" :key="m.id">
                                    <span class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-1 rounded-full text-xs font-medium bg-gray-100 dark:bg-gray-800 border border-gray-200 dark:border-gray-700">
                                        <template x-if="m.avatar">
                                            <img :src="'/storage/' + m.avatar" class="w-5 h-5 rounded-full object-cover">
                                        </template>
                                        <template x-if="!m.avatar">
                                            <span class="w-5 h-5 rounded-full bg-blue-600 text-white text-[10px] flex items-center justify-center font-bold"
                                                  x-text="m.name ? m.name.charAt(0).toUpperCase() : 'U'"></span>
                                        </template>
                                        <span class="text-gray-800 dark:text-gray-200" x-text="m.name"></span>
                                        <button type="button"
                                                @click="removeMember(m.id)"
                                                class="text-gray-400 hover:text-red-500 p-0.5 rounded-full transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                </template>

                                {{-- Add Member Dropdown --}}
                                <div class="relative" x-data="{ open: false }">
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium border border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-600 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Anggota
                                    </button>

                                    <div x-show="open"
                                         @click.outside="open = false"
                                         x-cloak
                                         class="absolute left-0 top-full mt-1.5 w-60 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 py-1 z-50 max-h-60 overflow-y-auto">
                                        <div class="px-3 py-1.5 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Pilih Anggota Tim</div>
                                        <template x-for="user in assignableUsers" :key="user.id">
                                            <button type="button"
                                                    @click="toggleMember(user); open = false"
                                                    class="w-full text-left px-3 py-2 text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                <span class="flex items-center gap-2">
                                                    <span class="w-5 h-5 rounded-full bg-blue-500 text-white text-[10px] flex items-center justify-center font-bold" x-text="user.name.charAt(0)"></span>
                                                    <span class="text-gray-800 dark:text-gray-200" x-text="user.name"></span>
                                                </span>
                                                <svg x-show="isMemberAssigned(user.id)" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Labels Row --}}
                        <div class="space-y-2">
                            <label class="block text-xs font-semibold text-gray-500 dark:text-gray-400">Labels</label>
                            <div class="flex items-center gap-2 flex-wrap">
                                {{-- Attached Labels --}}
                                <template x-for="l in task.labels" :key="l.id">
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-medium"
                                          :class="labelClasses(l.color).badge">
                                        <span class="w-2 h-2 rounded-full" :class="labelClasses(l.color).dot"></span>
                                        <span x-text="l.name"></span>
                                        <button type="button"
                                                @click="removeLabel(l.id)"
                                                class="text-gray-400 hover:text-red-500 ml-0.5 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </span>
                                </template>

                                {{-- Add Label Dropdown --}}
                                <div class="relative" x-data="{ open: false, newName: '', newColor: 'blue', isCreating: false }">
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-md text-xs font-medium border border-dashed border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 hover:border-blue-500 hover:text-blue-600 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                        Tambah Label
                                    </button>

                                    <div x-show="open"
                                         @click.outside="open = false"
                                         x-cloak
                                         class="absolute left-0 top-full mt-1.5 w-64 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 p-2 z-50">
                                        <div class="px-2 py-1 text-[11px] font-semibold text-gray-400 uppercase tracking-wider">Label Proyek</div>
                                        <div class="max-h-44 overflow-y-auto space-y-1">
                                            <template x-for="lbl in projectLabels" :key="lbl.id">
                                                <button type="button"
                                                        @click="toggleLabel(lbl); open = false"
                                                        class="w-full text-left px-2.5 py-1.5 rounded-lg text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/50 transition">
                                                    <span class="flex items-center gap-2">
                                                        <span class="w-2.5 h-2.5 rounded-full" :class="labelClasses(lbl.color).dot"></span>
                                                        <span x-text="lbl.name" class="font-medium text-gray-700 dark:text-gray-300"></span>
                                                    </span>
                                                    <svg x-show="isLabelAttached(lbl.id)" class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                            </template>
                                        </div>

                                        {{-- Inline Create Label Form --}}
                                        <div class="mt-2 pt-2 border-t border-gray-100 dark:border-gray-700/60">
                                            <template x-if="!isCreating">
                                                <button type="button"
                                                        @click="isCreating = true"
                                                        class="w-full text-left text-xs text-blue-600 hover:text-blue-700 font-medium py-1 px-2 flex items-center gap-1">
                                                    + Buat label baru
                                                </button>
                                            </template>
                                            <template x-if="isCreating">
                                                <div class="space-y-2 p-1">
                                                    <input type="text"
                                                           x-model="newName"
                                                           placeholder="Nama label..."
                                                           class="w-full text-xs px-2.5 py-1.5 rounded-lg border border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-900 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                                    <div class="flex items-center gap-1.5 flex-wrap">
                                                        <template x-for="c in ['blue', 'green', 'red', 'yellow', 'orange', 'purple', 'teal']" :key="c">
                                                            <button type="button"
                                                                    @click="newColor = c"
                                                                    class="w-5 h-5 rounded-full transition-transform"
                                                                    :class="[labelClasses(c).dot, newColor === c ? 'ring-2 ring-offset-1 ring-blue-500 scale-110' : 'opacity-80']"></button>
                                                        </template>
                                                    </div>
                                                    <div class="flex items-center justify-end gap-1 pt-1">
                                                        <button type="button" @click="isCreating = false; newName = ''" class="px-2 py-1 text-xs text-gray-500 hover:text-gray-700">Batal</button>
                                                        <button type="button" @click="createNewLabel(newName, newColor); isCreating = false; newName = ''; open = false" class="px-2.5 py-1 text-xs bg-blue-600 text-white font-medium rounded-md hover:bg-blue-700">Simpan</button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Checklists Section (Multiple Groups) --}}
                    <div class="p-6 space-y-5 bg-white dark:bg-gray-900">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-bold text-gray-800 dark:text-gray-200 flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                Checklists
                            </h4>
                            <button type="button"
                                    @click="addChecklistGroup()"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400 flex items-center gap-1 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                Tambah Checklist
                            </button>
                        </div>

                        {{-- Checklist Groups Loop --}}
                        <div class="space-y-4">
                            <template x-for="(cl, cIndex) in task.checklists" :key="cl.id || cIndex">
                                <div class="bg-gray-50/70 dark:bg-gray-850 rounded-xl border border-gray-200/80 dark:border-gray-800 p-4 space-y-3">
                                    {{-- Group Header --}}
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex-1">
                                            <input type="text"
                                                   x-model="cl.title"
                                                   @change="updateChecklistGroup(cl)"
                                                   placeholder="Judul Checklist..."
                                                   class="font-semibold text-sm text-gray-800 dark:text-gray-100 bg-transparent border-0 border-b border-transparent focus:border-blue-500 focus:ring-0 p-0">
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-xs text-gray-500 font-medium"
                                                  x-text="getChecklistProgress(cl).text"></span>
                                            <button type="button"
                                                    @click="deleteChecklistGroup(cl, cIndex)"
                                                    title="Hapus checklist ini"
                                                    class="text-gray-400 hover:text-red-500 p-1 rounded transition">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>

                                    {{-- Progress Bar --}}
                                    <div class="w-full bg-gray-200 dark:bg-gray-700 h-1.5 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full transition-all duration-300"
                                             :class="getChecklistProgress(cl).pct === 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                                             :style="'width: ' + getChecklistProgress(cl).pct + '%'"></div>
                                    </div>

                                    {{-- Items List --}}
                                    <div class="space-y-1.5">
                                        <template x-for="(item, iIndex) in cl.items" :key="item.id || iIndex">
                                            <div class="group/item flex items-center justify-between gap-2 p-1.5 rounded-lg hover:bg-white dark:hover:bg-gray-800 transition">
                                                <label class="flex items-center gap-2.5 flex-1 cursor-pointer">
                                                    <input type="checkbox"
                                                           :checked="item.is_done"
                                                           @change="toggleChecklistItem(cl, item)"
                                                           class="w-4 h-4 rounded text-blue-600 focus:ring-blue-500 border-gray-300 dark:border-gray-600 dark:bg-gray-700">
                                                    <span class="text-xs text-gray-700 dark:text-gray-300 transition"
                                                          :class="item.is_done ? 'line-through text-gray-400 dark:text-gray-500' : ''"
                                                          x-text="item.title"></span>
                                                </label>
                                                <button type="button"
                                                        @click="deleteChecklistItem(cl, item, iIndex)"
                                                        class="opacity-0 group-hover/item:opacity-100 text-gray-400 hover:text-red-500 p-0.5 rounded transition">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        </template>
                                    </div>

                                    {{-- Quick Add Item Input --}}
                                    <div class="pt-1">
                                        <form @submit.prevent="addChecklistItem(cl, $event)" class="flex items-center gap-2">
                                            <input type="text"
                                                   name="item_title"
                                                   placeholder="+ Tambah item (Tekan Enter)..."
                                                   class="flex-1 text-xs bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-1 focus:ring-blue-500">
                                            <button type="submit" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold transition">
                                                Tambah
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Tabs: Notes (Description) & Attachments --}}
                    <div class="p-6 space-y-4">
                        <div class="flex items-center gap-4 border-b border-gray-200 dark:border-gray-800">
                            <button type="button"
                                    @click="activeTab = 'notes'"
                                    :class="activeTab === 'notes' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="pb-2 text-xs border-b-2 transition flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"/></svg>
                                Catatan & Deskripsi
                            </button>
                            <button type="button"
                                    @click="activeTab = 'attachments'"
                                    :class="activeTab === 'attachments' ? 'border-blue-600 text-blue-600 dark:text-blue-400 font-semibold' : 'border-transparent text-gray-500 hover:text-gray-700 dark:hover:text-gray-300'"
                                    class="pb-2 text-xs border-b-2 transition flex items-center gap-1.5">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                Lampiran & Tautan
                                <span class="ml-1 px-1.5 py-0.2 rounded-full text-[10px] bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400"
                                      x-text="task.attachments ? task.attachments.length : 0"></span>
                            </button>
                        </div>

                        {{-- Tab: Notes --}}
                        <div x-show="activeTab === 'notes'" class="space-y-4">
                            <div>
                                <textarea x-model="task.description"
                                          @input="markDirty()"
                                          rows="5"
                                          placeholder="Tulis catatan, instruksi, atau detail task di sini..."
                                          class="w-full text-xs text-gray-700 dark:text-gray-200 bg-gray-50/50 dark:bg-gray-800/40 border border-gray-200 dark:border-gray-700 rounded-xl p-3 focus:outline-none focus:ring-2 focus:ring-blue-500 transition leading-relaxed"></textarea>
                            </div>

                            {{-- Completion Notes (Shown especially when status is Done) --}}
                            <div x-show="task.status === 'done'" class="p-3.5 bg-emerald-50/60 dark:bg-emerald-950/20 border border-emerald-200 dark:border-emerald-800/50 rounded-xl space-y-1.5">
                                <label class="block text-xs font-semibold text-emerald-800 dark:text-emerald-400">Deskripsi Penyelesaian (Completion Notes)</label>
                                <textarea x-model="task.completion_notes"
                                          @input="markDirty()"
                                          rows="2"
                                          placeholder="Catatan hasil pengerjaan atau serah terima..."
                                          class="w-full text-xs text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-900 border border-emerald-300 dark:border-emerald-700 rounded-lg p-2.5 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                            </div>
                        </div>

                        {{-- Tab: Attachments --}}
                        <div x-show="activeTab === 'attachments'" class="space-y-4">
                            {{-- Attachment Action Buttons --}}
                            <div class="flex items-center gap-2">
                                <label class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 hover:bg-blue-100 dark:bg-blue-950/40 dark:hover:bg-blue-900/50 text-blue-700 dark:text-blue-300 rounded-lg text-xs font-semibold transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                                    Upload File
                                    <input type="file" class="hidden" @change="uploadAttachmentFile($event)">
                                </label>

                                <div class="relative" x-data="{ open: false, url: '', name: '' }">
                                    <button type="button"
                                            @click="open = !open"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-semibold transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        Tambah Link URL
                                    </button>

                                    <div x-show="open"
                                         @click.outside="open = false"
                                         x-cloak
                                         class="absolute left-0 top-full mt-1 w-72 bg-white dark:bg-gray-800 rounded-xl shadow-xl border border-gray-100 dark:border-gray-700 p-3 z-50 space-y-2">
                                        <input type="url"
                                               x-model="url"
                                               placeholder="https://..."
                                               class="w-full text-xs px-2.5 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                                        <input type="text"
                                               x-model="name"
                                               placeholder="Judul / Nama Tautan (opsional)..."
                                               class="w-full text-xs px-2.5 py-1.5 border border-gray-200 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900">
                                        <div class="flex justify-end gap-1.5 pt-1">
                                            <button type="button" @click="open = false; url = ''; name = ''" class="px-2.5 py-1 text-xs text-gray-500">Batal</button>
                                            <button type="button" @click="addAttachmentLink(url, name); open = false; url = ''; name = ''" class="px-3 py-1 bg-blue-600 text-white rounded-md text-xs font-semibold">Simpan</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Attachments List --}}
                            <div class="space-y-2">
                                <template x-for="(att, aIndex) in task.attachments" :key="att.id">
                                    <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-850 rounded-xl border border-gray-200/80 dark:border-gray-800 hover:border-gray-300 transition">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-9 h-9 rounded-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 flex items-center justify-center shrink-0">
                                                <template x-if="att.type === 'link'">
                                                    <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                </template>
                                                <template x-if="att.type === 'file'">
                                                    <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                                </template>
                                            </div>
                                            <div class="min-w-0">
                                                <a :href="att.url || ('/storage/' + att.file_path)"
                                                   target="_blank"
                                                   class="text-xs font-semibold text-gray-800 dark:text-gray-200 hover:text-blue-600 dark:hover:text-blue-400 block truncate"
                                                   x-text="att.file_name"></a>
                                                <div class="flex items-center gap-2 text-[11px] text-gray-400">
                                                    <span x-text="att.type === 'link' ? 'Tautan Eksternal' : formatFileSize(att.file_size)"></span>
                                                    <span>•</span>
                                                    <span x-text="formatDate(att.created_at)"></span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="flex items-center gap-1 shrink-0">
                                            {{-- Make cover button if image --}}
                                            <template x-if="isImageAttachment(att)">
                                                <button type="button"
                                                        @click="setAsCover(att)"
                                                        title="Jadikan Cover Task"
                                                        class="p-1 text-gray-400 hover:text-blue-600 rounded">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                </button>
                                            </template>
                                            <button type="button"
                                                    @click="deleteAttachment(att, aIndex)"
                                                    title="Hapus Lampiran"
                                                    class="p-1 text-gray-400 hover:text-red-500 rounded transition">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>

                                <template x-if="!task.attachments || task.attachments.length === 0">
                                    <div class="text-center py-6 text-gray-400 text-xs border border-dashed border-gray-200 dark:border-gray-800 rounded-xl">
                                        Belum ada lampiran atau link untuk task ini.
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- RIGHT COLUMN (Activity & Discussion: 30% - Collapsible) --}}
                <div x-show="rightPanelOpen"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-x-4"
                     x-transition:enter-end="opacity-100 translate-x-0"
                     class="w-80 lg:w-96 border-l border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/60 flex flex-col shrink-0">

                    {{-- Right Header --}}
                    <div class="p-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between">
                        <h4 class="text-xs font-bold text-gray-800 dark:text-gray-200 uppercase tracking-wider flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                            Aktivitas & Diskusi
                        </h4>
                        <button type="button" @click="rightPanelOpen = false" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Google Meet Box --}}
                    <div class="p-4 border-b border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-850">
                        <template x-if="task.google_meet_link">
                            <div class="space-y-2">
                                <a :href="task.google_meet_link"
                                   target="_blank"
                                   class="w-full py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold flex items-center justify-center gap-2 shadow-sm transition">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                    Join Google Meet
                                </a>
                                <p class="text-[11px] text-gray-400 text-center truncate" x-text="task.google_meet_link"></p>
                            </div>
                        </template>
                        <template x-if="!task.google_meet_link">
                            <div>
                                @if(!auth()->user()->hasRole('client') && $project->google_meet_enabled)
                                <button type="button"
                                        @click="createMeeting()"
                                        :disabled="meetingCreating"
                                        class="w-full py-2 px-3 border border-gray-200 dark:border-gray-700 hover:border-blue-500 text-gray-700 dark:text-gray-200 rounded-lg text-xs font-semibold flex items-center justify-center gap-2 bg-white dark:bg-gray-800 transition">
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 14.5v-9l6 4.5-6 4.5z"/></svg>
                                    <span x-text="meetingCreating ? 'Membuat Meeting...' : 'Buat Google Meet'"></span>
                                </button>
                                @else
                                <div class="text-[11px] text-gray-400 text-center">Google Meet belum dijadwalkan.</div>
                                @endif
                            </div>
                        </template>
                    </div>

                    {{-- Activity / Comments Feed (Scrollable) --}}
                    <div class="flex-1 overflow-y-auto p-4 space-y-4 text-xs">
                        <template x-for="c in task.comments" :key="c.id">
                            <div class="flex items-start gap-2.5">
                                <div class="w-7 h-7 rounded-full bg-blue-600 text-white flex items-center justify-center font-bold text-[11px] shrink-0"
                                     x-text="c.user ? c.user.name.charAt(0) : 'U'"></div>
                                <div class="flex-1 bg-white dark:bg-gray-800 rounded-xl p-3 border border-gray-100 dark:border-gray-800 shadow-2xs space-y-1">
                                    <div class="flex items-center justify-between gap-1">
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="c.user ? c.user.name : 'Pengguna'"></span>
                                        <span class="text-[10px] text-gray-400" x-text="formatDate(c.created_at)"></span>
                                    </div>
                                    <p class="text-gray-700 dark:text-gray-300 leading-relaxed whitespace-pre-wrap" x-text="c.body"></p>
                                </div>
                            </div>
                        </template>

                        <template x-if="!task.comments || task.comments.length === 0">
                            <div class="text-center py-8 text-gray-400">
                                Belum ada komentar atau diskusi.
                            </div>
                        </template>
                    </div>

                    {{-- Add Comment Box --}}
                    <div class="p-3 border-t border-gray-100 dark:border-gray-800 bg-white dark:bg-gray-850">
                        <form @submit.prevent="postComment($event)" class="space-y-2">
                            <textarea name="body"
                                      rows="2"
                                      required
                                      placeholder="Tulis tanggapan atau komentar..."
                                      class="w-full text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-lg p-2 focus:outline-none focus:ring-1 focus:ring-blue-500"></textarea>
                            <div class="flex items-center justify-end">
                                <button type="submit"
                                        :disabled="commentPosting"
                                        class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-xs transition disabled:opacity-50">
                                    <span x-text="commentPosting ? 'Mengirim...' : 'Kirim Komentar'"></span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-3.5 border-t border-gray-100 dark:border-gray-800 bg-gray-50 dark:bg-gray-900/90 flex items-center justify-between shrink-0">
                <div>
                    @if(!auth()->user()->hasRole('client'))
                    <button type="button"
                            @click="deleteTask()"
                            class="text-xs text-red-500 hover:text-red-700 font-medium flex items-center gap-1 transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        Hapus Task
                    </button>
                    @endif
                </div>

                <div class="flex items-center gap-2">
                    <button type="button"
                            @click="attemptClose()"
                            class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Batal
                    </button>

                    <button type="button"
                            @click="saveTask()"
                            :disabled="saving"
                            class="px-5 py-2 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg shadow-sm transition flex items-center gap-1.5 disabled:opacity-50">
                        <svg x-show="saving" class="w-3.5 h-3.5 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                        <span x-text="saving ? 'Menyimpan...' : 'Simpan Perubahan'"></span>
                    </button>
                </div>
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
        activeTab: 'notes',
        meetingCreating: false,
        commentPosting: false,

        task: {},
        columns: @json($columns ?? []),
        projectLabels: @json($projectLabels ?? []),
        assignableUsers: @json($assignableUsers ?? []),
        milestones: @json($milestones ?? []),

        get currentColumn() {
            return this.columns.find(c => c.id === this.task.board_column_id);
        },

        get coverUrl() {
            if (!this.task.cover_image_path) return '';
            return '/storage/' + this.task.cover_image_path;
        },

        initModal() {
            // Global helper
            window.openTaskModal = (taskId) => {
                this.openModal(taskId);
            };
        },

        async openModal(taskId) {
            this.isOpen = true;
            this.loading = true;
            this.isDirty = false;

            try {
                const res = await fetch(`/projects/{{ $project->id }}/tasks/${taskId}/detail`, {
                    headers: { 'Accept': 'application/json' }
                });
                if (!res.ok) throw new Error('Gagal mengambil data task');
                const data = await res.json();
                this.task = data.task;
                if (!this.task.members) this.task.members = [];
                if (!this.task.labels) this.task.labels = [];
                if (!this.task.checklists) this.task.checklists = [];
                if (!this.task.attachments) this.task.attachments = [];
                if (!this.task.comments) this.task.comments = [];
            } catch (err) {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Gagal memuat task.' });
                this.isOpen = false;
            } finally {
                this.loading = false;
            }
        },

        attemptClose() {
            if (this.isDirty) {
                Swal.fire({
                    title: 'Perubahan belum disimpan',
                    text: 'Anda memiliki perubahan yang belum disimpan. Yakin ingin menutup?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Tutup tanpa simpan',
                    cancelButtonText: 'Tetap di sini',
                    confirmButtonColor: '#ef4444'
                }).then(result => {
                    if (result.isConfirmed) {
                        this.forceClose();
                    }
                });
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
                }
            } catch (err) {
                console.error(err);
            }
        },

        labelClasses(color) {
            const map = {
                blue:   { badge: 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300', dot: 'bg-blue-500' },
                green:  { badge: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300', dot: 'bg-emerald-500' },
                red:    { badge: 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-300', dot: 'bg-red-500' },
                yellow: { badge: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300', dot: 'bg-yellow-500' },
                orange: { badge: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300', dot: 'bg-orange-500' },
                purple: { badge: 'bg-purple-100 text-purple-800 dark:bg-purple-900/40 dark:text-purple-300', dot: 'bg-purple-500' },
                teal:   { badge: 'bg-teal-100 text-teal-800 dark:bg-teal-900/40 dark:text-teal-300', dot: 'bg-teal-500' },
            };
            return map[color] || { badge: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300', dot: 'bg-gray-400' };
        },

        // --- Checklists ---
        async addChecklistGroup() {
            try {
                const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ title: 'Checklist Baru' })
                });
                if (res.ok) {
                    const cl = await res.json();
                    if (!cl.items) cl.items = [];
                    this.task.checklists.push(cl);
                }
            } catch (err) {
                console.error(err);
            }
        },

        async updateChecklistGroup(cl) {
            if (!cl.id) return;
            fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/checklists/${cl.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ title: cl.title })
            });
        },

        async deleteChecklistGroup(cl, index) {
            if (!cl.id) {
                this.task.checklists.splice(index, 1);
                return;
            }
            if (!confirm('Hapus seluruh checklist ini?')) return;
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
                }
            } catch (err) {
                console.error(err);
            }
        },

        async addChecklistItem(cl, event) {
            const form = event.target;
            const input = form.querySelector('input[name="item_title"]');
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
            if (items.length === 0) return { pct: 0, text: '0 item' };
            const done = items.filter(i => i.is_done).length;
            const pct = Math.round((done / items.length) * 100);
            return { pct, text: `${done}/${items.length} selesai (${pct}%)` };
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
                    Swal.fire({ icon: 'success', title: 'Cover berhasil diperbarui', timer: 1200, showConfirmButton: false });
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
                }
            } catch (err) {
                console.error(err);
            }
        },

        async setAsCover(att) {
            if (att.file_path) {
                this.task.cover_image_path = att.file_path;
                this.markDirty();
                Swal.fire({ icon: 'success', title: 'Cover dipilih', timer: 1000, showConfirmButton: false });
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
                }
            } catch (err) {
                console.error(err);
            }
        },

        async addAttachmentLink(url, displayName) {
            if (!url.trim()) return;
            try {
                const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}/attachments`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ type: 'link', url: url.trim(), display_name: displayName.trim() })
                });
                if (res.ok) {
                    const data = await res.json();
                    this.task.attachments.unshift(data.attachment);
                }
            } catch (err) {
                console.error(err);
            }
        },

        async deleteAttachment(att, index) {
            if (!confirm('Hapus lampiran ini?')) return;
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

        // --- Comments ---
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
                    // re-fetch detail or push local comment
                    this.task.comments.push({
                        id: Date.now(),
                        body: body,
                        created_at: new Date().toISOString(),
                        user: { name: '{{ auth()->user()->name }}' }
                    });
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.commentPosting = false;
            }
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
                    Swal.fire({ icon: 'success', title: 'Meeting Berhasil Dibuat', timer: 1500, showConfirmButton: false });
                    // Refresh modal
                    this.openModal(this.task.id);
                } else {
                    Swal.fire({ icon: 'error', title: 'Gagal', text: 'Tidak dapat membuat meeting.' });
                }
            } catch (err) {
                console.error(err);
            } finally {
                this.meetingCreating = false;
            }
        },

        // --- Save Changes ---
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
                start_date: this.task.start_date,
                due_date: this.task.due_date,
                estimated_hours: this.task.estimated_hours,
                cover_image_path: this.task.cover_image_path,
                member_ids: (this.task.members || []).map(m => m.id),
                label_ids: (this.task.labels || []).map(l => l.id),
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
                    this.isDirty = false;
                    Swal.fire({
                        icon: 'success',
                        title: 'Tersimpan',
                        text: 'Perubahan task berhasil disimpan.',
                        timer: 1200,
                        showConfirmButton: false
                    });
                    this.forceClose();
                    // Dispatch event to refresh board or reload page smoothly
                    window.dispatchEvent(new CustomEvent('task-updated', { detail: { taskId: this.task.id } }));
                    setTimeout(() => window.location.reload(), 300);
                } else {
                    const errData = await res.json();
                    Swal.fire({ icon: 'error', title: 'Gagal Menyimpan', text: errData.message || 'Periksa kembali data Anda.' });
                }
            } catch (err) {
                console.error(err);
                Swal.fire({ icon: 'error', title: 'Error', text: 'Terjadi kesalahan sistem.' });
            } finally {
                this.saving = false;
            }
        },

        // --- Delete Task ---
        async deleteTask() {
            if (!confirm(`Yakin ingin menghapus task "${this.task.title}" secara permanen?`)) return;
            try {
                const res = await fetch(`/projects/{{ $project->id }}/tasks/${this.task.id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json'
                    }
                });
                if (res.ok) {
                    this.forceClose();
                    window.location.reload();
                }
            } catch (err) {
                console.error(err);
            }
        },

        // --- Formatting Helpers ---
        formatDate(dateStr) {
            if (!dateStr) return '';
            const d = new Date(dateStr);
            return d.toLocaleDateString('id-ID', { day: 'numeric', month: 'short' });
        },

        formatFileSize(bytes) {
            if (!bytes) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
        }
    };
}
</script>
