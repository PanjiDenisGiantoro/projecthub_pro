{{-- File Manager Project: Unified Files, Attachments & Links (Task & Standalone) --}}
@php
    $canManageFiles = !auth()->user()->hasRole('client');

    // Helper tipe file & style badge
    $typeMeta = function ($item) {
        if (($item['type'] ?? '') === 'link') {
            $url = strtolower($item['url'] ?? '');
            $icon = 'link';
            $badgeColor = 'bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400 border-blue-200/60 dark:border-blue-800/50';
            if (str_contains($url, 'github.com')) {
                $icon = 'github';
                $badgeColor = 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-200 border-gray-200 dark:border-gray-700';
            } elseif (str_contains($url, 'figma.com')) {
                $icon = 'figma';
                $badgeColor = 'bg-purple-50 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400 border-purple-200/60 dark:border-purple-800/50';
            } elseif (str_contains($url, 'drive.google.com') || str_contains($url, 'docs.google.com')) {
                $icon = 'drive';
                $badgeColor = 'bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 border-amber-200/60 dark:border-amber-800/50';
            }
            return [
                'ext' => 'LINK',
                'color' => 'bg-blue-50 text-blue-600 dark:bg-blue-950/50 dark:text-blue-400',
                'badge' => $badgeColor,
                'icon' => $icon,
            ];
        }

        $ext = strtolower($item['extension'] ?? pathinfo($item['name'] ?? '', PATHINFO_EXTENSION));
        [$color, $badgeColor, $icon] = match (true) {
            $ext === 'pdf' => ['bg-red-50 text-red-600 dark:bg-red-950/50 dark:text-red-400', 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300 border-red-200/60 dark:border-red-800/50', 'pdf'],
            in_array($ext, ['doc', 'docx', 'txt', 'md', 'rtf']) => ['bg-sky-50 text-sky-600 dark:bg-sky-950/50 dark:text-sky-400', 'bg-sky-50 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300 border-sky-200/60 dark:border-sky-800/50', 'doc'],
            in_array($ext, ['xls', 'xlsx', 'csv']) => ['bg-emerald-50 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400', 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border-emerald-200/60 dark:border-emerald-800/50', 'sheet'],
            in_array($ext, ['ppt', 'pptx']) => ['bg-orange-50 text-orange-600 dark:bg-orange-950/50 dark:text-orange-400', 'bg-orange-50 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 border-orange-200/60 dark:border-orange-800/50', 'slide'],
            in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg']) => ['bg-purple-50 text-purple-600 dark:bg-purple-950/50 dark:text-purple-400', 'bg-purple-50 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300 border-purple-200/60 dark:border-purple-800/50', 'image'],
            in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz']) => ['bg-amber-50 text-amber-600 dark:bg-amber-950/50 dark:text-amber-400', 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 border-amber-200/60 dark:border-amber-800/50', 'archive'],
            in_array($ext, ['mp4', 'avi', 'mov', 'mp3', 'wav']) => ['bg-pink-50 text-pink-600 dark:bg-pink-950/50 dark:text-pink-400', 'bg-pink-50 text-pink-700 dark:bg-pink-900/40 dark:text-pink-300 border-pink-200/60 dark:border-pink-800/50', 'media'],
            default => ['bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-300', 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300 border-gray-200 dark:border-gray-700', 'file'],
        };

        return [
            'ext' => $ext !== '' ? strtoupper(substr($ext, 0, 4)) : 'FILE',
            'color' => $color,
            'badge' => $badgeColor,
            'icon' => $icon,
        ];
    };
@endphp

<div class="space-y-6"
     x-data="{
        search: '',
        sourceFilter: 'all', // 'all', 'task', 'standalone'
        typeFilter: 'all',   // 'all', 'file', 'link'
        taskFilter: 'all',   // 'all' or taskId
        activeFolder: 'All', // 'All', '__tasks__', or folder path
        view: (() => { try { return localStorage.getItem('project_files_view') || 'grid' } catch (e) { return 'grid' } })(),
        
        // Modals state
        showUploadModal: false,
        showLinkModal: false,
        showFolderModal: false,
        moveItem: null,
        moveFolder: '',
        newFolderName: '',
        
        // Upload config
        uploadDestination: 'standalone', // 'standalone' | 'task'
        selectedUploadTask: '{{ $projectTasks->first()?->id ?? '' }}',
        uploadFolder: 'General',
        
        // Link config
        linkDestination: 'standalone', // 'standalone' | 'task'
        selectedLinkTask: '{{ $projectTasks->first()?->id ?? '' }}',
        linkFolder: 'General',

        // Feedback state
        copiedId: null,

        // Item catalog for fast counts
        items: @js($unifiedItems->map(fn($it) => [
            'id' => $it['id'],
            'raw_id' => $it['raw_id'],
            'source' => $it['source'],
            'type' => $it['type'],
            'folder' => $it['folder'],
            'name' => $it['name'],
            'url' => $it['url'],
            'desc' => $it['description'] ?? '',
            'taskId' => $it['task']['id'] ?? null,
            'taskTitle' => $it['task']['title'] ?? '',
            'uploader' => $it['uploader_name'] ?? '',
        ])->values()),

        setView(v) {
            this.view = v;
            try { localStorage.setItem('project_files_view', v); } catch (e) {}
        },

        copyLink(url, id) {
            if (!navigator.clipboard) return;
            navigator.clipboard.writeText(url).then(() => {
                this.copiedId = id;
                setTimeout(() => { if (this.copiedId === id) this.copiedId = null; }, 2000);
            });
        },

        openTaskModal(taskId) {
            if (!taskId) return;
            if (typeof window.openTask === 'function') {
                window.openTask(taskId);
            } else if (typeof window.openTaskModal === 'function') {
                window.openTaskModal(taskId);
            }
        },

        isVisible(item) {
            // 1. Search text
            if (this.search.trim() !== '') {
                const q = this.search.toLowerCase().trim();
                const nameMatch = (item.name || '').toLowerCase().includes(q);
                const urlMatch = (item.url || '').toLowerCase().includes(q);
                const descMatch = (item.desc || '').toLowerCase().includes(q);
                const taskMatch = (item.taskTitle || '').toLowerCase().includes(q) || String(item.taskId || '').includes(q);
                const uploaderMatch = (item.uploader || '').toLowerCase().includes(q);
                if (!nameMatch && !urlMatch && !descMatch && !taskMatch && !uploaderMatch) {
                    return false;
                }
            }

            // 2. Source filter
            if (this.sourceFilter !== 'all' && item.source !== this.sourceFilter) {
                return false;
            }

            // 3. Type filter
            if (this.typeFilter !== 'all' && item.type !== this.typeFilter) {
                return false;
            }

            // 4. Task filter
            if (this.taskFilter !== 'all') {
                if (!item.taskId || String(item.taskId) !== String(this.taskFilter)) {
                    return false;
                }
            }

            // 5. Folder filter
            if (this.activeFolder !== 'All') {
                if (this.activeFolder === '__tasks__') {
                    if (item.source !== 'task') return false;
                } else {
                    if (item.source !== 'standalone') return false;
                    const f = item.folder || 'General';
                    if (f !== this.activeFolder && !f.startsWith(this.activeFolder + '/')) {
                        return false;
                    }
                }
            }

            return true;
        },

        get visibleCount() {
            return this.items.filter(i => this.isVisible(i)).length;
        },

        resetFilters() {
            this.search = '';
            this.sourceFilter = 'all';
            this.typeFilter = 'all';
            this.taskFilter = 'all';
            this.activeFolder = 'All';
        }
     }">

    {{-- ============================================================
         1. TOP KPI SUMMARY CARDS
         ============================================================ --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3.5">
        {{-- Total Resources --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4 flex flex-col justify-between transition-all hover:bg-gray-50/80 dark:hover:bg-gray-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Total Berkas</span>
                <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-gray-900 dark:text-white leading-none">{{ $totalItemsCount }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 truncate">{{ $filesCount }} file · {{ $linksCount }} link</p>
            </div>
        </div>

        {{-- Task Attachments --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4 flex flex-col justify-between transition-all hover:bg-gray-50/80 dark:hover:bg-gray-800 cursor-pointer"
             @click="sourceFilter = 'task'; activeFolder = 'All'">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Lampiran Task</span>
                <span class="w-8 h-8 rounded-xl bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 leading-none">{{ $taskItemsCount }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 truncate">Dari board & task</p>
            </div>
        </div>

        {{-- Standalone Project Files --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4 flex flex-col justify-between transition-all hover:bg-gray-50/80 dark:hover:bg-gray-800 cursor-pointer"
             @click="sourceFilter = 'standalone'; activeFolder = 'All'">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">File Standalone</span>
                <span class="w-8 h-8 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 leading-none">{{ $standaloneCount }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 truncate">Mandiri proyek</p>
            </div>
        </div>

        {{-- External Links --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4 flex flex-col justify-between transition-all hover:bg-gray-50/80 dark:hover:bg-gray-800 cursor-pointer"
             @click="typeFilter = 'link'; activeFolder = 'All'">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Tautan / Links</span>
                <span class="w-8 h-8 rounded-xl bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-violet-600 dark:text-violet-400 leading-none">{{ $linksCount }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 truncate">Figma, GitHub, Docs</p>
            </div>
        </div>

        {{-- Total Storage --}}
        <div class="col-span-2 sm:col-span-1 bg-white dark:bg-gray-850 rounded-2xl p-4 flex flex-col justify-between transition-all hover:bg-gray-50/80 dark:hover:bg-gray-800">
            <div class="flex items-center justify-between">
                <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Kapasitas Storage</span>
                <span class="w-8 h-8 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/></svg>
                </span>
            </div>
            <div class="mt-3">
                <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 leading-none">{{ $humanTotalStorage }}</p>
                <p class="text-[11px] text-gray-400 dark:text-gray-500 mt-1 truncate">Total ukuran berkas</p>
            </div>
        </div>
    </div>

    {{-- ============================================================
         2. CONTROL & ACTION TOOLBAR
         ============================================================ --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl p-4 space-y-3.5">
        <div class="flex items-center justify-between gap-3 flex-wrap">
            {{-- Search & Primary Filters --}}
            <div class="flex items-center gap-2.5 flex-1 min-w-[280px]">
                {{-- Search Box --}}
                <div class="relative flex-1 max-w-md">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11A6 6 0 115 11a6 6 0 0112 0z"/></svg>
                    <input type="search" x-model="search" placeholder="Cari nama berkas, link, atau task..."
                           class="w-full pl-9 pr-8 py-2 bg-gray-50/60 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/80 rounded-xl text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    <button type="button" x-show="search" @click="search = ''" class="absolute right-2.5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Task Filter Selector --}}
                <div class="relative min-w-[160px] max-w-[220px]">
                    <select x-model="taskFilter"
                            class="w-full pl-3 pr-8 py-2 bg-gray-50/60 dark:bg-gray-800/60 border border-gray-200 dark:border-gray-700/80 rounded-xl text-xs font-medium text-gray-700 dark:text-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500 cursor-pointer appearance-none">
                        <option value="all">Semua Task ({{ $projectTasks->count() }})</option>
                        @foreach($projectTasks as $pt)
                            <option value="{{ $pt->id }}">#{{ $pt->id }} — {{ Str::limit($pt->title, 26) }}</option>
                        @endforeach
                    </select>
                    <svg class="w-3.5 h-3.5 text-gray-400 absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </div>
            </div>

            {{-- Action Buttons & View Toggle --}}
            <div class="flex items-center gap-2 flex-wrap ml-auto">
                {{-- View Grid/List Switcher --}}
                <div class="inline-flex rounded-xl border border-gray-200 dark:border-gray-700 p-0.5 bg-gray-50/50 dark:bg-gray-800/50">
                    <button type="button" @click="setView('grid')" title="Tampilan Grid"
                            :class="view === 'grid' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-2xs font-medium' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zm10 0a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/></svg>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button type="button" @click="setView('list')" title="Tampilan List"
                            :class="view === 'list' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-2xs font-medium' : 'text-gray-400 hover:text-gray-600 dark:hover:text-gray-300'"
                            class="flex items-center gap-1.5 px-2.5 py-1.5 rounded-lg text-xs transition-all">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        <span class="hidden sm:inline">List</span>
                    </button>
                </div>

                @if($canManageFiles)
                    {{-- Add Link Button --}}
                    <button type="button" @click="showLinkModal = true"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-semibold text-gray-700 dark:text-gray-200 bg-gray-100 hover:bg-gray-200/80 dark:bg-gray-800 dark:hover:bg-gray-750 transition-colors">
                        <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                        <span>Tambah Link</span>
                    </button>

                    {{-- Upload File Button --}}
                    <button type="button" @click="showUploadModal = true"
                            class="inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        <span>Upload File</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Filter Pills: Source & Types --}}
        <div class="flex items-center justify-between gap-3 pt-2 border-t border-gray-100 dark:border-gray-750/70 flex-wrap">
            {{-- Source Filter Pills --}}
            <div class="flex items-center gap-1.5 overflow-x-auto py-0.5">
                <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mr-1">Sumber:</span>
                
                <button type="button" @click="sourceFilter = 'all'; activeFolder = 'All'"
                        :class="sourceFilter === 'all' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="px-2.5 py-1 rounded-lg text-xs transition-colors whitespace-nowrap">
                    Semua ({{ $totalItemsCount }})
                </button>

                <button type="button" @click="sourceFilter = 'task'; activeFolder = 'All'"
                        :class="sourceFilter === 'task' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs transition-colors whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                    <span>Lampiran Task ({{ $taskItemsCount }})</span>
                </button>

                <button type="button" @click="sourceFilter = 'standalone'; activeFolder = 'All'"
                        :class="sourceFilter === 'standalone' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-medium' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs transition-colors whitespace-nowrap">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                    <span>File Proyek Standalone ({{ $standaloneCount }})</span>
                </button>
            </div>

            {{-- Type Filter Pills --}}
            <div class="flex items-center gap-1.5">
                <span class="text-[11px] font-semibold text-gray-400 dark:text-gray-500 uppercase tracking-wider mr-1">Tipe:</span>
                
                <button type="button" @click="typeFilter = 'all'"
                        :class="typeFilter === 'all' ? 'bg-gray-200/80 dark:bg-gray-700 text-gray-900 dark:text-white font-medium' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="px-2.5 py-1 rounded-lg text-xs transition-colors">
                    Semua
                </button>
                <button type="button" @click="typeFilter = 'file'"
                        :class="typeFilter === 'file' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-medium' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="px-2.5 py-1 rounded-lg text-xs transition-colors">
                    File ({{ $filesCount }})
                </button>
                <button type="button" @click="typeFilter = 'link'"
                        :class="typeFilter === 'link' ? 'bg-violet-50 dark:bg-violet-900/30 text-violet-600 dark:text-violet-400 font-medium' : 'text-gray-500 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800'"
                        class="px-2.5 py-1 rounded-lg text-xs transition-colors">
                    Link ({{ $linksCount }})
                </button>
            </div>
        </div>
    </div>

    {{-- ============================================================
         3. MAIN CONTENT: FOLDER SIDEBAR & UNIFIED ITEMS
         ============================================================ --}}
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-5 items-start">
        {{-- Sidebar: Folder & Kategori --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4 lg:sticky lg:top-4 space-y-4">
            {{-- Navigation Items --}}
            <div>
                <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider px-2 mb-2">Navigasi Utama</p>
                <div class="space-y-1">
                    {{-- All items --}}
                    <button type="button" @click="activeFolder = 'All'; sourceFilter = 'all'"
                            :class="activeFolder === 'All' && sourceFilter === 'all' ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60'"
                            class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2 truncate">
                            <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                            <span class="truncate">Semua Koleksi</span>
                        </span>
                        <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">{{ $totalItemsCount }}</span>
                    </button>

                    {{-- Task Attachments Virtual Folder --}}
                    <button type="button" @click="activeFolder = '__tasks__'; sourceFilter = 'task'"
                            :class="activeFolder === '__tasks__' ? 'bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60'"
                            class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2 truncate">
                            <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            <span class="truncate">Lampiran Task</span>
                        </span>
                        <span class="text-[11px] px-1.5 py-0.5 rounded-full bg-indigo-50 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400">{{ $taskItemsCount }}</span>
                    </button>
                </div>
            </div>

            {{-- Standalone Project Folders --}}
            <div class="pt-3 border-t border-gray-100 dark:border-gray-750/70">
                <div class="flex items-center justify-between px-2 mb-2">
                    <p class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">Folder Mandiri</p>
                    @if($canManageFiles)
                        <button type="button" @click="showFolderModal = true" title="Buat folder baru"
                                class="text-xs text-blue-600 hover:text-blue-700 font-semibold flex items-center gap-0.5">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Baru</span>
                        </button>
                    @endif
                </div>

                <div class="space-y-0.5">
                    {{-- Default Root Folder for Standalone --}}
                    <button type="button" @click="activeFolder = 'General'; sourceFilter = 'standalone'"
                            :class="activeFolder === 'General' ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60'"
                            class="w-full text-left px-3 py-1.5 rounded-xl text-xs flex items-center justify-between transition-colors">
                        <span class="flex items-center gap-2 truncate">
                            <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                            <span class="truncate">General</span>
                        </span>
                        <span class="text-[11px] text-gray-400">{{ $standaloneFiles->where('folder', 'General')->count() }}</span>
                    </button>

                    @foreach($folders as $folderName)
                        @if($folderName !== 'General')
                            <button type="button" @click="activeFolder = @js($folderName); sourceFilter = 'standalone'"
                                    :class="activeFolder === @js($folderName) ? 'bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 font-semibold' : 'text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60'"
                                    class="w-full text-left px-3 py-1.5 rounded-xl text-xs flex items-center justify-between transition-colors">
                                <span class="flex items-center gap-2 truncate">
                                    <svg class="w-4 h-4 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                    <span class="truncate">{{ $folderName }}</span>
                                </span>
                                <span class="text-[11px] text-gray-400">{{ $standaloneFiles->where('folder', $folderName)->count() }}</span>
                            </button>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Main Area: Grid & List of Unified Items --}}
        <div class="lg:col-span-3 space-y-4 min-w-0">
            {{-- Breadcrumb & Active Filter State --}}
            <div class="flex items-center justify-between gap-3 text-xs text-gray-500 dark:text-gray-400 flex-wrap">
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="font-medium text-gray-700 dark:text-gray-300">Menampilkan:</span>
                    <span class="font-bold text-gray-900 dark:text-white" x-text="visibleCount + ' item'"></span>
                    <template x-if="sourceFilter !== 'all'">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                            <span x-text="sourceFilter === 'task' ? 'Lampiran Task' : 'Standalone'"></span>
                            <button type="button" @click="sourceFilter = 'all'" class="hover:text-red-500">&times;</button>
                        </span>
                    </template>
                    <template x-if="typeFilter !== 'all'">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300 font-medium">
                            <span x-text="typeFilter === 'link' ? 'Tautan' : 'Berkas'"></span>
                            <button type="button" @click="typeFilter = 'all'" class="hover:text-red-500">&times;</button>
                        </span>
                    </template>
                    <template x-if="activeFolder !== 'All'">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-emerald-50 dark:bg-emerald-950/50 text-emerald-700 dark:text-emerald-300 font-medium">
                            <span>Folder: <span x-text="activeFolder === '__tasks__' ? 'Task Attachments' : activeFolder"></span></span>
                            <button type="button" @click="activeFolder = 'All'" class="hover:text-red-500">&times;</button>
                        </span>
                    </template>
                    <template x-if="taskFilter !== 'all'">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 font-medium">
                            <span>Task #<span x-text="taskFilter"></span></span>
                            <button type="button" @click="taskFilter = 'all'" class="hover:text-red-500">&times;</button>
                        </span>
                    </template>
                </div>

                <template x-if="search || sourceFilter !== 'all' || typeFilter !== 'all' || activeFolder !== 'All' || taskFilter !== 'all'">
                    <button type="button" @click="resetFilters()" class="text-blue-600 dark:text-blue-400 hover:underline font-semibold">
                        Reset Semua Filter
                    </button>
                </template>
            </div>

            {{-- ============================================================
                 GRID VIEW
                 ============================================================ --}}
            <div x-show="view === 'grid'" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
                @foreach($unifiedItems as $item)
                    @php
                        $meta = $typeMeta($item);
                        $isTask = ($item['source'] ?? '') === 'task';
                        $hasTask = !empty($item['task']);
                    @endphp

                    <div x-show="isVisible({
                            id: @js($item['id']),
                            raw_id: @js($item['raw_id']),
                            source: @js($item['source']),
                            type: @js($item['type']),
                            folder: @js($item['folder']),
                            name: @js($item['name']),
                            url: @js($item['url']),
                            desc: @js($item['description'] ?? ''),
                            taskId: @js($item['task']['id'] ?? null),
                            taskTitle: @js($item['task']['title'] ?? ''),
                            uploader: @js($item['uploader_name'] ?? '')
                         })"
                         class="group bg-white dark:bg-gray-850 rounded-2xl flex flex-col justify-between overflow-hidden transition-all duration-200 hover:bg-gray-50/80 dark:hover:bg-gray-800">
                        
                        {{-- Top Thumbnail / Banner Preview --}}
                        <div class="relative h-36 bg-gray-50 dark:bg-gray-800/50 flex items-center justify-center overflow-hidden border-b border-gray-100 dark:border-gray-750">
                            @if(!empty($item['is_image']) && $item['url'])
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" class="w-full h-full block overflow-hidden">
                                    <img src="{{ $item['url'] }}" alt="{{ $item['name'] }}" loading="lazy"
                                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                                </a>
                            @elseif($item['type'] === 'link')
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="w-full h-full flex flex-col items-center justify-center p-4 text-center group-hover:bg-blue-50/20 dark:group-hover:bg-blue-900/10 transition-colors">
                                    @if($meta['icon'] === 'github')
                                        <svg class="w-12 h-12 text-gray-800 dark:text-gray-200" fill="currentColor" viewBox="0 0 24 24"><path fill-rule="evenodd" clip-rule="evenodd" d="M12 2C6.477 2 2 6.484 2 12.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.53 1.032 1.53 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0112 6.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.202 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.943.359.309.678.92.678 1.855 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0022 12.017C22 6.484 17.522 2 12 2z"/></svg>
                                    @elseif($meta['icon'] === 'figma')
                                        <svg class="w-12 h-12 text-purple-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M5 5.5A3.5 3.5 0 018.5 2H12v7H8.5A3.5 3.5 0 015 5.5zM12 2h3.5a3.5 3.5 0 110 7H12V2zM12 12.5a3.5 3.5 0 117 0 3.5 3.5 0 01-7 0zM5 12.5A3.5 3.5 0 018.5 9H12v7H8.5A3.5 3.5 0 015 12.5zM5 19.5A3.5 3.5 0 018.5 16H12v3.5a3.5 3.5 0 11-7 0z"/></svg>
                                    @elseif($meta['icon'] === 'drive')
                                        <svg class="w-12 h-12 text-amber-500" viewBox="0 0 24 24" fill="currentColor"><path d="M7.71 3.5L1.15 15l3.43 6 6.55-11.5L7.71 3.5zm4.86 6.5l3.43 6h6.86l-3.43-6h-6.86zm1.14 8l-3.43 6h13.72l3.43-6H13.71z"/></svg>
                                    @else
                                        <span class="w-14 h-14 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shadow-xs">
                                            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                        </span>
                                    @endif
                                    <span class="mt-2 text-[11px] font-medium text-gray-400 dark:text-gray-500 truncate max-w-[200px]">{{ parse_url($item['url'], PHP_URL_HOST) ?? 'External Link' }}</span>
                                </a>
                            @else
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="w-full h-full flex flex-col items-center justify-center p-4">
                                    <span class="w-14 h-16 rounded-xl flex flex-col items-center justify-center font-bold shadow-xs {{ $meta['color'] }}">
                                        <svg class="w-6 h-6 mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        <span class="text-[10px] tracking-wide">{{ $meta['ext'] }}</span>
                                    </span>
                                </a>
                            @endif

                            {{-- Source Badge (Overlay Top-Right) --}}
                            <div class="absolute top-2.5 right-2.5 flex items-center gap-1">
                                @if($isTask)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-indigo-600/90 text-white backdrop-blur-xs shadow-xs">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        <span>Task</span>
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-semibold bg-emerald-600/90 text-white backdrop-blur-xs shadow-xs">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                        <span>Standalone</span>
                                    </span>
                                @endif
                            </div>

                            {{-- Type Badge (Overlay Top-Left) --}}
                            <div class="absolute top-2.5 left-2.5">
                                @if($item['type'] === 'link')
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium bg-blue-600/90 text-white backdrop-blur-xs shadow-xs">
                                        🔗 Link
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10.5px] font-medium bg-gray-900/70 text-white backdrop-blur-xs shadow-xs">
                                        {{ $meta['ext'] }}
                                    </span>
                                @endif
                            </div>
                        </div>

                        {{-- Card Details --}}
                        <div class="p-4 flex-1 flex flex-col justify-between space-y-3">
                            <div>
                                {{-- Task Reference Link (Highlight) --}}
                                @if($hasTask)
                                    <div class="mb-2">
                                        <button type="button"
                                                @click.prevent.stop="openTaskModal({{ $item['task']['id'] }})"
                                                title="Klik untuk membuka detail task #{{ $item['task']['id'] }}"
                                                class="w-full text-left inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-medium bg-indigo-50/80 hover:bg-indigo-100 dark:bg-indigo-950/60 dark:hover:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 transition-colors border border-indigo-100 dark:border-indigo-800/50 group/task">
                                            <span class="w-1.5 h-1.5 rounded-full shrink-0 {{ ($item['task']['status'] ?? '') === 'done' ? 'bg-emerald-500' : 'bg-indigo-500' }}"></span>
                                            <span class="font-bold shrink-0">#{{ $item['task']['id'] }}</span>
                                            <span class="truncate">{{ $item['task']['title'] }}</span>
                                            <svg class="w-3.5 h-3.5 text-indigo-400 group-hover/task:translate-x-0.5 transition-transform ml-auto shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                    </div>
                                @else
                                    <div class="mb-2">
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-xl text-[11px] font-medium bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                                            <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                            <span class="truncate">Folder: {{ $item['folder'] ?: 'General' }}</span>
                                        </span>
                                    </div>
                                @endif

                                {{-- Item Name --}}
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
                                   class="text-sm font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors line-clamp-2"
                                   title="{{ $item['name'] }}">
                                    {{ $item['name'] }}
                                </a>

                                {{-- Description if any --}}
                                @if(!empty($item['description']))
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 line-clamp-2">{{ $item['description'] }}</p>
                                @endif
                            </div>

                            {{-- Meta footer: Uploader + Size + Date --}}
                            <div class="pt-3 border-t border-gray-100 dark:border-gray-750 flex items-center justify-between text-xs text-gray-400 dark:text-gray-500">
                                <div class="flex items-center gap-2 truncate">
                                    <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                                          style="background-color: {{ $item['uploader_color'] }}">
                                        {{ $item['uploader_initials'] }}
                                    </span>
                                    <span class="truncate text-gray-600 dark:text-gray-400">{{ $item['uploader_name'] }}</span>
                                </div>
                                <span class="shrink-0 font-medium text-gray-700 dark:text-gray-300">{{ $item['size'] }}</span>
                            </div>
                        </div>

                        {{-- Quick Actions Toolbar --}}
                        <div class="px-4 py-2.5 bg-gray-50/70 dark:bg-gray-800/40 border-t border-gray-100 dark:border-gray-750 flex items-center justify-between gap-1">
                            <span class="text-[11px] text-gray-400 dark:text-gray-500 truncate">{{ $item['created_diff'] ?: $item['created_at'] }}</span>
                            
                            <div class="flex items-center gap-1">
                                {{-- Copy link --}}
                                <button type="button" @click="copyLink(@js($item['url']), @js($item['id']))"
                                        title="Salin tautan"
                                        class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-700 transition-colors relative">
                                    <template x-if="copiedId === @js($item['id'])">
                                        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    </template>
                                    <template x-if="copiedId !== @js($item['id'])">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                    </template>
                                </button>

                                {{-- Open link / view file --}}
                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" title="Buka di tab baru"
                                   class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-700 transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                </a>

                                {{-- Open task detail modal if attached to task --}}
                                @if($hasTask)
                                    <button type="button" @click.prevent.stop="openTaskModal({{ $item['task']['id'] }})"
                                            title="Buka Modal Task #{{ $item['task']['id'] }}"
                                            class="p-1.5 rounded-lg text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </button>
                                @endif

                                {{-- Move Folder (Standalone Only) --}}
                                @if(!empty($item['move_url']) && $canManageFiles)
                                    <button type="button" @click="moveItem = { action: @js($item['move_url']), name: @js($item['name']) }; moveFolder = @js($item['folder'])"
                                            title="Pindahkan folder"
                                            class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-white dark:hover:bg-gray-700 transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3l3 3-3 3M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                    </button>
                                @endif

                                {{-- Delete --}}
                                @if(!empty($item['can_delete']) && !empty($item['delete_url']))
                                    <form method="POST" action="{{ $item['delete_url'] }}"
                                          onsubmit="return confirm('Hapus {{ $item['type'] === 'link' ? 'tautan' : 'berkas' }} &quot;{{ addslashes($item['name']) }}&quot;?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" title="Hapus"
                                                class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40 transition-colors">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            {{-- ============================================================
                 LIST VIEW
                 ============================================================ --}}
            <div x-show="view === 'list'" x-cloak class="bg-white dark:bg-gray-850 rounded-2xl overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 dark:bg-gray-800/60 border-b border-gray-100 dark:border-gray-750 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Nama Berkas / Tautan</th>
                                <th class="px-4 py-3">Sumber & Task</th>
                                <th class="px-4 py-3">Tipe / Ukuran</th>
                                <th class="px-4 py-3">Pengunggah</th>
                                <th class="px-4 py-3">Tanggal</th>
                                <th class="px-4 py-3 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-750">
                            @foreach($unifiedItems as $item)
                                @php
                                    $meta = $typeMeta($item);
                                    $isTask = ($item['source'] ?? '') === 'task';
                                    $hasTask = !empty($item['task']);
                                @endphp

                                <tr x-show="isVisible({
                                        id: @js($item['id']),
                                        raw_id: @js($item['raw_id']),
                                        source: @js($item['source']),
                                        type: @js($item['type']),
                                        folder: @js($item['folder']),
                                        name: @js($item['name']),
                                        url: @js($item['url']),
                                        desc: @js($item['description'] ?? ''),
                                        taskId: @js($item['task']['id'] ?? null),
                                        taskTitle: @js($item['task']['title'] ?? ''),
                                        uploader: @js($item['uploader_name'] ?? '')
                                    })"
                                    class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition-colors">
                                    
                                    {{-- File / Link Name --}}
                                    <td class="px-4 py-3 min-w-[220px]">
                                        <div class="flex items-center gap-3">
                                            @if($item['type'] === 'link')
                                                <span class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                                                </span>
                                            @elseif(!empty($item['is_image']) && $item['url'])
                                                <img src="{{ $item['url'] }}" alt="" class="w-8 h-8 rounded-lg object-cover shrink-0 border border-gray-200 dark:border-gray-700">
                                            @else
                                                <span class="w-8 h-8 rounded-lg flex items-center justify-center text-[10px] font-bold shrink-0 {{ $meta['color'] }}">
                                                    {{ $meta['ext'] }}
                                                </span>
                                            @endif

                                            <div class="min-w-0">
                                                <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer"
                                                   class="block font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 truncate max-w-xs"
                                                   title="{{ $item['name'] }}">
                                                    {{ $item['name'] }}
                                                </a>
                                                @if(!empty($item['description']))
                                                    <p class="text-xs text-gray-400 truncate max-w-xs">{{ $item['description'] }}</p>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Source & Task Reference --}}
                                    <td class="px-4 py-3 whitespace-nowrap min-w-[180px]">
                                        @if($hasTask)
                                            <button type="button" @click.prevent.stop="openTaskModal({{ $item['task']['id'] }})"
                                                    class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-medium bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition-colors">
                                                <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                <span class="font-bold">#{{ $item['task']['id'] }}</span>
                                                <span class="truncate max-w-[140px]">{{ $item['task']['title'] }}</span>
                                            </button>
                                        @else
                                            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                                                <span>{{ $item['folder'] ?: 'General' }}</span>
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Type & Size --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                                        <div class="flex items-center gap-2">
                                            <span class="px-2 py-0.5 rounded-md font-semibold text-[10px] {{ $meta['badge'] }}">
                                                {{ $item['type'] === 'link' ? 'LINK' : $meta['ext'] }}
                                            </span>
                                            <span>{{ $item['size'] }}</span>
                                        </div>
                                    </td>

                                    {{-- Uploader --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-xs">
                                        <div class="flex items-center gap-2">
                                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-[10px] font-bold text-white shrink-0"
                                                  style="background-color: {{ $item['uploader_color'] }}">
                                                {{ $item['uploader_initials'] }}
                                            </span>
                                            <span class="text-gray-700 dark:text-gray-300 truncate">{{ $item['uploader_name'] }}</span>
                                        </div>
                                    </td>

                                    {{-- Date --}}
                                    <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                        <span>{{ $item['created_diff'] ?: $item['created_at'] }}</span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="px-4 py-3 text-right whitespace-nowrap">
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- Copy Link --}}
                                            <button type="button" @click="copyLink(@js($item['url']), @js($item['id']))"
                                                    title="Salin tautan"
                                                    class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <template x-if="copiedId === @js($item['id'])">
                                                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                                </template>
                                                <template x-if="copiedId !== @js($item['id'])">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                                </template>
                                            </button>

                                            {{-- Open Link --}}
                                            <a href="{{ $item['url'] }}" target="_blank" rel="noopener noreferrer" title="Buka"
                                               class="p-1.5 rounded-lg text-gray-400 hover:text-blue-600 hover:bg-gray-100 dark:hover:bg-gray-700">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                            </a>

                                            {{-- Open Task Modal --}}
                                            @if($hasTask)
                                                <button type="button" @click.prevent.stop="openTaskModal({{ $item['task']['id'] }})"
                                                        title="Buka Task #{{ $item['task']['id'] }}"
                                                        class="p-1.5 rounded-lg text-indigo-500 hover:text-indigo-700 hover:bg-indigo-50 dark:hover:bg-indigo-950/60">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                                </button>
                                            @endif

                                            {{-- Delete --}}
                                            @if(!empty($item['can_delete']) && !empty($item['delete_url']))
                                                <form method="POST" action="{{ $item['delete_url'] }}"
                                                      onsubmit="return confirm('Hapus {{ $item['name'] }}?')">
                                                    @csrf @method('DELETE')
                                                    <button type="submit" title="Hapus"
                                                            class="p-1.5 rounded-lg text-gray-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Empty State (No items matching filter) --}}
            <div x-show="visibleCount === 0" x-cloak
                 class="bg-white dark:bg-gray-850 rounded-2xl py-16 px-6 text-center">
                <span class="w-16 h-16 mx-auto rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 flex items-center justify-center mb-3">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </span>
                <p class="text-base font-semibold text-gray-900 dark:text-white">Tidak ada berkas atau tautan yang cocok</p>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 max-w-sm mx-auto">
                    Coba sesuaikan kata kunci pencarian, filter tipe, atau sumber file proyek Anda.
                </p>
                <div class="mt-4 flex items-center justify-center gap-2">
                    <button type="button" @click="resetFilters()"
                            class="px-4 py-2 rounded-xl text-xs font-semibold bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 transition-colors">
                        Reset Filter
                    </button>
                    @if($canManageFiles)
                        <button type="button" @click="showUploadModal = true"
                                class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white transition-colors">
                            Upload File Baru
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ============================================================
         4. MODAL: UPLOAD FILE (DRAG & DROP + DESTINATION SWITCHER)
         ============================================================ --}}
    @if($canManageFiles)
    <div x-show="showUploadModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showUploadModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="showUploadModal = false"></div>

        <div class="relative bg-white dark:bg-gray-850 rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4 border border-gray-100 dark:border-gray-750 max-h-[90vh] overflow-y-auto"
             x-data="{
                picked: [],
                dragging: false,
                sync(list) { this.picked = Array.from(list).map(f => ({ name: f.name, size: f.size })) },
                drop(e) {
                    this.dragging = false;
                    if (!e.dataTransfer.files.length) return;
                    $refs.fileInput.files = e.dataTransfer.files;
                    this.sync(e.dataTransfer.files);
                },
                fmt(b) { return b >= 1048576 ? (b / 1048576).toFixed(1) + ' MB' : (b >= 1024 ? (b / 1024).toFixed(1) + ' KB' : b + ' B') }
             }">
            
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Upload File ke Proyek</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pilih berkas standalone atau tautkan ke task tertentu</p>
                </div>
                <button type="button" @click="showUploadModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Destination Switcher --}}
            <div class="flex rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
                <button type="button" @click="uploadDestination = 'standalone'"
                        :class="uploadDestination === 'standalone' ? 'bg-white dark:bg-gray-700 text-blue-600 dark:text-blue-400 shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400 font-medium'"
                        class="flex-1 py-1.5 rounded-lg text-xs transition-all">
                    File Proyek (Standalone)
                </button>
                <button type="button" @click="uploadDestination = 'task'"
                        :class="uploadDestination === 'task' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400 font-medium'"
                        class="flex-1 py-1.5 rounded-lg text-xs transition-all">
                    Lampiran Task Tertentu
                </button>
            </div>

            {{-- 1. FORM STANDALONE --}}
            <form x-show="uploadDestination === 'standalone'" method="POST" action="{{ route('project.files.store', $project) }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <label @dragover.prevent="dragging = true" @dragleave.prevent="dragging = false" @drop.prevent="drop($event)"
                       :class="dragging ? 'border-blue-500 bg-blue-50/50 dark:bg-blue-950/20' : 'border-gray-200 dark:border-gray-700 hover:border-blue-400'"
                       class="flex flex-col items-center justify-center gap-2 border-2 border-dashed rounded-xl px-6 py-8 text-center cursor-pointer transition-colors bg-gray-50/50 dark:bg-gray-800/30">
                    <span class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    </span>
                    <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Tarik & lepas berkas ke sini, atau <span class="text-blue-600 dark:text-blue-400">pilih dari komputer</span></span>
                    <span class="text-xs text-gray-400">Bisa memilih banyak file sekaligus (Maks. 50 MB / file)</span>
                    <input type="file" name="files[]" multiple required x-ref="fileInput" @change="sync($event.target.files)" class="sr-only">
                </label>

                {{-- Selected Files Queue --}}
                <template x-if="picked.length">
                    <ul class="divide-y divide-gray-100 dark:divide-gray-750 border border-gray-100 dark:border-gray-750 rounded-xl max-h-36 overflow-y-auto">
                        <template x-for="f in picked" :key="f.name">
                            <li class="flex items-center justify-between gap-3 px-3 py-2 text-xs">
                                <span class="truncate text-gray-700 dark:text-gray-300 font-medium" x-text="f.name"></span>
                                <span class="text-gray-400 shrink-0" x-text="fmt(f.size)"></span>
                            </li>
                        </template>
                    </ul>
                </template>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Folder Tujuan</label>
                        <input type="text" name="folder" x-model="uploadFolder" placeholder="General atau Docs/Kontrak" list="pf-folder-list-modal"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Berkas</label>
                        <input type="text" name="description" placeholder="Opsional catatan"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-750">
                    <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800">Batal</button>
                    <button type="submit" :disabled="!picked.length"
                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-5 py-2 rounded-xl transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                        Upload Standalone <span x-show="picked.length" x-text="'(' + picked.length + ')'"></span>
                    </button>
                </div>
            </form>

            {{-- 2. FORM TASK ATTACHMENT --}}
            <form x-show="uploadDestination === 'task'" method="POST"
                  :action="'/projects/{{ $project->slug }}/tasks/' + selectedUploadTask + '/attachments'"
                  enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="file">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Task Sasaran</label>
                    <select x-model="selectedUploadTask" required
                            class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        @foreach($projectTasks as $pt)
                            <option value="{{ $pt->id }}">#{{ $pt->id }} — {{ $pt->title }} ({{ ucfirst($pt->status) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Berkas Lampiran Task</label>
                    <input type="file" name="file" required
                           class="w-full text-xs text-gray-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-indigo-50 file:text-indigo-700 dark:file:bg-indigo-950/60 dark:file:text-indigo-300 hover:file:bg-indigo-100">
                    <p class="text-[11px] text-gray-400 mt-1">Berkas akan langsung tercatat di task dan dapat dibuka lewat modal task.</p>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-750">
                    <button type="button" @click="showUploadModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800">Batal</button>
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-5 py-2 rounded-xl transition-colors">
                        Upload ke Task
                    </button>
                </div>
            </form>
        </div>
    </div>
    <datalist id="pf-folder-list-modal">
        @foreach($folders as $f)<option value="{{ $f }}">@endforeach
    </datalist>
    @endif

    {{-- ============================================================
         5. MODAL: ADD LINK (FIGMA, GITHUB, DOCS, ETC.)
         ============================================================ --}}
    @if($canManageFiles)
    <div x-show="showLinkModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showLinkModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="showLinkModal = false"></div>

        <div class="relative bg-white dark:bg-gray-850 rounded-2xl shadow-xl w-full max-w-lg p-6 space-y-4 border border-gray-100 dark:border-gray-750 max-h-[90vh] overflow-y-auto">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Tautan / Link Eksternal</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Tautkan Figma, GitHub repository, Google Docs, atau website proyek</p>
                </div>
                <button type="button" @click="showLinkModal = false" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Destination Switcher --}}
            <div class="flex rounded-xl bg-gray-100 dark:bg-gray-800 p-1">
                <button type="button" @click="linkDestination = 'standalone'"
                        :class="linkDestination === 'standalone' ? 'bg-white dark:bg-gray-700 text-violet-600 dark:text-violet-400 shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400 font-medium'"
                        class="flex-1 py-1.5 rounded-lg text-xs transition-all">
                    Link Proyek (Standalone)
                </button>
                <button type="button" @click="linkDestination = 'task'"
                        :class="linkDestination === 'task' ? 'bg-white dark:bg-gray-700 text-indigo-600 dark:text-indigo-400 shadow-2xs font-semibold' : 'text-gray-600 dark:text-gray-400 font-medium'"
                        class="flex-1 py-1.5 rounded-lg text-xs transition-all">
                    Lampiran Task Tertentu
                </button>
            </div>

            {{-- 1. FORM STANDALONE LINK --}}
            <form x-show="linkDestination === 'standalone'" method="POST" action="{{ route('project.files.link.store', $project) }}" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Judul / Nama Tautan <span class="text-red-500">*</span></label>
                    <input type="text" name="title" required placeholder="Mis. Desain UI Figma, Repo GitHub Flovig, Dokumentasi API"
                           class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">URL / Alamat Tautan <span class="text-red-500">*</span></label>
                    <input type="url" name="url" required placeholder="https://..."
                           class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Folder</label>
                        <input type="text" name="folder" value="General" list="pf-folder-list-modal"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Deskripsi Singkat</label>
                        <input type="text" name="description" placeholder="Opsional keterangan"
                               class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-violet-500">
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-750">
                    <button type="button" @click="showLinkModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800">Batal</button>
                    <button type="submit"
                            class="bg-violet-600 hover:bg-violet-700 text-white text-xs font-semibold px-5 py-2 rounded-xl transition-colors">
                        Simpan Link Standalone
                    </button>
                </div>
            </form>

            {{-- 2. FORM TASK ATTACHMENT LINK --}}
            <form x-show="linkDestination === 'task'" method="POST"
                  :action="'/projects/{{ $project->slug }}/tasks/' + selectedLinkTask + '/attachments'" class="space-y-4">
                @csrf
                <input type="hidden" name="type" value="link">

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Pilih Task Sasaran</label>
                    <select x-model="selectedLinkTask" required
                            class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500 cursor-pointer">
                        @foreach($projectTasks as $pt)
                            <option value="{{ $pt->id }}">#{{ $pt->id }} — {{ $pt->title }} ({{ ucfirst($pt->status) }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Judul Tautan / Display Name</label>
                    <input type="text" name="display_name" placeholder="Mis. Wireframe Figma Task Ini"
                           class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">URL / Alamat Tautan <span class="text-red-500">*</span></label>
                    <input type="url" name="url" required placeholder="https://..."
                           class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500">
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100 dark:border-gray-750">
                    <button type="button" @click="showLinkModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800">Batal</button>
                    <button type="submit"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-semibold px-5 py-2 rounded-xl transition-colors">
                        Tautkan ke Task
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- ============================================================
         6. MODAL: NEW FOLDER
         ============================================================ --}}
    @if($canManageFiles)
    <div x-show="showFolderModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="showFolderModal = false">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="showFolderModal = false"></div>

        <form method="POST" action="{{ route('project.files.folders.store', $project) }}"
              class="relative bg-white dark:bg-gray-850 rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4 border border-gray-100 dark:border-gray-750">
            @csrf
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Buat Folder Proyek Baru</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400">Folder untuk merapikan berkas-berkas mandiri</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Nama Folder</label>
                <input type="text" name="name" x-model="newFolderName" required placeholder="Mis. Desain, Kontrak, dsb"
                       class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="showFolderModal = false" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors">Buat Folder</button>
            </div>
        </form>
    </div>
    @endif

    {{-- ============================================================
         7. MODAL: MOVE FOLDER (STANDALONE)
         ============================================================ --}}
    @if($canManageFiles)
    <div x-show="moveItem" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4" @keydown.escape.window="moveItem = null">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-xs" @click="moveItem = null"></div>

        <form method="POST" :action="moveItem ? moveItem.action : ''"
              class="relative bg-white dark:bg-gray-850 rounded-2xl shadow-xl w-full max-w-sm p-5 space-y-4 border border-gray-100 dark:border-gray-750">
            @csrf @method('PATCH')
            <div>
                <h3 class="text-base font-bold text-gray-900 dark:text-white">Pindahkan Berkas</h3>
                <p class="text-xs text-gray-500 dark:text-gray-400 truncate" x-text="moveItem ? moveItem.name : ''"></p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1">Folder Tujuan</label>
                <input type="text" name="folder" x-model="moveFolder" required list="pf-folder-list-modal" placeholder="General atau Docs/Kontrak"
                       class="w-full px-3 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" @click="moveItem = null" class="px-4 py-2 text-xs font-semibold text-gray-600 dark:text-gray-400 hover:text-gray-800">Batal</button>
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold px-4 py-2 rounded-xl transition-colors">Pindahkan</button>
            </div>
        </form>
    </div>
    @endif
</div>
