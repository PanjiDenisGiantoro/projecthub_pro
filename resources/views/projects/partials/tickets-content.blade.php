{{-- ============================================================
     PROJECT TICKETS TAB CONTENT (Redesigned & Modernized)
     - Flat card container without border & shadow
     - KPI Metric Summary Cards
     - Filter toolbar (Status, Priority, Type, Search)
     - Create Ticket Modal Pop-up with Custom Selects:
       * Custom Popover for Tipe & Prioritas
       * Custom Popover for Kategori Error & Milestone (Opsional)
       * Searchable Multi-Select Assignee (Members only, no admin/client, multiple selection)
     - Edit/Update Ticket Modal Pop-up with matching Custom Selects
     - "View All" hidden per user request
============================================================ --}}
@php
    $tickets = $projectTickets ?? $recentTickets ?? collect();
    $milestones = $projectMilestones ?? $project->milestones ?? collect();
    $assignees = $ticketAssignableUsers ?? $assignableUsers ?? collect();
    $stats = $ticketStats ?? [
        'total'    => $project->tickets()->count(),
        'open'     => $project->tickets()->whereIn('status', ['open', 'assigned', 'in_progress', 'reopened'])->count(),
        'resolved' => $project->tickets()->whereIn('status', ['resolved', 'closed'])->count(),
        'breached' => $project->tickets()->where('sla_breached', true)->count(),
    ];

    $priorityMeta = [
        'critical' => ['label' => 'Critical', 'dot' => 'bg-red-500', 'badge' => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-400', 'desc' => 'Bloker / kendala kritis'],
        'high'     => ['label' => 'High',     'dot' => 'bg-orange-500', 'badge' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/60 dark:text-orange-400', 'desc' => 'Masalah utama sistem'],
        'medium'   => ['label' => 'Medium',   'dot' => 'bg-blue-500', 'badge' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-400', 'desc' => 'Standar operasional'],
        'low'      => ['label' => 'Low',      'dot' => 'bg-slate-400', 'badge' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300', 'desc' => 'Minor / perbaikan kecil'],
    ];

    $typeMeta = [
        'bug'         => ['label' => 'Bug',         'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-400'],
        'issue'       => ['label' => 'Issue',       'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-400'],
        'enhancement' => ['label' => 'Enhancement', 'badge' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-400'],
        'security'    => ['label' => 'Security',    'badge' => 'bg-red-50 text-red-700 dark:bg-red-950/60 dark:text-red-400'],
        'performance' => ['label' => 'Performance', 'badge' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-400'],
    ];

    $statusMeta = [
        'open'           => ['label' => 'Open',           'dot' => 'bg-sky-500',    'badge' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300'],
        'assigned'       => ['label' => 'Assigned',       'dot' => 'bg-indigo-500', 'badge' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'],
        'in_progress'    => ['label' => 'In Progress',    'dot' => 'bg-amber-500',  'badge' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'],
        'pending_review' => ['label' => 'Pending Review', 'dot' => 'bg-purple-500', 'badge' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300'],
        'resolved'       => ['label' => 'Resolved',       'dot' => 'bg-emerald-500','badge' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'],
        'closed'         => ['label' => 'Closed',         'dot' => 'bg-gray-400',   'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'],
        'reopened'       => ['label' => 'Reopened',       'dot' => 'bg-rose-500',   'badge' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'],
    ];

    $errorCategories = [
        'frontend'       => ['label' => 'Frontend / UI',           'icon' => 'window'],
        'backend'        => ['label' => 'Backend / Server',         'icon' => 'server'],
        'database'       => ['label' => 'Database / Storage',       'icon' => 'database'],
        'api'            => ['label' => 'API / Integrasi',          'icon' => 'arrows-up-down'],
        'infrastructure' => ['label' => 'Infrastruktur / Cloud',    'icon' => 'cloud'],
        'integration'    => ['label' => 'Integrasi Pihak Ketiga',   'icon' => 'puzzle-piece'],
        'configuration'  => ['label' => 'Konfigurasi Sistem',       'icon' => 'cog'],
        'other'          => ['label' => 'Lainnya',                  'icon' => 'ellipsis-horizontal'],
    ];

    // Build serialized assignees list for Alpine (strictly member role, no client/admin)
    $alpineAssignees = $assignees->map(function ($u) {
        return [
            'id'       => (int) $u->id,
            'name'     => $u->name,
            'email'    => $u->email,
            'avatar'   => $u->avatar ? Storage::url($u->avatar) : null,
            'initials' => $u->initials(),
            'color'    => $u->avatarColor(),
        ];
    })->values();

    // Build serialized milestones list for Alpine
    $alpineMilestones = $milestones->map(function ($m) {
        return [
            'id'    => (string) $m->id,
            'title' => $m->title,
        ];
    })->values();

    // Filter options & selected labels
    $filterStatusOptions = [
        ''               => 'All Status',
        'open'           => 'Open',
        'assigned'       => 'Assigned',
        'in_progress'    => 'In Progress',
        'pending_review' => 'Pending Review',
        'resolved'       => 'Resolved',
        'closed'         => 'Closed',
        'reopened'       => 'Reopened',
    ];
    $selectedFilterStatus = request('status', '');
    $selectedFilterStatusLabel = $filterStatusOptions[$selectedFilterStatus] ?? ($selectedFilterStatus ? ucfirst(str_replace('_', ' ', $selectedFilterStatus)) : 'All Status');

    $filterPriorityOptions = [
        ''         => 'All Priority',
        'critical' => 'Critical',
        'high'     => 'High',
        'medium'   => 'Medium',
        'low'      => 'Low',
    ];
    $selectedFilterPriority = request('priority', '');
    $selectedFilterPriorityLabel = $filterPriorityOptions[$selectedFilterPriority] ?? ($selectedFilterPriority ? ucfirst($selectedFilterPriority) : 'All Priority');

    $filterTypeOptions = [
        ''            => 'All Types',
        'bug'         => 'Bug',
        'issue'       => 'Issue',
        'enhancement' => 'Enhancement',
        'security'    => 'Security',
        'performance' => 'Performance',
    ];
    $selectedFilterType = request('type', '');
    $selectedFilterTypeLabel = $filterTypeOptions[$selectedFilterType] ?? ($selectedFilterType ? ucfirst($selectedFilterType) : 'All Types');

    // Build serialized ticket detail dictionary for instant zero-latency modal popup
    $buildTicketDetail = function ($t) use ($errorCategories, $project, $priorityMeta, $typeMeta, $statusMeta) {
        $assignedUsers = $t->assignees->isNotEmpty()
            ? $t->assignees
            : ($t->assignee ? collect([$t->assignee]) : collect());

        $slaRemaining = $t->sla_due_at ? max(0, now()->diffInMinutes($t->sla_due_at, false)) : null;
        $slaRemainingText = $slaRemaining !== null
            ? ($slaRemaining > 0 ? floor($slaRemaining / 60) . 'j ' . ($slaRemaining % 60) . 'm' : 'Terlewat')
            : '—';
        $slaPercent = $t->sla_percent_used ?? ($t->sla_breached ? 100 : 0);

        return [
            'id'                  => $t->id,
            'title'               => $t->title,
            'description'         => $t->description,
            'type'                => $t->type ?? 'bug',
            'type_badge'          => $typeMeta[$t->type ?? 'bug']['badge'] ?? '',
            'type_label'          => $typeMeta[$t->type ?? 'bug']['label'] ?? ucfirst($t->type ?? 'bug'),
            'priority'            => $t->priority ?? 'medium',
            'priority_label'      => $priorityMeta[$t->priority ?? 'medium']['label'] ?? ucfirst($t->priority ?? 'medium'),
            'priority_badge'      => $priorityMeta[$t->priority ?? 'medium']['badge'] ?? '',
            'priority_dot'        => $priorityMeta[$t->priority ?? 'medium']['dot'] ?? '',
            'status'              => $t->status ?? 'open',
            'status_label'        => $statusMeta[$t->status ?? 'open']['label'] ?? ucfirst(str_replace('_', ' ', $t->status ?? 'open')),
            'status_badge'        => $statusMeta[$t->status ?? 'open']['badge'] ?? '',
            'status_dot'          => $statusMeta[$t->status ?? 'open']['dot'] ?? '',
            'error_category'      => $t->error_category ?? '',
            'error_category_label'=> $errorCategories[$t->error_category]['label'] ?? ($t->error_category ? ucfirst($t->error_category) : '—'),
            'solution'            => $t->solution ?? '',
            'milestone_id'        => $t->milestone_id ? (string) $t->milestone_id : '',
            'milestone_title'     => $t->milestone ? $t->milestone->title : null,
            'sla_breached'        => (bool) $t->sla_breached,
            'sla_due_at'          => $t->sla_due_at ? $t->sla_due_at->format('d M Y H:i') : null,
            'sla_remaining_text'  => $slaRemainingText,
            'sla_percent'         => min(max($slaPercent, 0), 100),
            'created_at'          => $t->created_at ? $t->created_at->format('d M Y H:i') : '—',
            'created_at_diff'     => $t->created_at ? $t->created_at->diffForHumans() : '',
            'resolved_at'         => $t->resolved_at ? $t->resolved_at->format('d M Y H:i') : null,
            'closed_at'           => $t->closed_at ? $t->closed_at->format('d M Y H:i') : null,
            'google_meet_link'    => $t->google_meet_link,
            'can_create_meet'     => !$t->google_meet_link && ($project->google_meet_enabled ?? false) && !auth()->user()->hasRole('client'),

            'reporter' => $t->reporter ? [
                'id'       => $t->reporter->id,
                'name'     => $t->reporter->name,
                'email'    => $t->reporter->email,
                'avatar'   => $t->reporter->avatar ? Storage::url($t->reporter->avatar) : null,
                'initials' => $t->reporter->initials(),
                'color'    => $t->reporter->avatarColor(),
            ] : null,

            'assignees' => $assignedUsers->map(function ($u) {
                return [
                    'id'       => $u->id,
                    'name'     => $u->name,
                    'email'    => $u->email,
                    'avatar'   => $u->avatar ? Storage::url($u->avatar) : null,
                    'initials' => $u->initials(),
                    'color'    => $u->avatarColor(),
                ];
            })->values()->all(),

            'assignee_ids' => $assignedUsers->pluck('id')->map(fn($id) => (int)$id)->all(),

            'attachments' => $t->attachments->map(function ($att) use ($t) {
                return [
                    'id'            => $att->id,
                    'file_name'     => $att->file_name,
                    'file_size'     => $att->file_size ? number_format($att->file_size / 1024, 1) . ' KB' : '—',
                    'url'           => $att->url(),
                    'is_image'      => $att->isImage(),
                    'uploader_name' => $att->uploader ? $att->uploader->name : 'User',
                    'delete_url'    => route('tickets.attachments.delete', [$t, $att]),
                ];
            })->values()->all(),

            'comments' => $t->comments->sortBy('created_at')->map(function ($c) {
                return [
                    'id'              => $c->id,
                    'body'            => $c->body,
                    'created_at_diff' => $c->created_at ? $c->created_at->diffForHumans() : '',
                    'created_at'      => $c->created_at ? $c->created_at->format('d M Y H:i') : '',
                    'user'            => $c->user ? [
                        'id'       => $c->user->id,
                        'name'     => $c->user->name,
                        'avatar'   => $c->user->avatar ? Storage::url($c->user->avatar) : null,
                        'initials' => $c->user->initials(),
                        'color'    => $c->user->avatarColor(),
                    ] : null,
                ];
            })->values()->all(),

            'histories' => $t->histories->sortByDesc('created_at')->map(function ($h) {
                return [
                    'id'            => $h->id,
                    'actor_name'    => $h->actor ? $h->actor->name : 'Sistem',
                    'field_changed' => $h->field_changed,
                    'old_value'     => $h->old_value,
                    'new_value'     => $h->new_value,
                    'description'   => $h->description,
                    'created_at'    => $h->created_at ? $h->created_at->format('d M H:i') : '',
                ];
            })->values()->all(),

            'urls' => [
                'update'     => route('tickets.update', $t),
                'details'    => route('tickets.details', $t),
                'comment'    => route('tickets.comment', $t),
                'meeting'    => route('tickets.meeting.create', $t),
                'reopen'     => route('tickets.reopen', $t),
            ],
        ];
    };

    $ticketsDetailMap = [];
    foreach ($tickets as $t) {
        $ticketsDetailMap[(int) $t->id] = $buildTicketDetail($t);
    }
    if (isset($initialOpenTicket) && !isset($ticketsDetailMap[(int) $initialOpenTicket->id])) {
        $ticketsDetailMap[(int) $initialOpenTicket->id] = $buildTicketDetail($initialOpenTicket);
    }
@endphp

<div x-data="{
    // Global data
    allAssignees: {{ Js::from($alpineAssignees) }},
    allMilestones: {{ Js::from($alpineMilestones) }},
    errorCategoryMap: {{ Js::from(collect($errorCategories)->map(fn($c) => $c['label'])) }},
    ticketsDetailMap: {{ Js::from($ticketsDetailMap) }},
    isClient: {{ auth()->user()->hasRole('client') ? 'true' : 'false' }},
    isAdminOrMember: {{ auth()->user()->hasRole(['admin', 'member']) ? 'true' : 'false' }},

    // --- DETAIL MODAL STATE ---
    showDetailTicketModal: false,
    detailTicket: null,
    activeDetailTab: 'details',
    detailStatusDropdownOpen: false,

    setFilter(key, val) {
        const url = new URL(window.location.href);
        if (val !== undefined && val !== null && val !== '') {
            url.searchParams.set(key, val);
        } else {
            url.searchParams.delete(key);
        }
        url.searchParams.delete('tickets_page');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    },

    // Lifecycle Init
    init() {
        const urlParams = new URLSearchParams(window.location.search);
        const urlTicket = urlParams.get('ticket');
        if (urlTicket && this.ticketsDetailMap[urlTicket]) {
            this.openDetailModal(Number(urlTicket), false);
        }
        window.addEventListener('popstate', (e) => {
            const ut = new URLSearchParams(window.location.search).get('ticket');
            if (ut && this.ticketsDetailMap[ut]) {
                this.openDetailModal(Number(ut), false);
            } else {
                this.showDetailTicketModal = false;
            }
        });
    },

    // Detail Modal Actions
    openDetailModal(id, updateUrl = true) {
        const t = this.ticketsDetailMap[Number(id)];
        if (!t) return;
        this.detailTicket = t;
        this.activeDetailTab = 'details';
        this.detailStatusDropdownOpen = false;
        this.closeAllDropdowns();
        this.showDetailTicketModal = true;
        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('ticket', id);
            window.history.pushState({ ticket: id }, '', url.toString());
        }
    },

    closeDetailModal() {
        this.showDetailTicketModal = false;
        const url = new URL(window.location.href);
        if (url.searchParams.has('ticket')) {
            url.searchParams.delete('ticket');
            window.history.pushState({}, '', url.toString());
        }
    },

    openEditFromDetail() {
        if (!this.detailTicket) return;
        const d = this.detailTicket;
        this.closeDetailModal();
        this.openEditModal({
            id: d.id,
            title: d.title,
            description: d.description,
            type: d.type,
            priority: d.priority,
            error_category: d.error_category,
            milestone_id: d.milestone_id,
            status: d.status,
            assignee_ids: d.assignee_ids,
            update_url: d.urls.update,
        });
    },

    // --- CREATE MODAL STATE ---
    showCreateTicketModal: false,
    createSubmitting: false,
    selectedType: 'bug',
    selectedPriority: 'medium',
    selectedErrorCategory: '',
    selectedMilestoneId: '',
    selectedAssigneeIds: [],
    createAssigneeSearch: '',

    // Create dropdown states
    typeDropdownOpen: false,
    priorityDropdownOpen: false,
    categoryDropdownOpen: false,
    milestoneDropdownOpen: false,
    assigneeDropdownOpen: false,

    // --- EDIT MODAL STATE ---
    showEditTicketModal: false,
    editSubmitting: false,
    editTicket: {
        id: null,
        title: '',
        description: '',
        type: 'bug',
        priority: 'medium',
        error_category: '',
        milestone_id: '',
        status: 'open',
        assignee_ids: [],
        update_url: '',
    },
    editAssigneeSearch: '',

    // Edit dropdown states
    editTypeDropdownOpen: false,
    editPriorityDropdownOpen: false,
    editCategoryDropdownOpen: false,
    editMilestoneDropdownOpen: false,
    editStatusDropdownOpen: false,
    editAssigneeDropdownOpen: false,

    // Methods
    closeAllDropdowns() {
        this.typeDropdownOpen = false;
        this.priorityDropdownOpen = false;
        this.categoryDropdownOpen = false;
        this.milestoneDropdownOpen = false;
        this.assigneeDropdownOpen = false;

        this.editTypeDropdownOpen = false;
        this.editPriorityDropdownOpen = false;
        this.editCategoryDropdownOpen = false;
        this.editMilestoneDropdownOpen = false;
        this.editStatusDropdownOpen = false;
        this.editAssigneeDropdownOpen = false;
        this.detailStatusDropdownOpen = false;
    },

    // Create modal assignee helpers
    toggleCreateAssignee(id) {
        const numId = Number(id);
        const idx = this.selectedAssigneeIds.indexOf(numId);
        if (idx > -1) {
            this.selectedAssigneeIds.splice(idx, 1);
        } else {
            this.selectedAssigneeIds.push(numId);
        }
    },
    removeCreateAssignee(id) {
        const numId = Number(id);
        const idx = this.selectedAssigneeIds.indexOf(numId);
        if (idx > -1) {
            this.selectedAssigneeIds.splice(idx, 1);
        }
    },
    get filteredCreateAssignees() {
        if (!this.createAssigneeSearch.trim()) return this.allAssignees;
        const q = this.createAssigneeSearch.toLowerCase();
        return this.allAssignees.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
    },
    get selectedCreateAssigneeObjects() {
        return this.allAssignees.filter(u => this.selectedAssigneeIds.includes(u.id));
    },

    // Edit modal assignee helpers
    toggleEditAssignee(id) {
        const numId = Number(id);
        const idx = this.editTicket.assignee_ids.indexOf(numId);
        if (idx > -1) {
            this.editTicket.assignee_ids.splice(idx, 1);
        } else {
            this.editTicket.assignee_ids.push(numId);
        }
    },
    removeEditAssignee(id) {
        const numId = Number(id);
        const idx = this.editTicket.assignee_ids.indexOf(numId);
        if (idx > -1) {
            this.editTicket.assignee_ids.splice(idx, 1);
        }
    },
    get filteredEditAssignees() {
        if (!this.editAssigneeSearch.trim()) return this.allAssignees;
        const q = this.editAssigneeSearch.toLowerCase();
        return this.allAssignees.filter(u => u.name.toLowerCase().includes(q) || u.email.toLowerCase().includes(q));
    },
    get selectedEditAssigneeObjects() {
        return this.allAssignees.filter(u => this.editTicket.assignee_ids.includes(u.id));
    },

    openEditModal(data) {
        this.closeAllDropdowns();
        this.editTicket = {
            id: data.id,
            title: data.title || '',
            description: data.description || '',
            type: data.type || 'bug',
            priority: data.priority || 'medium',
            error_category: data.error_category || '',
            milestone_id: data.milestone_id ? String(data.milestone_id) : '',
            status: data.status || 'open',
            assignee_ids: Array.isArray(data.assignee_ids) ? data.assignee_ids.map(Number) : [],
            update_url: data.update_url,
        };
        this.editAssigneeSearch = '';
        this.showEditTicketModal = true;
    }
}" class="space-y-6 w-full max-w-full min-w-0">

    {{-- ── 1. KPI Metric Summary Cards (Flat, No Border, No Shadow) ────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {{-- Total Tickets --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => null, 'priority' => null, 'type' => null, 'search' => null]) }}"
           class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-slate-100 dark:bg-slate-800 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-slate-600 dark:text-slate-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">TOTAL TICKETS</span>
                <span class="text-2xl font-bold text-gray-900 dark:text-white leading-none mt-1 block">{{ $stats['total'] }}</span>
            </div>
        </a>

        {{-- Active / Open Tickets --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'open']) }}"
           class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-950/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-amber-600 dark:text-amber-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">TIKET AKTIF</span>
                <span class="text-2xl font-bold text-amber-600 dark:text-amber-400 leading-none mt-1 block">{{ $stats['open'] }}</span>
            </div>
        </a>

        {{-- Resolved Tickets --}}
        <a href="{{ request()->fullUrlWithQuery(['status' => 'resolved']) }}"
           class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5 hover:bg-gray-50/80 dark:hover:bg-gray-800/80 transition group">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-950/50 flex items-center justify-center shrink-0 group-hover:scale-105 transition-transform text-emerald-600 dark:text-emerald-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">SELESAI</span>
                <span class="text-2xl font-bold text-emerald-600 dark:text-emerald-400 leading-none mt-1 block">{{ $stats['resolved'] }}</span>
            </div>
        </a>

        {{-- SLA Breached --}}
        <div class="bg-white dark:bg-gray-850 rounded-2xl p-4.5 flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-xl bg-rose-50 dark:bg-rose-950/50 flex items-center justify-center shrink-0 text-rose-600 dark:text-rose-400">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />
                </svg>
            </div>
            <div class="min-w-0">
                <span class="text-[11px] font-bold tracking-wider text-gray-400 uppercase block truncate">SLA BREACH</span>
                <span class="text-2xl font-bold text-rose-600 dark:text-rose-400 leading-none mt-1 block">{{ $stats['breached'] }}</span>
            </div>
        </div>
    </div>

    {{-- ── 2. Main Section Card (No Border, No Shadow per Request) ──────── --}}
    <div class="bg-white dark:bg-gray-850 rounded-2xl">
        {{-- Card Header: Title + Create Ticket Button (View All Hidden) --}}
        <div class="px-6 py-4.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between flex-wrap gap-4 rounded-t-2xl">
            <div>
                <h2 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-xl bg-rose-50 dark:bg-rose-950/60 text-rose-600 dark:text-rose-400 flex items-center justify-center">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </span>
                    <span>Tickets & Issues</span>
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400">
                        {{ $stats['total'] }}
                    </span>
                </h2>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                    Daftar tiket kendala, bug, dan issue teknis yang dilaporkan pada proyek ini
                </p>
            </div>

            {{-- Actions: Create Ticket Modal Pop-up Trigger (No View All) --}}
            <div class="flex items-center gap-2.5">
                <button type="button"
                        @click="showCreateTicketModal = true"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-semibold rounded-xl transition shadow-2xs cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Create Ticket</span>
                </button>
            </div>
        </div>

        {{-- Filter Toolbar with Custom Dropdown Selects (Matching Projects Index) --}}
        <div class="relative z-30 p-4 sm:px-6 border-b border-gray-100 dark:border-gray-800 bg-gray-50/50 dark:bg-gray-900/30">
            <div class="flex flex-wrap items-center gap-2.5">

                {{-- Search Bar --}}
                <div class="relative flex-1 min-w-[220px] max-w-xs">
                    <svg class="w-4 h-4 text-gray-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input type="text"
                           value="{{ request('search') }}"
                           placeholder="Cari judul tiket..."
                           @keydown.enter.prevent="setFilter('search', $el.value)"
                           class="w-full pl-9.5 pr-4 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50/80 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm text-gray-800 dark:text-gray-200 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition shadow-2xs">
                </div>

                {{-- Status Custom Filter Select --}}
                <div class="relative" :class="open ? 'z-50' : 'z-10'" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 transition cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>Status: <strong class="font-semibold text-gray-900 dark:text-white">{{ $selectedFilterStatusLabel }}</strong></span>
                        <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1.5 min-w-[200px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-1.5 z-50 space-y-0.5 max-h-64 overflow-y-auto">
                        @foreach($filterStatusOptions as $val => $lbl)
                            <button type="button"
                                     @click="setFilter('status', '{{ $val }}')"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer {{ $selectedFilterStatus === (string) $val ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200' }}">
                                <div class="flex items-center gap-2">
                                    @if($val && isset($statusMeta[$val]))
                                        <span class="w-2 h-2 rounded-full {{ $statusMeta[$val]['dot'] }}"></span>
                                    @endif
                                    <span>{{ $lbl }}</span>
                                </div>
                                @if($selectedFilterStatus === (string) $val)
                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Priority Custom Filter Select --}}
                <div class="relative" :class="open ? 'z-50' : 'z-10'" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 transition cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                        <span>Prioritas: <strong class="font-semibold text-gray-900 dark:text-white">{{ $selectedFilterPriorityLabel }}</strong></span>
                        <svg class="w-3 h-3 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1.5 min-w-[200px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-1.5 z-50 space-y-0.5">
                        @foreach($filterPriorityOptions as $val => $lbl)
                            <button type="button"
                                    @click="setFilter('priority', '{{ $val }}')"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer {{ $selectedFilterPriority === (string) $val ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200' }}">
                                <div class="flex items-center gap-2">
                                    @if($val && isset($priorityMeta[$val]))
                                        <span class="w-2 h-2 rounded-full {{ $priorityMeta[$val]['dot'] }}"></span>
                                    @endif
                                    <span>{{ $lbl }}</span>
                                </div>
                                @if($selectedFilterPriority === (string) $val)
                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Type Custom Filter Select --}}
                <div class="relative" :class="open ? 'z-50' : 'z-10'" x-data="{ open: false }">
                    <button @click="open = !open" @click.away="open = false" type="button"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-750 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-700 dark:text-gray-200 transition cursor-pointer shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                        <span>Tipe: <strong class="font-semibold text-gray-900 dark:text-white">{{ $selectedFilterTypeLabel }}</strong></span>
                        <svg class="w-3.5 h-3.5 text-gray-400 shrink-0 transition-transform duration-150" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>
                    <div x-show="open" x-cloak
                         class="absolute left-0 top-full mt-1.5 min-w-[200px] bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-2xl p-1.5 z-50 space-y-0.5">
                        @foreach($filterTypeOptions as $val => $lbl)
                            <button type="button"
                                    @click="setFilter('type', '{{ $val }}')"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between hover:bg-gray-50 dark:hover:bg-gray-700/60 transition cursor-pointer {{ $selectedFilterType === (string) $val ? 'text-blue-600 dark:text-blue-400 font-bold bg-blue-50/70 dark:bg-blue-950/50' : 'text-gray-700 dark:text-gray-200' }}">
                                <span>{{ $lbl }}</span>
                                @if($selectedFilterType === (string) $val)
                                    <svg class="w-3.5 h-3.5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="m4.5 12.75 6 6 9-13.5"/>
                                    </svg>
                                @endif
                            </button>
                        @endforeach
                    </div>
                </div>

                {{-- Reset Filter Button --}}
                @if(request('search') || request('status') || request('priority') || request('type'))
                    <a href="{{ url()->current() }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 rounded-xl text-xs font-semibold text-rose-600 hover:text-rose-700 dark:text-rose-400 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Reset Filter</span>
                    </a>
                @endif
            </div>
        </div>

        {{-- ── 3. Tickets Table ────────────────────────────────────────── --}}
        <div class="overflow-x-auto rounded-b-2xl">
            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800 text-left">
                <thead class="bg-gray-50/70 dark:bg-gray-800/40 text-[11px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider">
                    <tr>
                        <th class="px-6 py-3.5">Tiket / Masalah</th>
                        <th class="px-4 py-3.5">Milestone</th>
                        <th class="px-4 py-3.5">Prioritas</th>
                        <th class="px-4 py-3.5">Status</th>
                        <th class="px-4 py-3.5">SLA</th>
                        <th class="px-4 py-3.5">Petugas (Assignee)</th>
                        <th class="px-4 py-3.5">Dibuat</th>
                        <th class="px-6 py-3.5 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-gray-800/60 bg-white dark:bg-gray-850">
                    @forelse($tickets as $ticket)
                        @php
                            $pm = $priorityMeta[$ticket->priority ?? 'medium'] ?? $priorityMeta['medium'];
                            $tm = $typeMeta[$ticket->type ?? 'bug'] ?? $typeMeta['bug'];
                            $sm = $statusMeta[$ticket->status ?? 'open'] ?? $statusMeta['open'];

                            // Multi-assignees collection
                            $assignedUsers = $ticket->assignees->isNotEmpty()
                                ? $ticket->assignees
                                : ($ticket->assignee ? collect([$ticket->assignee]) : collect());

                            // Prepare serialized data for edit modal
                            $ticketEditData = [
                                'id'             => $ticket->id,
                                'title'          => $ticket->title,
                                'description'    => $ticket->description,
                                'type'           => $ticket->type ?? 'bug',
                                'priority'       => $ticket->priority ?? 'medium',
                                'error_category' => $ticket->error_category ?? '',
                                'milestone_id'   => $ticket->milestone_id ? (string) $ticket->milestone_id : '',
                                'status'         => $ticket->status ?? 'open',
                                'assignee_ids'   => $assignedUsers->pluck('id')->map(fn($id) => (int)$id)->all(),
                                'update_url'     => route('tickets.update', $ticket),
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50/70 dark:hover:bg-gray-800/40 transition group cursor-pointer"
                            @click="openDetailModal({{ $ticket->id }})">

                            {{-- Ticket Title & Preview --}}
                            <td class="px-6 py-3.5 max-w-md">
                                <div class="flex items-start gap-2.5">
                                    <div class="pt-0.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider {{ $tm['badge'] }}">
                                            {{ $tm['label'] }}
                                        </span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-1.5">
                                            <span class="text-xs font-mono text-gray-400">#{{ $ticket->id }}</span>
                                            <a href="javascript:void(0)"
                                               @click.stop.prevent="openDetailModal({{ $ticket->id }})"
                                               class="text-xs sm:text-sm font-semibold text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition truncate block">
                                                {{ $ticket->title }}
                                            </a>
                                        </div>
                                        @if($ticket->description)
                                            <p class="text-xs text-gray-400 dark:text-gray-500 truncate mt-0.5">
                                                {{ Str::limit(strip_tags($ticket->description), 80) }}
                                            </p>
                                        @endif
                                        <div class="flex items-center gap-2 mt-1 flex-wrap">
                                            @if($ticket->error_category)
                                                <span class="text-[10px] px-1.5 py-0.5 rounded bg-gray-100 dark:bg-gray-800 text-gray-500 font-medium">
                                                    {{ $errorCategories[$ticket->error_category]['label'] ?? ucfirst($ticket->error_category) }}
                                                </span>
                                            @endif
                                            @if($ticket->attachments && $ticket->attachments->count())
                                                <span class="inline-flex items-center gap-0.5 text-[10px] text-gray-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                                                    {{ $ticket->attachments->count() }}
                                                </span>
                                            @endif
                                            @if($ticket->comments && $ticket->comments->count())
                                                <span class="inline-flex items-center gap-0.5 text-[10px] text-gray-400">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.767-.932 6.002 6.002 0 0 0 1.054-2.85A8.19 8.19 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                                    {{ $ticket->comments->count() }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </td>

                            {{-- Milestone --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($ticket->milestone)
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-medium bg-blue-50/70 text-blue-700 dark:bg-blue-950/40 dark:text-blue-300">
                                        <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 0 1 2-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 0 0-2 2Zm9-13.5V9" /></svg>
                                        <span class="max-w-[130px] truncate" title="{{ $ticket->milestone->title }}">{{ $ticket->milestone->title }}</span>
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Priority --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $pm['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $pm['dot'] }}"></span>
                                    {{ $pm['label'] }}
                                </span>
                            </td>

                            {{-- Status --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold {{ $sm['badge'] }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $sm['dot'] }}"></span>
                                    {{ $sm['label'] }}
                                </span>
                            </td>

                            {{-- SLA --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($ticket->sla_breached)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300">
                                        <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                                        BREACHED
                                    </span>
                                @elseif($ticket->sla_due_at)
                                    <span class="text-xs font-medium text-gray-600 dark:text-gray-300 flex items-center gap-1" title="{{ $ticket->sla_due_at->format('d M Y H:i') }}">
                                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                                        {{ $ticket->sla_due_at->diffForHumans() }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Assignee & Reporter (Avatar Stack for multiple assignees) --}}
                            <td class="px-4 py-3.5 whitespace-nowrap">
                                @if($assignedUsers->isNotEmpty())
                                    <div class="flex items-center gap-2">
                                        <div class="flex -space-x-1.5 overflow-hidden shrink-0">
                                            @foreach($assignedUsers->take(3) as $u)
                                                @if($u->avatar)
                                                    <img src="{{ Storage::url($u->avatar) }}" alt="{{ $u->name }}" title="{{ $u->name }}" class="w-6 h-6 rounded-full object-cover ring-2 ring-white dark:ring-gray-800">
                                                @else
                                                    <div class="w-6 h-6 rounded-full text-white flex items-center justify-center text-[10px] font-bold ring-2 ring-white dark:ring-gray-800"
                                                         style="background-color: {{ $u->avatarColor() }};" title="{{ $u->name }}">
                                                        {{ $u->initials() }}
                                                    </div>
                                                @endif
                                            @endforeach
                                            @if($assignedUsers->count() > 3)
                                                <div class="w-6 h-6 rounded-full bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 flex items-center justify-center text-[9px] font-bold ring-2 ring-white dark:ring-gray-800"
                                                     title="{{ $assignedUsers->slice(3)->pluck('name')->implode(', ') }}">
                                                    +{{ $assignedUsers->count() - 3 }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <span class="text-xs font-medium text-gray-800 dark:text-gray-200 block truncate max-w-[130px]" title="{{ $assignedUsers->pluck('name')->implode(', ') }}">
                                                {{ $assignedUsers->first()->name }}{{ $assignedUsers->count() > 1 ? ' +' . ($assignedUsers->count() - 1) : '' }}
                                            </span>
                                            @if($ticket->reporter)
                                                <span class="text-[10px] text-gray-400 block truncate">Oleh: {{ $ticket->reporter->name }}</span>
                                            @endif
                                        </div>
                                    </div>
                                @else
                                    <div class="min-w-0">
                                        <span class="text-xs text-gray-400 italic block">Unassigned</span>
                                        @if($ticket->reporter)
                                            <span class="text-[10px] text-gray-400 block truncate">Oleh: {{ $ticket->reporter->name }}</span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            {{-- Created At --}}
                            <td class="px-4 py-3.5 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                {{ $ticket->created_at->format('d M Y') }}
                            </td>

                            {{-- Actions: Edit Button (Modal Pop-up) + View Detail Button --}}
                            <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1" @click.stop>
                                    {{-- Edit Ticket Modal Button --}}
                                    <button type="button"
                                            @click.stop="openEditModal({{ Js::from($ticketEditData) }})"
                                            title="Edit Tiket"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-gray-400 hover:text-amber-600 hover:bg-amber-50 dark:hover:bg-amber-950/50 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                        </svg>
                                    </button>

                                    {{-- View Detail Button (Modal Pop-up) --}}
                                    <button type="button"
                                            @click.stop="openDetailModal({{ $ticket->id }})"
                                            title="Lihat Detail Tiket"
                                            class="inline-flex items-center justify-center w-8 h-8 rounded-xl text-gray-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-950/50 transition cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <div class="w-12 h-12 rounded-2xl bg-blue-50 dark:bg-blue-950/60 text-blue-500 flex items-center justify-center mx-auto mb-3">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0 1 18 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-200 mb-1">Belum ada tiket yang dilaporkan</h3>
                                <p class="text-xs text-gray-400 max-w-md mx-auto mb-4">
                                    Semua modul dan fitur proyek ini berjalan lancar. Klik tombol di bawah untuk melaporkan masalah baru.
                                </p>
                                <button type="button" @click="showCreateTicketModal = true"
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-xl transition shadow-2xs cursor-pointer">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Buat Tiket Baru</span>
                                </button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if($tickets instanceof \Illuminate\Pagination\LengthAwarePaginator && $tickets->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-between gap-4 flex-wrap">
                <span class="text-xs text-gray-500 dark:text-gray-400">
                    Menampilkan {{ $tickets->firstItem() ?? 0 }} - {{ $tickets->lastItem() ?? 0 }} dari {{ $tickets->total() }} tiket
                </span>
                <div>
                    {{ $tickets->links() }}
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================
         CREATE TICKET MODAL POP-UP (Matching Task UI & Custom Selects)
    ============================================================ --}}
    <template x-teleport="body">
        <div x-show="showCreateTicketModal" x-cloak
             class="fixed inset-0 z-[100] w-screen h-screen flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
             @keydown.escape.window="showCreateTicketModal = false"
             @click.self="showCreateTicketModal = false">

            {{-- Modal Dialog Card --}}
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-850 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden transform transition-all max-h-[92vh] flex flex-col"
                 @click.stop>

            {{-- Modal Header --}}
            <div class="px-6 py-4.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 bg-white dark:bg-gray-850 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-rose-50 dark:bg-rose-950/60 border border-rose-200/60 dark:border-rose-900/60 flex items-center justify-center text-rose-600 dark:text-rose-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white">Create New Ticket</h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Laporkan bug, issue teknis, atau permintaan perbaikan</p>
                    </div>
                </div>
                <button type="button" @click="showCreateTicketModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Modal Body Form --}}
            <form method="POST" action="{{ route('tickets.store', $project) }}" enctype="multipart/form-data"
                  class="flex-1 overflow-y-auto p-6 space-y-4.5"
                  @submit="if(createSubmitting){ $event.preventDefault(); } else { createSubmitting = true; }">
                @csrf
                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                <input type="hidden" name="type" :value="selectedType">
                <input type="hidden" name="priority" :value="selectedPriority">
                <input type="hidden" name="error_category" :value="selectedErrorCategory">
                <input type="hidden" name="milestone_id" :value="selectedMilestoneId">
                <input type="hidden" name="assignee_id" :value="selectedAssigneeIds[0] || ''">

                {{-- Hidden Assignee IDs array inputs --}}
                <template x-for="id in selectedAssigneeIds" :key="id">
                    <input type="hidden" name="assignee_ids[]" :value="id">
                </template>

                {{-- Judul Tiket --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        Judul Tiket <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" required placeholder="Contoh: Tombol bayar checkout tidak merespons di iOS Safari"
                           class="w-full text-xs sm:text-sm font-semibold px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                {{-- Deskripsi Masalah --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        Deskripsi Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="description" rows="3" required placeholder="Jelaskan langkah-langkah untuk mereproduksi masalah, apa yang diharapkan, dan apa yang sebenarnya terjadi..."
                              class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition resize-none"></textarea>
                </div>

                {{-- Custom Selects: Prioritas & Tipe --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Prioritas Select --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Prioritas</label>
                        <button type="button"
                                @click="priorityDropdownOpen = !priorityDropdownOpen; closeAllDropdowns(); priorityDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full"
                                      :class="{
                                          'bg-red-500': selectedPriority === 'critical',
                                          'bg-orange-500': selectedPriority === 'high',
                                          'bg-blue-500': selectedPriority === 'medium',
                                          'bg-slate-400': selectedPriority === 'low'
                                      }"></span>
                                <span class="capitalize font-semibold" x-text="selectedPriority"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="priorityDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Priority Dropdown Popover --}}
                        <div x-show="priorityDropdownOpen" @click.outside="priorityDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5">
                            @foreach($priorityMeta as $pKey => $pInfo)
                                <button type="button"
                                        @click="selectedPriority = '{{ $pKey }}'; priorityDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="selectedPriority === '{{ $pKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full {{ $pInfo['dot'] }}"></span>
                                        <div>
                                            <span class="block">{{ $pInfo['label'] }}</span>
                                            <span class="text-[10px] text-gray-400 block font-normal">{{ $pInfo['desc'] }}</span>
                                        </div>
                                    </div>
                                    <svg x-show="selectedPriority === '{{ $pKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Tipe Tiket Select --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Tipe Masalah</label>
                        <button type="button"
                                @click="typeDropdownOpen = !typeDropdownOpen; closeAllDropdowns(); typeDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2">
                                <span class="capitalize font-semibold" x-text="selectedType"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="typeDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Type Dropdown Popover --}}
                        <div x-show="typeDropdownOpen" @click.outside="typeDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5">
                            @foreach($typeMeta as $tKey => $tInfo)
                                <button type="button"
                                        @click="selectedType = '{{ $tKey }}'; typeDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="selectedType === '{{ $tKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $tInfo['label'] }}</span>
                                    <svg x-show="selectedType === '{{ $tKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Custom Selects: Kategori Error & Milestone (Redesigned like Prioritas) --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Kategori Error Custom Popover --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Kategori Error (Opsional)</label>
                        <button type="button"
                                @click="categoryDropdownOpen = !categoryDropdownOpen; closeAllDropdowns(); categoryDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full" :class="selectedErrorCategory ? 'bg-purple-500' : 'bg-gray-300 dark:bg-gray-600'"></span>
                                <span x-text="selectedErrorCategory ? (errorCategoryMap[selectedErrorCategory] || selectedErrorCategory) : '— Pilih Kategori —'"
                                      :class="selectedErrorCategory ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-400'"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="categoryDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Category Dropdown Popover --}}
                        <div x-show="categoryDropdownOpen" @click.outside="categoryDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5 max-h-56 overflow-y-auto">
                            <button type="button"
                                    @click="selectedErrorCategory = ''; categoryDropdownOpen = false"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-500">
                                <span>— Tanpa Kategori Error —</span>
                                <svg x-show="selectedErrorCategory === ''" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            @foreach($errorCategories as $cKey => $cInfo)
                                <button type="button"
                                        @click="selectedErrorCategory = '{{ $cKey }}'; categoryDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="selectedErrorCategory === '{{ $cKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <span class="truncate">{{ $cInfo['label'] }}</span>
                                    <svg x-show="selectedErrorCategory === '{{ $cKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Milestone Custom Popover --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Milestone (Opsional)</label>
                        <button type="button"
                                @click="milestoneDropdownOpen = !milestoneDropdownOpen; closeAllDropdowns(); milestoneDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2 truncate">
                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 0 1 2-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 0 0-2 2Zm9-13.5V9" />
                                </svg>
                                <span x-text="selectedMilestoneId ? ((allMilestones.find(m => m.id === selectedMilestoneId) || {}).title || 'Milestone Terpilih') : '— Tanpa Milestone —'"
                                      :class="selectedMilestoneId ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-400'"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="milestoneDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Milestone Dropdown Popover --}}
                        <div x-show="milestoneDropdownOpen" @click.outside="milestoneDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5 max-h-56 overflow-y-auto">
                            <button type="button"
                                    @click="selectedMilestoneId = ''; milestoneDropdownOpen = false"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-500">
                                <span>— Tanpa Milestone —</span>
                                <svg x-show="selectedMilestoneId === ''" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            <template x-for="m in allMilestones" :key="m.id">
                                <button type="button"
                                        @click="selectedMilestoneId = m.id; milestoneDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="selectedMilestoneId === m.id ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <span class="truncate" x-text="m.title"></span>
                                    <svg x-show="selectedMilestoneId === m.id" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Tugaskan ke Anggota Tim: Searchable Multi-Select (Members Only, No Admin/Client) --}}
                <div class="space-y-1.5 relative">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center justify-between">
                        <span>Tugaskan ke Anggota Tim (Hanya Member)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Bisa pilih lebih dari 1</span>
                    </label>

                    {{-- Trigger Button showing Chips or Placeholder --}}
                    <div @click="assigneeDropdownOpen = !assigneeDropdownOpen; closeAllDropdowns(); assigneeDropdownOpen = true"
                         class="w-full min-h-[42px] px-3 py-1.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs flex items-center justify-between gap-2 transition cursor-pointer">
                        <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0 py-0.5">
                            <template x-if="selectedAssigneeIds.length === 0">
                                <span class="text-gray-400 text-xs sm:text-sm pl-1">— Pilih Petugas (Member Tim) —</span>
                            </template>
                            <template x-for="user in selectedCreateAssigneeObjects" :key="user.id">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-medium text-xs">
                                    <template x-if="user.avatar">
                                        <img :src="user.avatar" class="w-4 h-4 rounded-full object-cover">
                                    </template>
                                    <template x-if="!user.avatar">
                                        <span class="w-4 h-4 rounded-full text-white text-[9px] font-bold flex items-center justify-center shrink-0"
                                              :style="'background-color: ' + user.color" x-text="user.initials"></span>
                                    </template>
                                    <span x-text="user.name" class="max-w-[120px] truncate"></span>
                                    <button type="button" @click.stop="removeCreateAssignee(user.id)" class="text-blue-400 hover:text-blue-600 transition">
                                        ✕
                                    </button>
                                </span>
                            </template>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="assigneeDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    {{-- Searchable Multi-Select Popover --}}
                    <div x-show="assigneeDropdownOpen" @click.outside="assigneeDropdownOpen = false" x-cloak
                         class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-2 z-50 space-y-2">
                        {{-- Search Input inside Popover --}}
                        <div class="relative">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="text" x-model="createAssigneeSearch" placeholder="Cari nama atau email member..."
                                   class="w-full pl-8.5 pr-3 py-1.5 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-gray-900 dark:text-white placeholder-gray-400">
                        </div>

                        {{-- Member list with checkboxes --}}
                        <div class="max-h-52 overflow-y-auto space-y-1 pr-1">
                            <template x-for="user in filteredCreateAssignees" :key="user.id">
                                <div @click="toggleCreateAssignee(user.id)"
                                     class="px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                     :class="selectedAssigneeIds.includes(user.id) ? 'bg-blue-50/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-700 dark:text-gray-200'">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <template x-if="user.avatar">
                                            <img :src="user.avatar" class="w-6 h-6 rounded-full object-cover shrink-0">
                                        </template>
                                        <template x-if="!user.avatar">
                                            <div class="w-6 h-6 rounded-full text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                                 :style="'background-color: ' + user.color" x-text="user.initials"></div>
                                        </template>
                                        <div class="min-w-0">
                                            <span class="block truncate" x-text="user.name"></span>
                                            <span class="text-[10px] text-gray-400 block truncate font-normal" x-text="user.email"></span>
                                        </div>
                                    </div>
                                    <div class="w-4 h-4 rounded-md border flex items-center justify-center transition shrink-0 ml-2"
                                         :class="selectedAssigneeIds.includes(user.id) ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 dark:border-gray-600'">
                                        <svg x-show="selectedAssigneeIds.includes(user.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredCreateAssignees.length === 0">
                                <div class="px-3 py-4 text-center text-xs text-gray-400">
                                    Tidak ada member yang cocok
                                </div>
                            </template>
                        </div>

                        {{-- Popover Footer with selection count and done button --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-[11px] text-gray-400">
                            <span x-text="selectedAssigneeIds.length + ' member dipilih'"></span>
                            <button type="button" @click="assigneeDropdownOpen = false"
                                    class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Lampiran / Attachments --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Lampiran File / Screenshot (Maks. 5 file)</label>
                    <div class="p-3.5 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50/40 dark:bg-gray-900/40 hover:bg-gray-50 dark:hover:bg-gray-900/70 transition">
                        <input type="file" name="attachments[]" multiple class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        <p class="text-[11px] text-gray-400 mt-1">Maks. 5 file (PNG, JPG, PDF, ZIP), masing-masing maksimal 10MB.</p>
                    </div>
                </div>

                {{-- Modal Actions --}}
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" @click="showCreateTicketModal = false"
                            class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="createSubmitting"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-2 cursor-pointer">
                        <svg x-show="createSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="createSubmitting ? 'Menyimpan...' : 'Buat Tiket'">Buat Tiket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    {{-- ============================================================
         EDIT TICKET MODAL POP-UP (Matching Task UI & Custom Selects)
    ============================================================ --}}
    <template x-teleport="body">
        <div x-show="showEditTicketModal" x-cloak
             class="fixed inset-0 z-[100] w-screen h-screen flex items-center justify-center p-3 sm:p-4 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
             @keydown.escape.window="showEditTicketModal = false"
             @click.self="showEditTicketModal = false">

            {{-- Modal Dialog Card --}}
            <div class="relative w-full max-w-2xl bg-white dark:bg-gray-850 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden transform transition-all max-h-[92vh] flex flex-col"
                 @click.stop>

            {{-- Modal Header --}}
            <div class="px-6 py-4.5 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 bg-white dark:bg-gray-850 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-amber-50 dark:bg-amber-950/60 border border-amber-200/60 dark:border-amber-900/60 flex items-center justify-center text-amber-600 dark:text-amber-400 shrink-0">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-base font-bold text-gray-900 dark:text-white flex items-center gap-2">
                            <span>Edit Ticket</span>
                            <span class="text-xs font-mono text-gray-400 font-normal" x-text="'#' + editTicket.id"></span>
                        </h3>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">Perbarui detail kendala, prioritas, status, atau petugas</p>
                    </div>
                </div>
                <button type="button" @click="showEditTicketModal = false"
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Modal Body Form --}}
            <form method="POST" :action="editTicket.update_url" enctype="multipart/form-data"
                  class="flex-1 overflow-y-auto p-6 space-y-4.5"
                  @submit="if(editSubmitting){ $event.preventDefault(); } else { editSubmitting = true; }">
                @csrf
                @method('PUT')
                <input type="hidden" name="redirect_to" value="{{ url()->full() }}">
                <input type="hidden" name="type" :value="editTicket.type">
                <input type="hidden" name="priority" :value="editTicket.priority">
                <input type="hidden" name="error_category" :value="editTicket.error_category">
                <input type="hidden" name="milestone_id" :value="editTicket.milestone_id">
                <input type="hidden" name="status" :value="editTicket.status">
                <input type="hidden" name="assignee_id" :value="editTicket.assignee_ids[0] || ''">

                {{-- Hidden Assignee IDs array inputs --}}
                <template x-for="id in editTicket.assignee_ids" :key="id">
                    <input type="hidden" name="assignee_ids[]" :value="id">
                </template>

                {{-- Judul Tiket --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        Judul Tiket <span class="text-rose-500">*</span>
                    </label>
                    <input type="text" name="title" x-model="editTicket.title" required placeholder="Judul tiket masalah..."
                           class="w-full text-xs sm:text-sm font-semibold px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition">
                </div>

                {{-- Deskripsi Masalah --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">
                        Deskripsi Lengkap <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="description" x-model="editTicket.description" rows="3" required placeholder="Deskripsi kendala..."
                              class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition resize-none"></textarea>
                </div>

                {{-- Custom Selects: Status & Prioritas --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Status Select --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Status Tiket</label>
                        <button type="button"
                                @click="editStatusDropdownOpen = !editStatusDropdownOpen; closeAllDropdowns(); editStatusDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full"
                                      :class="{
                                          'bg-sky-500': editTicket.status === 'open',
                                          'bg-indigo-500': editTicket.status === 'assigned',
                                          'bg-amber-500': editTicket.status === 'in_progress',
                                          'bg-purple-500': editTicket.status === 'pending_review',
                                          'bg-emerald-500': editTicket.status === 'resolved',
                                          'bg-gray-400': editTicket.status === 'closed',
                                          'bg-rose-500': editTicket.status === 'reopened'
                                      }"></span>
                                <span class="capitalize font-semibold" x-text="editTicket.status.replace('_', ' ')"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="editStatusDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Status Dropdown Popover --}}
                        <div x-show="editStatusDropdownOpen" @click.outside="editStatusDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5">
                            @foreach($statusMeta as $stKey => $stInfo)
                                <button type="button"
                                        @click="editTicket.status = '{{ $stKey }}'; editStatusDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="editTicket.status === '{{ $stKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <div class="flex items-center gap-2">
                                        <span class="w-2 h-2 rounded-full {{ $stInfo['dot'] }}"></span>
                                        <span>{{ $stInfo['label'] }}</span>
                                    </div>
                                    <svg x-show="editTicket.status === '{{ $stKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Prioritas Select --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Prioritas</label>
                        <button type="button"
                                @click="editPriorityDropdownOpen = !editPriorityDropdownOpen; closeAllDropdowns(); editPriorityDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full"
                                      :class="{
                                          'bg-red-500': editTicket.priority === 'critical',
                                          'bg-orange-500': editTicket.priority === 'high',
                                          'bg-blue-500': editTicket.priority === 'medium',
                                          'bg-slate-400': editTicket.priority === 'low'
                                      }"></span>
                                <span class="capitalize font-semibold" x-text="editTicket.priority"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="editPriorityDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Priority Dropdown Popover --}}
                        <div x-show="editPriorityDropdownOpen" @click.outside="editPriorityDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5">
                            @foreach($priorityMeta as $pKey => $pInfo)
                                <button type="button"
                                        @click="editTicket.priority = '{{ $pKey }}'; editPriorityDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="editTicket.priority === '{{ $pKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <div class="flex items-center gap-2.5">
                                        <span class="w-2 h-2 rounded-full {{ $pInfo['dot'] }}"></span>
                                        <div>
                                            <span class="block">{{ $pInfo['label'] }}</span>
                                            <span class="text-[10px] text-gray-400 block font-normal">{{ $pInfo['desc'] }}</span>
                                        </div>
                                    </div>
                                    <svg x-show="editTicket.priority === '{{ $pKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Custom Selects: Tipe & Kategori Error --}}
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    {{-- Tipe Tiket Select --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Tipe Masalah</label>
                        <button type="button"
                                @click="editTypeDropdownOpen = !editTypeDropdownOpen; closeAllDropdowns(); editTypeDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="capitalize font-semibold" x-text="editTicket.type"></span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150" :class="editTypeDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Type Dropdown Popover --}}
                        <div x-show="editTypeDropdownOpen" @click.outside="editTypeDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5">
                            @foreach($typeMeta as $tKey => $tInfo)
                                <button type="button"
                                        @click="editTicket.type = '{{ $tKey }}'; editTypeDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="editTicket.type === '{{ $tKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <span>{{ $tInfo['label'] }}</span>
                                    <svg x-show="editTicket.type === '{{ $tKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Kategori Error Custom Popover --}}
                    <div class="space-y-1.5 relative">
                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Kategori Error (Opsional)</label>
                        <button type="button"
                                @click="editCategoryDropdownOpen = !editCategoryDropdownOpen; closeAllDropdowns(); editCategoryDropdownOpen = true"
                                class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                            <span class="flex items-center gap-2 truncate">
                                <span class="w-2 h-2 rounded-full" :class="editTicket.error_category ? 'bg-purple-500' : 'bg-gray-300 dark:bg-gray-600'"></span>
                                <span x-text="editTicket.error_category ? (errorCategoryMap[editTicket.error_category] || editTicket.error_category) : '— Pilih Kategori —'"
                                      :class="editTicket.error_category ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-400'"></span>
                            </span>
                            <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="editCategoryDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        {{-- Category Dropdown Popover --}}
                        <div x-show="editCategoryDropdownOpen" @click.outside="editCategoryDropdownOpen = false" x-cloak
                             class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5 max-h-56 overflow-y-auto">
                            <button type="button"
                                    @click="editTicket.error_category = ''; editCategoryDropdownOpen = false"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-500">
                                <span>— Tanpa Kategori Error —</span>
                                <svg x-show="editTicket.error_category === ''" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                            @foreach($errorCategories as $cKey => $cInfo)
                                <button type="button"
                                        @click="editTicket.error_category = '{{ $cKey }}'; editCategoryDropdownOpen = false"
                                        class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                        :class="editTicket.error_category === '{{ $cKey }}' ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                    <span class="truncate">{{ $cInfo['label'] }}</span>
                                    <svg x-show="editTicket.error_category === '{{ $cKey }}'" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                    </svg>
                                </button>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Milestone Custom Popover --}}
                <div class="space-y-1.5 relative">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Milestone (Opsional)</label>
                    <button type="button"
                            @click="editMilestoneDropdownOpen = !editMilestoneDropdownOpen; closeAllDropdowns(); editMilestoneDropdownOpen = true"
                            class="w-full text-left px-3.5 py-2.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs sm:text-sm font-medium text-gray-800 dark:text-gray-200 flex items-center justify-between transition cursor-pointer">
                        <span class="flex items-center gap-2 truncate">
                            <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 0 1 2-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 0 0-2 2Zm9-13.5V9" />
                            </svg>
                            <span x-text="editTicket.milestone_id ? ((allMilestones.find(m => m.id === editTicket.milestone_id) || {}).title || 'Milestone Terpilih') : '— Tanpa Milestone —'"
                                  :class="editTicket.milestone_id ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-400'"></span>
                        </span>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="editMilestoneDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </button>

                    {{-- Milestone Dropdown Popover --}}
                    <div x-show="editMilestoneDropdownOpen" @click.outside="editMilestoneDropdownOpen = false" x-cloak
                         class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-1.5 z-50 space-y-0.5 max-h-56 overflow-y-auto">
                        <button type="button"
                                @click="editTicket.milestone_id = ''; editMilestoneDropdownOpen = false"
                                class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60 text-gray-500">
                            <span>— Tanpa Milestone —</span>
                            <svg x-show="editTicket.milestone_id === ''" class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                        </button>
                        <template x-for="m in allMilestones" :key="m.id">
                            <button type="button"
                                    @click="editTicket.milestone_id = m.id; editMilestoneDropdownOpen = false"
                                    class="w-full text-left px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                    :class="editTicket.milestone_id === m.id ? 'bg-blue-50 dark:bg-blue-950/50 font-bold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-200'">
                                <span class="truncate" x-text="m.title"></span>
                                <svg x-show="editTicket.milestone_id === m.id" class="w-4 h-4 text-blue-600 dark:text-blue-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                                </svg>
                            </button>
                        </template>
                    </div>
                </div>

                {{-- Tugaskan ke Anggota Tim: Searchable Multi-Select (Members Only, No Admin/Client) --}}
                <div class="space-y-1.5 relative">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 flex items-center justify-between">
                        <span>Tugaskan ke Anggota Tim (Hanya Member)</span>
                        <span class="text-[11px] text-gray-400 font-normal">Bisa pilih lebih dari 1</span>
                    </label>

                    {{-- Trigger Button showing Chips or Placeholder --}}
                    <div @click="editAssigneeDropdownOpen = !editAssigneeDropdownOpen; closeAllDropdowns(); editAssigneeDropdownOpen = true"
                         class="w-full min-h-[42px] px-3 py-1.5 bg-gray-50/60 dark:bg-gray-900/60 hover:bg-white dark:hover:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl text-xs flex items-center justify-between gap-2 transition cursor-pointer">
                        <div class="flex flex-wrap items-center gap-1.5 flex-1 min-w-0 py-0.5">
                            <template x-if="editTicket.assignee_ids.length === 0">
                                <span class="text-gray-400 text-xs sm:text-sm pl-1">— Pilih Petugas (Member Tim) —</span>
                            </template>
                            <template x-for="user in selectedEditAssigneeObjects" :key="user.id">
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300 font-medium text-xs">
                                    <template x-if="user.avatar">
                                        <img :src="user.avatar" class="w-4 h-4 rounded-full object-cover">
                                    </template>
                                    <template x-if="!user.avatar">
                                        <span class="w-4 h-4 rounded-full text-white text-[9px] font-bold flex items-center justify-center shrink-0"
                                              :style="'background-color: ' + user.color" x-text="user.initials"></span>
                                    </template>
                                    <span x-text="user.name" class="max-w-[120px] truncate"></span>
                                    <button type="button" @click.stop="removeEditAssignee(user.id)" class="text-blue-400 hover:text-blue-600 transition">
                                        ✕
                                    </button>
                                </span>
                            </template>
                        </div>
                        <svg class="w-4 h-4 text-gray-400 transition-transform duration-150 shrink-0" :class="editAssigneeDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>

                    {{-- Searchable Multi-Select Popover --}}
                    <div x-show="editAssigneeDropdownOpen" @click.outside="editAssigneeDropdownOpen = false" x-cloak
                         class="absolute left-0 top-full mt-1 w-full bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-2xl shadow-xl p-2 z-50 space-y-2">
                        {{-- Search Input inside Popover --}}
                        <div class="relative">
                            <svg class="w-3.5 h-3.5 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                            </svg>
                            <input type="text" x-model="editAssigneeSearch" placeholder="Cari nama atau email member..."
                                   class="w-full pl-8.5 pr-3 py-1.5 text-xs bg-gray-50 dark:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-blue-500/20 text-gray-900 dark:text-white placeholder-gray-400">
                        </div>

                        {{-- Member list with checkboxes --}}
                        <div class="max-h-52 overflow-y-auto space-y-1 pr-1">
                            <template x-for="user in filteredEditAssignees" :key="user.id">
                                <div @click="toggleEditAssignee(user.id)"
                                     class="px-3 py-2 rounded-xl text-xs flex items-center justify-between transition cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-700/60"
                                     :class="editTicket.assignee_ids.includes(user.id) ? 'bg-blue-50/70 dark:bg-blue-950/40 text-blue-700 dark:text-blue-300 font-semibold' : 'text-gray-700 dark:text-gray-200'">
                                    <div class="flex items-center gap-2.5 min-w-0">
                                        <template x-if="user.avatar">
                                            <img :src="user.avatar" class="w-6 h-6 rounded-full object-cover shrink-0">
                                        </template>
                                        <template x-if="!user.avatar">
                                            <div class="w-6 h-6 rounded-full text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                                 :style="'background-color: ' + user.color" x-text="user.initials"></div>
                                        </template>
                                        <div class="min-w-0">
                                            <span class="block truncate" x-text="user.name"></span>
                                            <span class="text-[10px] text-gray-400 block truncate font-normal" x-text="user.email"></span>
                                        </div>
                                    </div>
                                    <div class="w-4 h-4 rounded-md border flex items-center justify-center transition shrink-0 ml-2"
                                         :class="editTicket.assignee_ids.includes(user.id) ? 'bg-blue-600 border-blue-600 text-white' : 'border-gray-300 dark:border-gray-600'">
                                        <svg x-show="editTicket.assignee_ids.includes(user.id)" class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </div>
                                </div>
                            </template>
                            <template x-if="filteredEditAssignees.length === 0">
                                <div class="px-3 py-4 text-center text-xs text-gray-400">
                                    Tidak ada member yang cocok
                                </div>
                            </template>
                        </div>

                        {{-- Popover Footer with selection count and done button --}}
                        <div class="pt-2 border-t border-gray-100 dark:border-gray-700 flex items-center justify-between text-[11px] text-gray-400">
                            <span x-text="editTicket.assignee_ids.length + ' member dipilih'"></span>
                            <button type="button" @click="editAssigneeDropdownOpen = false"
                                    class="px-2.5 py-1 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-700 dark:text-gray-200 font-semibold rounded-lg transition">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Lampiran Tambahan (Optional) --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Tambah Lampiran Baru (Maks. 5 file)</label>
                    <div class="p-3.5 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-2xl bg-gray-50/40 dark:bg-gray-900/40 hover:bg-gray-50 dark:hover:bg-gray-900/70 transition">
                        <input type="file" name="attachments[]" multiple class="block w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                        <p class="text-[11px] text-gray-400 mt-1">PNG, JPG, PDF, ZIP, maksimal 10MB per file.</p>
                    </div>
                </div>

                {{-- Modal Actions --}}
                <div class="pt-4 border-t border-gray-100 dark:border-gray-800 flex items-center justify-end gap-2.5 shrink-0">
                    <button type="button" @click="showEditTicketModal = false"
                            class="px-4 py-2 text-xs sm:text-sm font-semibold text-gray-600 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200 cursor-pointer transition">
                        Batal
                    </button>
                    <button type="submit" :disabled="editSubmitting"
                            class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs sm:text-sm font-bold rounded-xl shadow-xs transition disabled:opacity-50 flex items-center gap-2 cursor-pointer">
                        <svg x-show="editSubmitting" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span x-text="editSubmitting ? 'Menyimpan...' : 'Perbarui Tiket'">Perbarui Tiket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
    </template>

    {{-- ============================================================
         TICKET DETAIL MODAL POP-UP (Modern, Comprehensive & Responsive)
    ============================================================ --}}
    <template x-teleport="body">
        <div x-show="showDetailTicketModal" x-cloak
             class="fixed inset-0 z-[100] w-screen h-screen flex items-center justify-center p-3 sm:p-5 bg-slate-900/60 backdrop-blur-xs overflow-y-auto"
             @keydown.escape.window="closeDetailModal()"
             @click.self="closeDetailModal()">

            {{-- Modal Dialog Card --}}
            <div class="relative w-full max-w-5xl bg-white dark:bg-gray-850 rounded-3xl shadow-2xl border border-gray-100 dark:border-gray-800 overflow-hidden transform transition-all max-h-[92vh] flex flex-col"
                 @click.stop>

            {{-- Modal Top Bar / Header --}}
            <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800 flex items-center justify-between gap-3 bg-white dark:bg-gray-850 shrink-0">
                <div class="flex items-center gap-2.5 flex-wrap min-w-0">
                    {{-- Ticket ID pill --}}
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 font-mono text-xs font-bold shrink-0">
                        <span>#TKT-<span x-text="detailTicket ? String(detailTicket.id).padStart(4, '0') : ''"></span></span>
                    </div>

                    {{-- Type Badge --}}
                    <template x-if="detailTicket">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg text-[10px] font-bold uppercase tracking-wider shrink-0"
                              :class="detailTicket.type_badge"
                              x-text="detailTicket.type_label">
                        </span>
                    </template>

                    {{-- Priority Badge --}}
                    <template x-if="detailTicket">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold shrink-0"
                              :class="detailTicket.priority_badge">
                            <span class="w-1.5 h-1.5 rounded-full" :class="detailTicket.priority_dot"></span>
                            <span x-text="detailTicket.priority_label"></span>
                        </span>
                    </template>

                    {{-- Status Badge --}}
                    <template x-if="detailTicket">
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-lg text-xs font-semibold shrink-0"
                              :class="detailTicket.status_badge">
                            <span class="w-1.5 h-1.5 rounded-full" :class="detailTicket.status_dot"></span>
                            <span x-text="detailTicket.status_label"></span>
                        </span>
                    </template>

                    {{-- SLA Breached Badge --}}
                    <template x-if="detailTicket && detailTicket.sla_breached">
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-[10px] font-bold bg-rose-100 text-rose-700 dark:bg-rose-950/80 dark:text-rose-300 shrink-0">
                            <svg class="w-3 h-3 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" /></svg>
                            SLA BREACHED
                        </span>
                    </template>
                </div>

                {{-- Header Actions: Edit Button + Close Button --}}
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button" @click="openEditFromDetail()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:hover:bg-amber-900/50 dark:text-amber-400 text-xs font-semibold rounded-xl transition cursor-pointer">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        <span>Edit Tiket</span>
                    </button>

                    <button type="button" @click="closeDetailModal()"
                            class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 p-1.5 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-800 transition cursor-pointer">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>
            </div>

            {{-- Modal Body: 2-Column Responsive Layout --}}
            <div class="flex-1 overflow-y-auto p-6 flex flex-col lg:flex-row gap-6">

                {{-- Left / Main Content (col-span-8 or flex-1) --}}
                <div class="flex-1 min-w-0 space-y-5">
                    {{-- Ticket Title --}}
                    <div>
                        <h2 class="text-xl sm:text-2xl font-bold text-gray-900 dark:text-white leading-tight" x-text="detailTicket?.title"></h2>
                        <div class="flex items-center gap-3 text-xs text-gray-400 mt-2 flex-wrap">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                                <span>Dilaporkan oleh: <strong class="text-gray-700 dark:text-gray-300 font-semibold" x-text="detailTicket?.reporter?.name || 'User'"></strong></span>
                            </span>
                            <span>•</span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                <span x-text="detailTicket?.created_at_diff ? (detailTicket.created_at_diff + ' (' + detailTicket.created_at + ')') : detailTicket?.created_at"></span>
                            </span>
                            <template x-if="detailTicket && detailTicket.milestone_title">
                                <span class="flex items-center gap-1 px-2 py-0.5 rounded-lg bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 font-medium">
                                    <svg class="w-3 h-3 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 21v-4m0 0V5a2 2 0 0 1 2-2h6.5l1 1H21l-3 6 3 6h-8.5l-1-1H5a2 2 0 0 0-2 2Zm9-13.5V9" /></svg>
                                    <span x-text="detailTicket.milestone_title"></span>
                                </span>
                            </template>
                        </div>
                    </div>

                    {{-- Tabs Switcher: Detail & Solusi, Komentar, Riwayat --}}
                    <div class="flex items-center gap-1 border-b border-gray-100 dark:border-gray-800 pb-2">
                        <button type="button" @click="activeDetailTab = 'details'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                                :class="activeDetailTab === 'details' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <span>Detail & Solusi</span>
                        </button>

                        <button type="button" @click="activeDetailTab = 'comments'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                                :class="activeDetailTab === 'comments' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.767-.932 6.002 6.002 0 0 0 1.054-2.85A8.19 8.19 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
                            </svg>
                            <span>Komentar & Diskusi</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="activeDetailTab === 'comments' ? 'bg-blue-200/80 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                  x-text="detailTicket?.comments ? detailTicket.comments.length : 0">
                            </span>
                        </button>

                        <button type="button" @click="activeDetailTab = 'history'"
                                class="px-4 py-2 rounded-xl text-xs font-bold transition flex items-center gap-2 cursor-pointer"
                                :class="activeDetailTab === 'history' ? 'bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400' : 'text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-200'">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Riwayat Aktivitas</span>
                            <span class="px-1.5 py-0.5 rounded-full text-[10px] font-bold"
                                  :class="activeDetailTab === 'history' ? 'bg-blue-200/80 text-blue-800 dark:bg-blue-900 dark:text-blue-200' : 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400'"
                                  x-text="detailTicket?.histories ? detailTicket.histories.length : 0">
                            </span>
                        </button>
                    </div>

                    {{-- TAB 1: Detail & Solusi --}}
                    <div x-show="activeDetailTab === 'details'" class="space-y-5">
                        {{-- Deskripsi Masalah Card --}}
                        <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-5 border border-gray-100 dark:border-gray-800">
                            <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2.5">Deskripsi Masalah</h4>
                            <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-200 leading-relaxed whitespace-pre-line"
                                 x-text="detailTicket?.description || 'Tidak ada deskripsi.'"></div>
                        </div>

                        {{-- Kategori Error & Solusi --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-4 flex items-center justify-between">
                                <span>Kategori Error & Solusi</span>
                                <template x-if="detailTicket && detailTicket.error_category">
                                    <span class="text-xs normal-case px-2.5 py-0.5 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 font-semibold"
                                          x-text="detailTicket.error_category_label"></span>
                                </template>
                            </h4>

                            @if(auth()->user()->hasRole(['admin', 'member']))
                                <form method="POST" :action="detailTicket?.urls.details" enctype="multipart/form-data" class="space-y-4">
                                    @csrf
                                    <input type="hidden" name="redirect_to" :value="window.location.href">

                                    {{-- Kategori Error select --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Kategori Error</label>
                                        <select name="error_category"
                                                class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                            <option value="">— Pilih Kategori Error —</option>
                                            @foreach($errorCategories as $ecKey => $ecData)
                                                <option value="{{ $ecKey }}" :selected="detailTicket?.error_category === '{{ $ecKey }}'">
                                                    {{ $ecData['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    {{-- Solusi textarea --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Solusi / Catatan Perbaikan Teknis</label>
                                        <textarea name="solution" rows="3" placeholder="Jelaskan akar penyebab masalah dan solusi/patch yang telah diterapkan..."
                                                  class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 resize-none"
                                                  :value="detailTicket?.solution"></textarea>
                                    </div>

                                    {{-- Tambah Lampiran Baru --}}
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300 mb-1.5">Tambah File Lampiran</label>
                                        <input type="file" name="attachments[]" multiple
                                               class="w-full text-xs text-gray-500 file:mr-3 file:py-1.5 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                                    </div>

                                    <div class="flex justify-end pt-1">
                                        <button type="submit"
                                                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                                            Simpan Solusi & Lampiran
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="space-y-3 text-xs sm:text-sm">
                                    <div>
                                        <span class="text-gray-400 block text-xs mb-0.5">Kategori:</span>
                                        <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="detailTicket?.error_category_label || '—'"></span>
                                    </div>
                                    <div>
                                        <span class="text-gray-400 block text-xs mb-0.5">Solusi:</span>
                                        <div class="p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl text-gray-700 dark:text-gray-300 whitespace-pre-line"
                                             x-text="detailTicket?.solution || 'Belum ada catatan solusi.'"></div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Lampiran List --}}
                        <div class="bg-white dark:bg-gray-800 rounded-2xl p-5 border border-gray-100 dark:border-gray-700/60">
                            <h4 class="text-xs font-bold text-gray-900 dark:text-white uppercase tracking-wider mb-3 flex items-center justify-between">
                                <span>Lampiran File</span>
                                <span class="text-xs text-gray-400 font-normal" x-text="(detailTicket?.attachments ? detailTicket.attachments.length : 0) + ' file'"></span>
                            </h4>

                            <template x-if="detailTicket && detailTicket.attachments && detailTicket.attachments.length > 0">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                    <template x-for="att in detailTicket.attachments" :key="att.id">
                                        <div class="flex items-center justify-between gap-3 p-3 bg-gray-50 dark:bg-gray-900/60 rounded-xl border border-gray-100 dark:border-gray-750">
                                            <div class="flex items-center gap-2.5 min-w-0">
                                                <div class="w-8 h-8 rounded-lg bg-blue-50 dark:bg-blue-950/60 text-blue-600 dark:text-blue-400 flex items-center justify-center shrink-0">
                                                    <template x-if="att.is_image">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                                                    </template>
                                                    <template x-if="!att.is_image">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                                    </template>
                                                </div>
                                                <div class="min-w-0">
                                                    <a :href="att.url" target="_blank"
                                                       class="text-xs font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 block truncate"
                                                       x-text="att.file_name"></a>
                                                    <span class="text-[10px] text-gray-400 block" x-text="att.file_size + ' • oleh ' + att.uploader_name"></span>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-1 shrink-0">
                                                <a :href="att.url" target="_blank" download
                                                   class="p-1.5 text-gray-400 hover:text-blue-600 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-800 transition" title="Unduh">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                                </a>
                                                @if(auth()->user()->hasRole(['admin', 'member']))
                                                    <form method="POST" :action="att.delete_url"
                                                          @submit="return confirm('Hapus lampiran ini?')">
                                                        @csrf @method('DELETE')
                                                        <input type="hidden" name="redirect_to" :value="window.location.href">
                                                        <button type="submit" class="p-1.5 text-gray-400 hover:text-rose-600 rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/50 transition cursor-pointer" title="Hapus">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!detailTicket || !detailTicket.attachments || detailTicket.attachments.length === 0">
                                <div class="py-6 text-center text-xs text-gray-400">
                                    Belum ada lampiran file untuk tiket ini.
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- TAB 2: Komentar & Diskusi --}}
                    <div x-show="activeDetailTab === 'comments'" class="space-y-4">
                        {{-- Comments List --}}
                        <div class="space-y-3 max-h-96 overflow-y-auto pr-1">
                            <template x-if="detailTicket && detailTicket.comments && detailTicket.comments.length > 0">
                                <div class="space-y-3">
                                    <template x-for="c in detailTicket.comments" :key="c.id">
                                        <div class="flex items-start gap-3 p-4 bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl border border-gray-100 dark:border-gray-800">
                                            <template x-if="c.user && c.user.avatar">
                                                <img :src="c.user.avatar" class="w-8 h-8 rounded-full object-cover shrink-0 mt-0.5">
                                            </template>
                                            <template x-if="!c.user || !c.user.avatar">
                                                <div class="w-8 h-8 rounded-full text-white text-xs font-bold flex items-center justify-center shrink-0 mt-0.5"
                                                     :style="'background-color: ' + (c.user ? c.user.color : '#6366f1')"
                                                     x-text="c.user ? c.user.initials : 'U'"></div>
                                            </template>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-center justify-between gap-2 mb-1">
                                                    <span class="text-xs font-bold text-gray-900 dark:text-white" x-text="c.user ? c.user.name : 'User'"></span>
                                                    <span class="text-[10px] text-gray-400" x-text="c.created_at_diff || c.created_at"></span>
                                                </div>
                                                <div class="text-xs sm:text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line leading-relaxed"
                                                     x-text="c.body"></div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <template x-if="!detailTicket || !detailTicket.comments || detailTicket.comments.length === 0">
                                <div class="py-10 text-center">
                                    <div class="w-10 h-10 rounded-xl bg-gray-100 dark:bg-gray-800 text-gray-400 flex items-center justify-center mx-auto mb-2">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a.75.75 0 0 1-.767-.932 6.002 6.002 0 0 0 1.054-2.85A8.19 8.19 0 0 1 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                                    </div>
                                    <p class="text-xs text-gray-400">Belum ada komentar atau diskusi untuk tiket ini.</p>
                                </div>
                            </template>
                        </div>

                        {{-- New Comment Form --}}
                        <form method="POST" :action="detailTicket?.urls.comment" class="space-y-3 pt-2">
                            @csrf
                            <input type="hidden" name="redirect_to" :value="window.location.href">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold text-gray-700 dark:text-gray-300">Tambah Komentar</label>
                                <textarea name="body" rows="3" required placeholder="Tulis komentar, update status kerja, atau masukan..."
                                          class="w-full text-xs sm:text-sm px-3.5 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-gray-50/60 dark:bg-gray-900/60 text-gray-900 dark:text-white placeholder-gray-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500/20 focus:border-blue-500 transition resize-none"></textarea>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit"
                                        class="px-5 py-2.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white text-xs font-bold rounded-xl transition cursor-pointer flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                    <span>Kirim Komentar</span>
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- TAB 3: Riwayat Aktivitas --}}
                    <div x-show="activeDetailTab === 'history'" class="space-y-3">
                        <template x-if="detailTicket && detailTicket.histories && detailTicket.histories.length > 0">
                            <div class="space-y-2 max-h-96 overflow-y-auto pr-1">
                                <template x-for="h in detailTicket.histories" :key="h.id">
                                    <div class="flex items-start gap-3 p-3 bg-gray-50/60 dark:bg-gray-900/40 rounded-xl text-xs border border-gray-100 dark:border-gray-800">
                                        <div class="w-2 h-2 rounded-full bg-blue-500 mt-1.5 shrink-0"></div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-1.5 flex-wrap">
                                                <strong class="text-gray-900 dark:text-white" x-text="h.actor_name"></strong>
                                                <span class="text-gray-400">mengubah</span>
                                                <span class="font-mono bg-gray-100 dark:bg-gray-800 px-1.5 py-0.5 rounded text-[11px] text-gray-700 dark:text-gray-300 font-semibold" x-text="h.field_changed"></span>
                                                <template x-if="h.old_value">
                                                    <span class="text-gray-400">dari <span class="line-through text-gray-500" x-text="h.old_value"></span></span>
                                                </template>
                                                <span class="text-gray-400">menjadi</span>
                                                <strong class="text-blue-600 dark:text-blue-400" x-text="h.new_value"></strong>
                                            </div>
                                            <template x-if="h.description">
                                                <p class="text-gray-500 dark:text-gray-400 mt-1" x-text="h.description"></p>
                                            </template>
                                        </div>
                                        <span class="text-[10px] text-gray-400 shrink-0 ml-2" x-text="h.created_at"></span>
                                    </div>
                                </template>
                            </div>
                        </template>

                        <template x-if="!detailTicket || !detailTicket.histories || detailTicket.histories.length === 0">
                            <div class="py-10 text-center text-xs text-gray-400">
                                Belum ada riwayat aktivitas yang tercatat untuk tiket ini.
                            </div>
                        </template>
                    </div>

                </div>

                {{-- Right / Sidebar Content (w-full lg:w-80 shrink-0) --}}
                <div class="w-full lg:w-80 shrink-0 space-y-4">

                    {{-- Quick Update Status (for admin and member) --}}
                    @if(auth()->user()->hasRole(['admin', 'member']))
                        <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-4.5 border border-gray-100 dark:border-gray-800 space-y-3">
                            <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status Tiket</h4>
                            <form method="POST" :action="detailTicket?.urls.update" class="space-y-3">
                                @csrf @method('PUT')
                                <input type="hidden" name="redirect_to" :value="window.location.href">
                                <select name="status"
                                        class="w-full text-xs sm:text-sm font-semibold px-3 py-2.5 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500/20">
                                    @foreach($statusMeta as $stKey => $stInfo)
                                        <option value="{{ $stKey }}" :selected="detailTicket?.status === '{{ $stKey }}'">
                                            {{ $stInfo['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="submit"
                                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition cursor-pointer shadow-xs">
                                    Simpan Status
                                </button>
                            </form>
                        </div>
                    @endif

                    {{-- SLA Countdown Card --}}
                    <template x-if="detailTicket && detailTicket.sla_due_at">
                        <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-4.5 border border-gray-100 dark:border-gray-800 space-y-2.5">
                            <div class="flex items-center justify-between">
                                <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">SLA Target</h4>
                                <span class="text-xs font-bold"
                                      :class="detailTicket.sla_breached ? 'text-rose-600' : 'text-gray-700 dark:text-gray-300'"
                                      x-text="detailTicket.sla_breached ? 'Terlewat' : ('Sisa: ' + detailTicket.sla_remaining_text)">
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-750 rounded-full h-2 overflow-hidden">
                                <div class="h-2 rounded-full transition-all"
                                     :class="{
                                         'bg-rose-500': detailTicket.sla_percent >= 100 || detailTicket.sla_breached,
                                         'bg-amber-400': detailTicket.sla_percent >= 75 && detailTicket.sla_percent < 100 && !detailTicket.sla_breached,
                                         'bg-emerald-500': detailTicket.sla_percent < 75 && !detailTicket.sla_breached
                                     }"
                                     :style="'width: ' + detailTicket.sla_percent + '%'"></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-gray-400">
                                <span>Terpakai: <strong class="text-gray-600 dark:text-gray-300 font-semibold" x-text="detailTicket.sla_percent + '%'"></strong></span>
                                <span x-text="'Batas: ' + detailTicket.sla_due_at"></span>
                            </div>
                        </div>
                    </template>

                    {{-- Google Meet Card --}}
                    <template x-if="detailTicket && (detailTicket.google_meet_link || detailTicket.can_create_meet)">
                        <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-4.5 border border-gray-100 dark:border-gray-800 space-y-2">
                            <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Google Meet</h4>
                            <template x-if="detailTicket.google_meet_link">
                                <a :href="detailTicket.google_meet_link" target="_blank" rel="noopener"
                                   class="w-full inline-flex items-center justify-center gap-2 py-2 px-3 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-xl transition shadow-xs">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M4.5 18.75h9a2.25 2.25 0 0 0 2.25-2.25v-9a2.25 2.25 0 0 0-2.25-2.25h-9A2.25 2.25 0 0 0 2.25 7.5v9a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                                    <span>Join Google Meet</span>
                                </a>
                            </template>
                            <template x-if="!detailTicket.google_meet_link && detailTicket.can_create_meet">
                                <form method="POST" :action="detailTicket.urls.meeting">
                                    @csrf
                                    <input type="hidden" name="redirect_to" :value="window.location.href">
                                    <button type="submit"
                                            class="w-full inline-flex items-center justify-center gap-2 py-2 px-3 bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold rounded-xl transition shadow-xs cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        <span>Buat Google Meet</span>
                                    </button>
                                </form>
                            </template>
                        </div>
                    </template>

                    {{-- Petugas / Assignees Card --}}
                    <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-4.5 border border-gray-100 dark:border-gray-800 space-y-3">
                        <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Petugas (Assignee)</h4>
                        <template x-if="detailTicket && detailTicket.assignees && detailTicket.assignees.length > 0">
                            <div class="space-y-2">
                                <template x-for="u in detailTicket.assignees" :key="u.id">
                                    <div class="flex items-center gap-2.5">
                                        <template x-if="u.avatar">
                                            <img :src="u.avatar" class="w-7 h-7 rounded-full object-cover shrink-0">
                                        </template>
                                        <template x-if="!u.avatar">
                                            <div class="w-7 h-7 rounded-full text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                                 :style="'background-color: ' + u.color" x-text="u.initials"></div>
                                        </template>
                                        <div class="min-w-0">
                                            <span class="text-xs font-semibold text-gray-900 dark:text-white block truncate" x-text="u.name"></span>
                                            <span class="text-[10px] text-gray-400 block truncate" x-text="u.email"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </template>
                        <template x-if="!detailTicket || !detailTicket.assignees || detailTicket.assignees.length === 0">
                            <p class="text-xs text-gray-400 italic">Belum ditugaskan (Unassigned)</p>
                        </template>
                    </div>

                    {{-- Pelapor / Reporter Card --}}
                    <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-4.5 border border-gray-100 dark:border-gray-800 space-y-2">
                        <h4 class="text-xs font-bold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Pelapor</h4>
                        <template x-if="detailTicket && detailTicket.reporter">
                            <div class="flex items-center gap-2.5">
                                <template x-if="detailTicket.reporter.avatar">
                                    <img :src="detailTicket.reporter.avatar" class="w-7 h-7 rounded-full object-cover shrink-0">
                                </template>
                                <template x-if="!detailTicket.reporter.avatar">
                                    <div class="w-7 h-7 rounded-full text-white text-[10px] font-bold flex items-center justify-center shrink-0"
                                         :style="'background-color: ' + detailTicket.reporter.color" x-text="detailTicket.reporter.initials"></div>
                                </template>
                                <div class="min-w-0">
                                    <span class="text-xs font-semibold text-gray-900 dark:text-white block truncate" x-text="detailTicket.reporter.name"></span>
                                    <span class="text-[10px] text-gray-400 block truncate" x-text="detailTicket.reporter.email"></span>
                                </div>
                            </div>
                        </template>
                    </div>

                    {{-- Metadata info --}}
                    <div class="bg-gray-50/70 dark:bg-gray-900/60 rounded-2xl p-4.5 border border-gray-100 dark:border-gray-800 space-y-2 text-xs">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Proyek:</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200 truncate max-w-[150px]">{{ $project->name }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Milestone:</span>
                            <span class="font-semibold text-gray-800 dark:text-gray-200" x-text="detailTicket?.milestone_title || '—'"></span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-400">Dibuat:</span>
                            <span class="text-gray-700 dark:text-gray-300" x-text="detailTicket?.created_at"></span>
                        </div>
                        <template x-if="detailTicket && detailTicket.resolved_at">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Resolved:</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold" x-text="detailTicket.resolved_at"></span>
                            </div>
                        </template>
                        <template x-if="detailTicket && detailTicket.closed_at">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-400">Closed:</span>
                                <span class="text-gray-500 font-semibold" x-text="detailTicket.closed_at"></span>
                            </div>
                        </template>
                    </div>

                    {{-- Reopen Ticket (if closed and client) --}}
                    @if(auth()->user()->hasRole('client'))
                        <template x-if="detailTicket && detailTicket.status === 'closed'">
                            <div class="bg-rose-50/60 dark:bg-rose-950/40 rounded-2xl p-4 border border-rose-200/60 dark:border-rose-900/60 space-y-2">
                                <h4 class="text-xs font-bold text-rose-700 dark:text-rose-400">Buka Kembali Tiket?</h4>
                                <form method="POST" :action="detailTicket.urls.reopen" class="space-y-2">
                                    @csrf @method('PUT')
                                    <input type="hidden" name="redirect_to" :value="window.location.href">
                                    <textarea name="reason" rows="2" placeholder="Alasan reopen tiket..." required
                                              class="w-full text-xs px-3 py-2 rounded-xl border border-rose-200 dark:border-rose-800 bg-white dark:bg-gray-850 text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-rose-500/20"></textarea>
                                    <button type="submit"
                                            class="w-full py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold rounded-xl transition cursor-pointer">
                                        Reopen Tiket
                                    </button>
                                </form>
                            </div>
                        </template>
                    @endif

                </div>

            </div>

        </div>
    </div>
    </template>

</div>
