{{-- ============================================================
     PROJECT TEAM TAB CONTENT (Redesigned & Modernized)
     - Flat card container without border & shadow
     - KPI Metric Summary Cards (Total Members, Developers, Lead, Client)
     - Filter toolbar (Real-time Search & Role Filter)
     - Grid / Card View & Table / List View Toggle
     - Add Member Modal Pop-up with Searchable Multi-Select
============================================================ --}}
@php
    $members = $project->members ?? collect();
    $taskStats = $memberTaskStats ?? collect();
    $ticketStats = $memberTicketStats ?? collect();
    $availableUsers = $companyUsers ?? collect();

    $manager = $project->manager;
    $client = $project->client;

    // Filter counts
    $devCount = $members->filter(fn($m) => $m->user && $m->user->hasRole('member') && !$m->user->hasRole('client'))->count();
    $clientCount = $members->filter(fn($m) => $m->user && $m->user->hasRole('client'))->count();

    // Prepare serialized members list for Alpine
    $alpineMembers = $members->map(function ($member) use ($project, $taskStats, $ticketStats) {
        $u = $member->user;
        if (!$u) return null;

        $isManager = (int) $project->manager_id === (int) $u->id;
        $isClient = $u->hasRole('client') || (int) $project->client_id === (int) $u->id;
        $isAdmin = $u->hasRole('admin');
        $isDev = $u->hasRole('member') && !$isClient;

        $tasks = $taskStats->get($u->id);
        $totalTasks = $tasks ? (int) $tasks->total : 0;
        $doneTasks = $tasks ? (int) $tasks->done : 0;
        $taskPct = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

        $totalTickets = (int) ($ticketStats[$u->id] ?? 0);

        // Primary role string
        $roleKey = $isManager ? 'manager' : ($isAdmin ? 'admin' : ($isClient ? 'client' : 'member'));
        $roleLabel = $isManager ? 'Project Manager' : ($isAdmin ? 'Admin' : ($isClient ? 'Client' : 'Developer'));

        return [
            'id'             => (int) $u->id,
            'member_id'      => (int) $member->id,
            'name'           => $u->name,
            'email'          => $u->email,
            'avatar'         => $u->avatar ? Storage::url($u->avatar) : null,
            'initials'       => $u->initials(),
            'color'          => $u->avatarColor(),
            'role_key'       => $roleKey,
            'role_label'     => $roleLabel,
            'is_manager'     => $isManager,
            'is_client'      => $isClient,
            'total_tasks'    => $totalTasks,
            'done_tasks'     => $doneTasks,
            'task_pct'       => $taskPct,
            'total_tickets'  => $totalTickets,
            'joined_at'      => $member->created_at ? $member->created_at->format('d M Y') : '—',
            'remove_url'     => route('projects.members.remove', [$project, $u]),
        ];
    })->filter()->values();

    // Prepare serialized available company users for Add Member modal
    $alpineAvailableUsers = $availableUsers->map(function ($u) {
        $roles = $u->roles->pluck('name')->implode(', ');
        return [
            'id'       => (int) $u->id,
            'name'     => $u->name,
            'email'    => $u->email,
            'avatar'   => $u->avatar ? Storage::url($u->avatar) : null,
            'initials' => $u->initials(),
            'color'    => $u->avatarColor(),
            'roles'    => $roles ?: 'Member',
        ];
    })->values();
@endphp

<div x-data="{
    allMembers: {{ Js::from($alpineMembers) }},
    availableUsers: {{ Js::from($alpineAvailableUsers) }},

    // View mode: 'grid' or 'table'
    viewMode: 'grid',

    // Search and filter
    searchQuery: '',
    roleFilter: '',
    roleDropdownOpen: false,

    // Add Member modal state
    showAddMemberModal: false,
    addSearchQuery: '',
    selectedUserIds: [],
    submitting: false,

    // Methods
    toggleUser(id) {
        const numId = Number(id);
        const idx = this.selectedUserIds.indexOf(numId);
        if (idx > -1) {
            this.selectedUserIds.splice(idx, 1);
        } else {
            this.selectedUserIds.push(numId);
        }
    },
    removeSelectedUser(id) {
        const numId = Number(id);
        const idx = this.selectedUserIds.indexOf(numId);
        if (idx > -1) {
            this.selectedUserIds.splice(idx, 1);
        }
    },
    selectAllAvailable() {
        this.selectedUserIds = this.filteredAvailableUsers.map(u => u.id);
    },
    clearSelectedAvailable() {
        this.selectedUserIds = [];
    },

    get filteredAvailableUsers() {
        if (!this.addSearchQuery.trim()) return this.availableUsers;
        const q = this.addSearchQuery.toLowerCase();
        return this.availableUsers.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q) || u.roles.toLowerCase().includes(q));
    },
    get selectedUserObjects() {
        return this.availableUsers.filter(u => this.selectedUserIds.includes(u.id));
    },

    get filteredMembers() {
        return this.allMembers.filter(m => {
            const matchesSearch = !this.searchQuery.trim() ||
                m.name.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
                m.email.toLowerCase().includes(this.searchQuery.toLowerCase());

            const matchesRole = !this.roleFilter || m.role_key === this.roleFilter;

            return matchesSearch && matchesRole;
        });
    }
}" class="space-y-6 w-full max-w-full min-w-0">

    {{-- ── 1. KPI Metric Summary Cards (Flat, No Border, No Shadow) ────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {{-- Total Members --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 group hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition">
            <div class="w-12 h-12 rounded-xl bg-blue-50 dark:bg-blue-950/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-blue-600 dark:text-blue-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">TOTAL ANGGOTA</span>
                <span class="text-2xl font-bold text-gray-900 dark:text-white leading-none mt-1 block">{{ $members->count() }}</span>
            </div>
        </div>

        {{-- Developers / Team Members --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 group hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition">
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-950/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-indigo-600 dark:text-indigo-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">DEVELOPERS</span>
                <span class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 leading-none mt-1 block">{{ $devCount }}</span>
            </div>
        </div>

        {{-- Project Manager / Lead --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 group hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-amber-600 dark:text-amber-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">PROJECT LEAD</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white leading-tight mt-1 block truncate" title="{{ $manager ? $manager->name : 'Unassigned' }}">
                    {{ $manager ? $manager->name : 'Belum Ditentukan' }}
                </span>
                <span class="text-[10px] text-amber-600 dark:text-amber-400 font-medium block truncate">Project Manager</span>
            </div>
        </div>

        {{-- Client Stakeholder --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 group hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-emerald-600 dark:text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75a1.5 1.5 0 0 1 1.5-1.5h3a1.5 1.5 0 0 1 1.5 1.5V21" />
                </svg>
            </div>
            <div class="min-w-0 flex-1">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">KLIEN / STAKEHOLDER</span>
                <span class="text-sm font-bold text-gray-900 dark:text-white leading-tight mt-1 block truncate" title="{{ $client ? $client->name : 'Internal Team' }}">
                    {{ $client ? $client->name : 'Proyek Internal' }}
                </span>
                <span class="text-[10px] text-emerald-600 dark:text-emerald-400 font-medium block truncate">Client Stakeholder</span>
            </div>
        </div>
    </div>

    {{-- ── 2. Main Section Card (No Border, No Shadow per Request) ──────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl overflow-hidden">
        {{-- Card Header: Title + View Switcher + Add Member Button --}}
        <div class="px-6 py-4.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between flex-wrap gap-4">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                        </svg>
                    </span>
                    <span>Team Members</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400"
                          x-text="filteredMembers.length + ' Anggota'">
                        {{ $members->count() }} Anggota
                    </span>
                </h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Daftar developer, manajer, dan pemangku kepentingan yang terlibat dalam proyek ini
                </p>
            </div>

            {{-- Actions: View Toggle + Add Member Button --}}
            <div class="flex items-center gap-3">
                {{-- View Toggle (Grid / Table) --}}
                <div class="flex items-center bg-gray-100 dark:bg-gray-800 p-1 rounded-xl">
                    <button type="button" @click="viewMode = 'grid'"
                            :class="viewMode === 'grid' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-2xs font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="px-2.5 py-1 text-xs rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6zM14 6a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2V6zM4 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-2zM14 16a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2a2 2 0 0 1-2 2h-2a2 2 0 0 1-2-2v-2z" />
                        </svg>
                        <span class="hidden sm:inline">Grid</span>
                    </button>
                    <button type="button" @click="viewMode = 'table'"
                            :class="viewMode === 'table' ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-2xs font-semibold' : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200'"
                            class="px-2.5 py-1 text-xs rounded-lg transition flex items-center gap-1.5 cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                        <span class="hidden sm:inline">Table</span>
                    </button>
                </div>

                @if(!auth()->user()->hasRole('client'))
                    <button type="button"
                            @click="showAddMemberModal = true; addSearchQuery = ''; selectedUserIds = []"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-2xs cursor-pointer">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Add Member</span>
                    </button>
                @endif
            </div>
        </div>

        {{-- Filter Toolbar --}}
        <div class="p-4 sm:px-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30">
            <div class="flex flex-wrap items-center gap-2.5">
                {{-- Search Bar --}}
                <div class="relative flex-1 min-w-[220px] max-w-xs">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text"
                           x-model="searchQuery"
                           placeholder="Cari nama atau email anggota..."
                           class="w-full pl-9.5 pr-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50/80 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                </div>

                {{-- Role Custom Filter Select --}}
                <div class="relative" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 transition cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                        <span>Peran: <strong class="font-semibold text-gray-900 dark:text-white"
                                            x-text="roleFilter === 'manager' ? 'Project Manager' : (roleFilter === 'member' ? 'Developer' : (roleFilter === 'client' ? 'Client' : (roleFilter === 'admin' ? 'Admin' : 'Semua Peran')))">Semua Peran</strong></span>
                        <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1.5 min-w-[190px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-40 space-y-0.5">
                        <button type="button" @click="roleFilter = ''; open = false"
                                class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                :class="roleFilter === '' ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200'">
                            <span>Semua Peran</span>
                            <svg x-show="roleFilter === ''" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button type="button" @click="roleFilter = 'manager'; open = false"
                                class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                :class="roleFilter === 'manager' ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200'">
                            <span>Project Manager</span>
                            <svg x-show="roleFilter === 'manager'" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button type="button" @click="roleFilter = 'member'; open = false"
                                class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                :class="roleFilter === 'member' ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200'">
                            <span>Developer</span>
                            <svg x-show="roleFilter === 'member'" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button type="button" @click="roleFilter = 'client'; open = false"
                                class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                :class="roleFilter === 'client' ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200'">
                            <span>Client Stakeholder</span>
                            <svg x-show="roleFilter === 'client'" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                        <button type="button" @click="roleFilter = 'admin'; open = false"
                                class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer"
                                :class="roleFilter === 'admin' ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200'">
                            <span>Admin</span>
                            <svg x-show="roleFilter === 'admin'" class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Reset Button --}}
                <template x-if="searchQuery || roleFilter">
                    <button type="button" @click="searchQuery = ''; roleFilter = ''"
                            class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset Filter</span>
                    </button>
                </template>
            </div>
        </div>

        {{-- ── 3. Team Members Grid View ───────────────────────────────── --}}
        <div x-show="viewMode === 'grid'" class="p-6">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4.5">
                <template x-for="member in filteredMembers" :key="member.id">
                    <div class="bg-gray-50/60 dark:bg-gray-800/40 hover:bg-gray-50 dark:hover:bg-gray-800/80 rounded-2xl p-5 transition flex flex-col justify-between group">
                        {{-- Top Part: Avatar, Name, Role Badge, Actions --}}
                        <div>
                            <div class="flex items-start justify-between gap-3 mb-3.5">
                                <div class="flex items-center gap-3 min-w-0">
                                    <template x-if="member.avatar">
                                        <img :src="member.avatar" class="w-11 h-11 rounded-2xl object-cover shrink-0 ring-2 ring-white dark:ring-gray-700">
                                    </template>
                                    <template x-if="!member.avatar">
                                        <div class="w-11 h-11 rounded-2xl text-white font-bold text-sm flex items-center justify-center shrink-0 ring-2 ring-white dark:ring-gray-700"
                                             :style="'background-color: ' + member.color" x-text="member.initials"></div>
                                    </template>
                                    <div class="min-w-0">
                                        <h3 class="text-sm font-bold text-gray-900 dark:text-white truncate" x-text="member.name"></h3>
                                        <p class="text-xs text-gray-400 dark:text-gray-500 truncate" x-text="member.email"></p>
                                    </div>
                                </div>

                                {{-- Remove Action (Only if authorized and not project manager) --}}
                                @if(!auth()->user()->hasRole('client'))
                                    <div class="shrink-0" x-show="!member.is_manager">
                                        <form method="POST" :action="member.remove_url"
                                              :data-confirm-delete="member.name + ' dari tim proyek'"
                                              data-confirm-label="Keluarkan Anggota">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Keluarkan dari tim"
                                                    class="w-7 h-7 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/60 transition flex items-center justify-center opacity-60 group-hover:opacity-100 cursor-pointer">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>

                            {{-- Role Badges --}}
                            <div class="mb-4">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold"
                                      :class="{
                                          'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400': member.role_key === 'manager',
                                          'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400': member.role_key === 'member',
                                          'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400': member.role_key === 'client',
                                          'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400': member.role_key === 'admin'
                                      }">
                                    <template x-if="member.role_key === 'manager'">
                                        <svg class="w-3 h-3 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" /></svg>
                                    </template>
                                    <template x-if="member.role_key === 'member'">
                                        <svg class="w-3 h-3 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" /></svg>
                                    </template>
                                    <template x-if="member.role_key === 'client'">
                                        <svg class="w-3 h-3 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
                                    </template>
                                    <template x-if="member.role_key === 'admin'">
                                        <svg class="w-3 h-3 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" /></svg>
                                    </template>
                                    <span x-text="member.role_label"></span>
                                </span>
                            </div>
                        </div>

                        {{-- Middle & Bottom Part: Task Statistics & Joined Date --}}
                        <div class="pt-3.5 border-t border-gray-200/60 dark:border-gray-700/60 space-y-3">
                            {{-- Task Progress Bar --}}
                            <div>
                                <div class="flex items-center justify-between text-[11px] mb-1.5">
                                    <span class="text-gray-500 dark:text-gray-400 font-medium">Tugas Selesai</span>
                                    <span class="font-bold text-gray-900 dark:text-white">
                                        <span x-text="member.done_tasks"></span> / <span x-text="member.total_tasks"></span>
                                        <span class="text-gray-400 font-normal" x-text="'(' + member.task_pct + '%)'"></span>
                                    </span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                                    <div class="h-full rounded-full transition-all duration-300"
                                         :class="member.task_pct >= 100 ? 'bg-emerald-500' : 'bg-blue-600'"
                                         :style="'width: ' + member.task_pct + '%'"></div>
                                </div>
                            </div>

                            {{-- Bottom Meta: Tickets & Joined --}}
                            <div class="flex items-center justify-between text-[11px] text-gray-400 dark:text-gray-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    <span x-text="member.total_tickets + ' Tiket'"></span>
                                </span>
                                <span>Bergabung: <strong class="font-medium text-gray-600 dark:text-gray-300" x-text="member.joined_at"></strong></span>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Empty Search State --}}
            <div x-show="filteredMembers.length === 0" class="py-16 text-center">
                <div class="w-12 h-12 rounded-2xl bg-gray-100 dark:bg-gray-800 text-gray-400 flex items-center justify-center mx-auto mb-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
                    </svg>
                </div>
                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Tidak ada anggota yang cocok</h3>
                <p class="text-xs text-gray-400 max-w-sm mx-auto mb-4">
                    Coba ubah kata kunci pencarian atau reset filter peran di atas.
                </p>
                <button type="button" @click="searchQuery = ''; roleFilter = ''"
                        class="px-4 py-2 bg-gray-100 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-200 text-xs font-semibold rounded-xl transition cursor-pointer">
                    Reset Filter
                </button>
            </div>
        </div>

        {{-- ── 4. Team Members Table View ──────────────────────────────── --}}
        <div x-show="viewMode === 'table'" class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left">
                <thead class="bg-gray-50/70 dark:bg-gray-800/40 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Anggota Tim</th>
                        <th class="px-4 py-3.5">Peran / Jabatan</th>
                        <th class="px-4 py-3.5">Progres Tugas</th>
                        <th class="px-4 py-3.5">Tiket Ditugaskan</th>
                        <th class="px-4 py-3.5">Tanggal Bergabung</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-850">
                    <template x-for="member in filteredMembers" :key="member.id">
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition group">
                            {{-- Member Name & Avatar --}}
                            <td class="px-6 py-3.5">
                                <div class="flex items-center gap-3">
                                    <template x-if="member.avatar">
                                        <img :src="member.avatar" class="w-9 h-9 rounded-xl object-cover shrink-0">
                                    </template>
                                    <template x-if="!member.avatar">
                                        <div class="w-9 h-9 rounded-xl text-white font-bold text-xs flex items-center justify-center shrink-0"
                                             :style="'background-color: ' + member.color" x-text="member.initials"></div>
                                    </template>
                                    <div class="min-w-0">
                                        <p class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white truncate" x-text="member.name"></p>
                                        <p class="text-xs text-gray-400 truncate" x-text="member.email"></p>
                                    </div>
                                </div>
                            </td>

                            {{-- Role Badge --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl text-xs font-semibold"
                                      :class="{
                                          'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400': member.role_key === 'manager',
                                          'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-400': member.role_key === 'member',
                                          'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400': member.role_key === 'client',
                                          'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400': member.role_key === 'admin'
                                      }">
                                    <span x-text="member.role_label"></span>
                                </span>
                            </td>

                            {{-- Task Progress --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <div class="w-36">
                                    <div class="flex items-center justify-between text-[11px] mb-1">
                                        <span class="text-gray-500 font-medium" x-text="member.done_tasks + ' / ' + member.total_tasks + ' Selesai'"></span>
                                        <span class="font-bold text-gray-900 dark:text-white" x-text="member.task_pct + '%'"></span>
                                    </div>
                                    <div class="w-full h-1.5 rounded-full bg-gray-100 dark:bg-gray-700 overflow-hidden">
                                        <div class="h-full rounded-full bg-blue-600" :style="'width: ' + member.task_pct + '%'"></div>
                                    </div>
                                </div>
                            </td>

                            {{-- Tickets Assigned --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-rose-50 text-rose-700 dark:bg-rose-950/50 dark:text-rose-400">
                                    <svg class="w-3 h-3 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                    <span x-text="member.total_tickets + ' Tiket'"></span>
                                </span>
                            </td>

                            {{-- Joined At --}}
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400" x-text="member.joined_at"></td>

                            {{-- Actions --}}
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                @if(!auth()->user()->hasRole('client'))
                                    <template x-if="!member.is_manager">
                                        <form method="POST" :action="member.remove_url"
                                              :data-confirm-delete="member.name + ' dari tim proyek'"
                                              data-confirm-label="Keluarkan Anggota">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Keluarkan dari tim"
                                                    class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-gray-400 hover:text-rose-600 hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                                </svg>
                                            </button>
                                        </form>
                                    </template>
                                @endif
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============================================================
         ADD TEAM MEMBER MODAL POP-UP (Searchable Multi-Select)
    ============================================================ --}}
    @if(!auth()->user()->hasRole('client'))
        <div x-show="showAddMemberModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs"
             @keydown.escape.window="showAddMemberModal = false">

            {{-- Modal Dialog Card --}}
            <div class="relative w-full max-w-xl bg-white dark:bg-gray-850 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden transform transition-all max-h-[90vh] flex flex-col"
                 @click.away="showAddMemberModal = false">

                {{-- Modal Header --}}
                <div class="px-6 py-4.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 bg-white dark:bg-gray-850 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-2xl bg-blue-50 dark:bg-blue-950/60 border border-blue-200/60 dark:border-blue-900/60 flex items-center justify-center text-blue-600 dark:text-blue-400 shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-base font-bold text-gray-900 dark:text-white">Tambah Anggota ke Tim</h3>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Pilih pengguna dari perusahaan untuk ditugaskan ke proyek ini</p>
                        </div>
                    </div>
                    <button type="button" @click="showAddMemberModal = false"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                {{-- Modal Body Form --}}
                <form method="POST" action="{{ route('projects.members.add', $project) }}"
                      class="flex-1 overflow-y-auto p-6 space-y-4"
                      @submit="if(submitting){ $event.preventDefault(); } else { submitting = true; }">
                    @csrf

                    {{-- Hidden user_id[] inputs --}}
                    <template x-for="id in selectedUserIds" :key="id">
                        <input type="hidden" name="user_id[]" :value="id">
                    </template>

                    {{-- Selected Chips --}}
                    <div>
                        <div class="flex items-center justify-between text-xs font-bold text-gray-700 dark:text-gray-300 mb-2">
                            <span>Anggota Terpilih (<span x-text="selectedUserIds.length"></span>)</span>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="selectAllAvailable()" class="text-blue-600 hover:text-blue-700 text-[11px] font-semibold transition">
                                    Pilih Semua
                                </button>
                                <span class="text-gray-300 dark:text-gray-700">|</span>
                                <button type="button" @click="clearSelectedAvailable()" class="text-rose-600 hover:text-rose-700 text-[11px] font-semibold transition">
                                    Batal Semua
                                </button>
                            </div>
                        </div>

                        <div class="min-h-[46px] p-2 rounded-2xl bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 flex flex-wrap gap-1.5 items-center">
                            <template x-if="selectedUserIds.length === 0">
                                <span class="text-xs text-gray-400 italic px-2">Belum ada anggota yang dipilih dari daftar di bawah.</span>
                            </template>
                            <template x-for="u in selectedUserObjects" :key="u.id">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-xl bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-medium text-xs">
                                    <template x-if="u.avatar">
                                        <img :src="u.avatar" class="w-4 h-4 rounded-full object-cover">
                                    </template>
                                    <template x-if="!u.avatar">
                                        <span class="w-4 h-4 rounded-full text-white text-[9px] font-bold flex items-center justify-center shrink-0"
                                              :style="'background-color: ' + u.color" x-text="u.initials"></span>
                                    </template>
                                    <span x-text="u.name" class="max-w-[120px] truncate"></span>
                                    <button type="button" @click="removeSelectedUser(u.id)" class="text-blue-400 hover:text-blue-600 transition">
                                        ✕
                                    </button>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Search in Available Users --}}
                    <div class="relative">
                        <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                        <input type="text" x-model="addSearchQuery" placeholder="Cari nama, email, atau jabatan..."
                               class="w-full pl-9.5 pr-4 py-2 bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                    </div>

                    {{-- Scrollable List of Available Users --}}
                    <div class="max-h-60 overflow-y-auto space-y-1 pr-1 border border-gray-100 dark:border-gray-800 rounded-2xl p-2 bg-gray-50/40 dark:bg-gray-900/40">
                        <template x-for="user in filteredAvailableUsers" :key="user.id">
                            <div @click="toggleUser(user.id)"
                                 class="px-3.5 py-2.5 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-white dark:hover:bg-gray-800"
                                 :class="selectedUserIds.includes(user.id) ? 'bg-blue-50/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-700 dark:text-gray-200'">
                                <div class="flex items-center gap-3 min-w-0">
                                    <template x-if="user.avatar">
                                        <img :src="user.avatar" class="w-7 h-7 rounded-xl object-cover shrink-0">
                                    </template>
                                    <template x-if="!user.avatar">
                                        <div class="w-7 h-7 rounded-xl text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                             :style="'background-color: ' + user.color" x-text="user.initials"></div>
                                    </template>
                                    <div class="min-w-0">
                                        <div class="flex items-center gap-1.5">
                                            <span class="block truncate font-bold" x-text="user.name"></span>
                                            <span class="text-[10px] px-1.5 py-0.2 rounded-md bg-gray-200/80 dark:bg-gray-700 text-gray-600 dark:text-gray-400 font-normal uppercase" x-text="user.roles"></span>
                                        </div>
                                        <span class="text-[11px] text-gray-400 block truncate font-normal" x-text="user.email"></span>
                                    </div>
                                </div>
                                <div class="w-5 h-5 rounded-lg border flex items-center justify-center transition shrink-0 ml-2"
                                     :class="selectedUserIds.includes(user.id) ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 dark:border-gray-600'">
                                    <svg x-show="selectedUserIds.includes(user.id)" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                    </svg>
                                </div>
                            </div>
                        </template>

                        <template x-if="filteredAvailableUsers.length === 0">
                            <div class="px-4 py-8 text-center text-xs text-gray-400">
                                <p class="font-medium">Tidak ada anggota perusahaan lain yang tersedia</p>
                                <p class="text-[11px] mt-0.5">Semua pengguna aktif sudah bergabung dalam proyek ini.</p>
                            </div>
                        </template>
                    </div>

                    {{-- Modal Footer Actions --}}
                    <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 shrink-0">
                        <span class="text-xs text-gray-400" x-text="selectedUserIds.length + ' anggota terpilih'"></span>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="showAddMemberModal = false"
                                    class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer transition">
                                Batal
                            </button>
                            <button type="submit" :disabled="selectedUserIds.length === 0 || submitting"
                                    class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-2 cursor-pointer">
                                <svg x-show="submitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span x-text="submitting ? 'Menambahkan...' : 'Tambahkan ke Tim'">Tambahkan ke Tim</span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    @endif

</div>
