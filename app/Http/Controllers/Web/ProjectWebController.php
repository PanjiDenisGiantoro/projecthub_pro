<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Concerns\HasPerPage;
use App\Http\Controllers\Controller;
use App\Models\BoardColumnTemplate;
use App\Models\Project;
use App\Models\ProjectMember;
use App\Models\TimeLog;
use App\Models\User;
use App\Services\GoogleCalendarService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProjectWebController extends Controller
{
    use HasPerPage;

    public function __construct(private NotificationService $notifier) {}

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Project::with([
            'client.organizationUnit',
            'client.company',
            'manager.structuralLevel',
            'manager.roles',
        ])
        ->withCount([
            'tasks as total_tasks_count',
            'tasks as completed_tasks_count' => function ($q) {
                $q->where(function ($sub) {
                    $sub->where('status', 'done')
                        ->orWhereHas('boardColumn', fn ($bc) => $bc->where('is_done', true));
                });
            },
        ]);

        if ($user->hasRole('client')) {
            $query->where('client_id', $user->id);
        } elseif (! $user->is_super_admin) {
            // Selain super admin (termasuk role 'admin'), hanya melihat proyek
            // yang ia pimpin atau ia menjadi anggota timnya — selaras dengan
            // ProjectPolicy::view().
            $query->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhereHas('members', fn ($m) => $m->where('user_id', $user->id));
            });
        }

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
                $cleanId = preg_replace('/[^0-9]/', '', $search);
                if (!empty($cleanId)) {
                    $q->orWhere('id', (int) $cleanId);
                }
            });
        }

        // Status filter
        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'overdue') {
                $query->whereNotIn('status', ['completed', 'cancelled'])
                      ->whereNotNull('end_date')
                      ->whereDate('end_date', '<', now()->toDateString());
            } elseif ($status === 'in_progress') {
                $query->where('status', 'active');
            } elseif ($status === 'pending') {
                $query->whereIn('status', ['on_hold', 'draft']);
            } else {
                $query->where('status', $status);
            }
        }

        // PIC / Manager filter
        if ($request->filled('manager_id')) {
            $query->where('manager_id', $request->manager_id);
        }

        // Client filter
        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        // Deadline filter
        if ($request->filled('deadline')) {
            $deadline = $request->deadline;
            if ($deadline === 'today') {
                $query->whereDate('end_date', now()->toDateString());
            } elseif ($deadline === 'this_week') {
                $query->whereBetween('end_date', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
            } elseif ($deadline === 'this_month') {
                $query->whereBetween('end_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()]);
            } elseif ($deadline === 'overdue') {
                $query->whereNotIn('status', ['completed', 'cancelled'])
                      ->whereNotNull('end_date')
                      ->whereDate('end_date', '<', now()->toDateString());
            }
        }

        // Summary KPI stats (tenant and role scoped)
        $statsBase = Project::query();
        if ($user->hasRole('client')) {
            $statsBase->where('client_id', $user->id);
        }
        $stats = [
            'total'       => (clone $statsBase)->count(),
            'completed'   => (clone $statsBase)->where('status', 'completed')->count(),
            'in_progress' => (clone $statsBase)->where('status', 'active')->count(),
            'pending'     => (clone $statsBase)->whereIn('status', ['on_hold', 'draft'])->count(),
            'overdue'     => (clone $statsBase)->whereNotIn('status', ['completed', 'cancelled'])
                                               ->whereNotNull('end_date')
                                               ->whereDate('end_date', '<', now()->toDateString())
                                               ->count(),
        ];

        // Filter dropdown options
        $companyId = $user->company_id;
        $clientsQuery = User::role('client')->where('is_active', true);
        $managersQuery = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))->where('is_active', true);
        if ($companyId) {
            $clientsQuery->where('company_id', $companyId);
            $managersQuery->where('company_id', $companyId);
        }
        $clients = $clientsQuery->orderBy('name')->get(['id', 'name']);
        $managers = $managersQuery->orderBy('name')->get(['id', 'name']);

        $perPage = $this->perPage($request, 6, 'per_page', [6, 9, 12, 24, 48, 100]);
        $projects = $query->latest()->paginate($perPage)->withQueryString();

        return view('projects.index', compact('projects', 'stats', 'clients', 'managers'));
    }

    public function create()
    {
        return redirect()->route('projects.index', ['create' => 1]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'client_id' => 'nullable|exists:users,id',
            'manager_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,active,on_hold,completed,cancelled',
            'images' => 'nullable|array|max:'.Project::MAX_IMAGES,
            'images.*' => 'image|max:2048|mimes:jpg,jpeg,png,gif,webp',
        ]);

        $project = Project::create([
            ...$request->only('name', 'description', 'client_id', 'manager_id', 'start_date', 'end_date', 'budget'),
            'status' => $request->input('status', 'active'),
            'images' => $this->storeProjectImages($request),
        ]);

        BoardColumnTemplate::default()?->applyTo($project);

        $this->notifier->notifyManagers(
            'new_project',
            'Proyek Baru Dibuat',
            "Proyek \"{$project->name}\" baru saja dibuat oleh ".auth()->user()->name.'.',
            ['project_id' => $project->id],
            push: true,
            companyId: $project->company_id
        );

        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('projects.index')->with('success', 'Proyek berhasil dibuat.');
        }

        return redirect()->route('projects.show', $project)->with('success', 'Proyek berhasil dibuat.');
    }

    /**
     * Tab halaman detail project: slug URL (/projects/{id}/{slug}) => key tab di view.
     * Tiap tab dirender & di-query terpisah supaya buka satu tab tidak ikut memuat
     * data semua tab lain.
     */
    public const TABS = [
        'overview'       => 'overview',
        'timesheet'      => 'timesheet',
        'tasks'          => 'tasks',
        'sprints'        => 'sprints',
        'milestones'     => 'milestones',
        'team'           => 'team',
        'tickets'        => 'tickets',
        'files'          => 'files',
        'knowledge-base' => 'kb',
        'portal'         => 'portal',
        'budget'         => 'budget',
        'recurring'      => 'recurring',
        'notifications'  => 'notif',
        'chat'           => 'chat',
    ];

    public static function tabSlug(string $key): string
    {
        return array_search($key, self::TABS, true) ?: 'overview';
    }

    public function show(Project $project, Request $request, ?string $tab = null)
    {
        // /projects/{slug} & URL lama /projects/{id}?tab=xxx → /projects/{slug}/{tab}
        if ($tab === null || $tab !== strtolower($tab)) {
            $requested = strtolower($tab ?? (string) $request->query('tab', 'overview'));
            $slug = isset(self::TABS[$requested]) ? $requested : self::tabSlug($requested);
            $query = http_build_query($request->except('tab'));

            return redirect()->to(route('projects.tab', [$project, $slug]) . ($query ? "?{$query}" : ''));
        }

        $this->authorize('view', $project);

        $tabKey = self::TABS[$tab];

        // Knowledge Base punya halaman sendiri (/projects/{id}/kb).
        if ($tabKey === 'kb') {
            return redirect()->route('kb.index', $project);
        }

        // Chat punya halaman terpusat (/chat?project={slug})
        if ($tabKey === 'chat') {
            return redirect()->route('chat.index', ['project' => $project->slug]);
        }
        $data = ['project' => $project, 'tab' => $tabKey];

        // Load clients & managers untuk modal edit project
        $companyId = $project->company_id;
        $clientsQuery = User::role('client')->where('is_active', true);
        $managersQuery = User::whereDoesntHave('roles', fn ($q) => $q->where('name', 'client'))->where('is_active', true);
        if ($companyId) {
            $clientsQuery->where('company_id', $companyId);
            $managersQuery->where('company_id', $companyId);
        }
        $data['clients'] = $clientsQuery->orderBy('name')->get(['id', 'name']);
        $data['managers'] = $managersQuery->orderBy('name')->get(['id', 'name']);

        $project->load(['client', 'manager']);

        if (in_array($tabKey, ['overview', 'tasks', 'milestones', 'sprints'], true)) {
            $project->load([
                'milestones' => fn ($q) => $q->with(['assignee', 'tasks.assignee', 'sprints.lead', 'sprints.tasks.assignee', 'standaloneTasks.assignee'])->orderByDesc('created_at'),
                'tasks' => fn ($q) => $q->with(['assignee', 'members', 'labels', 'checklists.items', 'attachments', 'milestone', 'boardColumn'])->withCount(['comments', 'attachments'])->orderBy('sort_order'),
            ]);
        }
        if (in_array($tabKey, ['overview', 'team'], true)) {
            $project->load('members.user.roles');
        }
        if (in_array($tabKey, ['overview', 'tickets'], true)) {
            $data['recentTickets'] = $project->tickets()->with('reporter')->latest()->limit(5)->get();
        }
        if ($tabKey === 'overview') {
            $data['recentFilesTotal'] = $project->files()->count();
        }
        if (in_array($tabKey, ['tasks', 'milestones', 'sprints'], true)) {
            $data['assignableUsers'] = $project->members()->with('user')->get()->pluck('user')->push($project->manager)->filter()->unique('id')->values();
        }

        switch ($tabKey) {
            case 'overview':
                $data['memberTaskCounts'] = $project->tasks()
                    ->selectRaw('assigned_to, count(*) as total, sum(case when status="done" then 1 else 0 end) as done')
                    ->whereNotNull('assigned_to')
                    ->groupBy('assigned_to')
                    ->pluck('total', 'assigned_to');
                break;

            case 'tasks':
                $data['columns'] = $project->boardColumns()->orderBy('sort_order')->get();
                $data['projectLabels'] = $project->labels()->orderBy('name')->get();
                $data['sprints'] = $project->sprints()->with(['tasks.assignee'])->orderBy('start_date')->get();
                break;

            case 'team':
                $data['companyUsers'] = User::where('is_active', true)->where('company_id', $project->company_id)
                    ->whereNotIn('id', $project->members()->pluck('user_id'))
                    ->with('roles')
                    ->orderBy('name')->get();
                $data['memberTaskStats'] = $project->tasks()
                    ->selectRaw('assigned_to, count(*) as total, sum(case when status="done" then 1 else 0 end) as done')
                    ->whereNotNull('assigned_to')
                    ->groupBy('assigned_to')
                    ->get()
                    ->keyBy('assigned_to');
                $data['memberTicketStats'] = $project->tickets()
                    ->selectRaw('assignee_id, count(*) as total')
                    ->whereNotNull('assignee_id')
                    ->groupBy('assignee_id')
                    ->pluck('total', 'assignee_id');

                // Pekerjaan belum selesai per anggota (task, sprint, milestone, ticket,
                // recurring task), dipakai dialog hapus anggota untuk memindahkan/
                // mengosongkan penanggung jawabnya dulu.
                $data['memberOpenWork'] = [];
                foreach ($this->openTasksQuery($project)->with('members:id')->get(['id', 'title', 'assigned_to']) as $task) {
                    $userIds = $task->members->pluck('id')->push($task->assigned_to)->filter()->unique();
                    foreach ($userIds as $uid) {
                        $data['memberOpenWork'][$uid][] = ['type' => 'Task', 'id' => $task->id, 'title' => $task->title];
                    }
                }
                foreach ($this->openOwnedWorkQueries($project) as [$label, $query, $column, $titleColumn]) {
                    foreach ($query->whereNotNull($column)->get(['id', $titleColumn, $column]) as $item) {
                        $data['memberOpenWork'][$item->{$column}][] = ['type' => $label, 'id' => $item->id, 'title' => $item->{$titleColumn}];
                    }
                }
                break;

            case 'tickets':
                $status   = $request->query('status');
                $priority = $request->query('priority');
                $type     = $request->query('type');
                $search   = $request->query('search');

                $ticketRelations = [
                    'reporter', 'assignee', 'assignees', 'milestone',
                    'attachments.uploader', 'comments.user', 'histories.actor', 'slaPolicy'
                ];

                $ticketsQuery = $project->tickets()->with($ticketRelations)
                    ->when($status, fn ($q) => $q->where('status', $status))
                    ->when($priority, fn ($q) => $q->where('priority', $priority))
                    ->when($type, fn ($q) => $q->where('type', $type))
                    ->when($search, fn ($q) => $q->where(function ($sq) use ($search) {
                        $sq->where('title', 'like', "%{$search}%")
                           ->orWhere('description', 'like', "%{$search}%");
                    }));

                if ($request->user()->hasRole('client')) {
                    $ticketsQuery->where('reporter_id', $request->user()->id);
                }

                $data['projectTickets'] = $ticketsQuery->latest()->paginate($this->perPage($request, 15, 'tickets_per_page'), ['*'], 'tickets_page')->withQueryString();

                // If specific ticket requested (e.g. from /tickets/2 redirect), make sure it is available for modal
                if ($request->filled('ticket')) {
                    $targetTicketId = (int) $request->ticket;
                    $targetTicket = $project->tickets()->with($ticketRelations)->find($targetTicketId);
                    if ($targetTicket) {
                        $data['initialOpenTicket'] = $targetTicket;
                    }
                }

                $data['projectMilestones'] = $project->milestones()->orderBy('title')->get();
                $data['ticketAssignableUsers'] = $project->members()
                    ->with(['user' => fn ($q) => $q->with('roles')])
                    ->get()
                    ->pluck('user')
                    ->filter(fn ($u) => $u && $u->hasRole('member') && !$u->hasRole('admin') && !$u->hasRole('client'))
                    ->unique('id')
                    ->values();
                $data['ticketStats'] = [
                    'total'    => $project->tickets()->count(),
                    'open'     => $project->tickets()->whereIn('status', ['open', 'assigned', 'in_progress', 'reopened'])->count(),
                    'resolved' => $project->tickets()->whereIn('status', ['resolved', 'closed'])->count(),
                    'breached' => $project->tickets()->where('sla_breached', true)->count(),
                ];
                break;

            case 'timesheet':
                $data += $this->buildTimesheetData($project, $request);
                break;

            case 'files':
                $data += ProjectFileWebController::managerData($project);
                break;

            case 'budget':
                abort_unless($request->user()->can('view', $project), 403);
                $data += BudgetWebController::pageData($request, $project);
                break;

            case 'portal':
                abort_unless($request->user()->can('view', $project), 403);
                $data += ClientPortalWebController::manageData($project);
                break;

            case 'recurring':
                $data['recurringDefinitions'] = $project->recurringTasks()->with('assignee', 'milestone')->withCount('tasks')->orderByDesc('id')
                    ->paginate($this->perPage($request, 10, 'recurring_per_page'), ['*'], 'recurring_page')->withQueryString();
                $data['recurringMilestones'] = $project->milestones()->orderBy('title')->get(['id', 'title']);
                $data['recurringUsers'] = User::where('company_id', $project->company_id)->orderBy('name')->get(['id', 'name']);
                break;

            case 'chat':
                $data['chatMembers'] = User::whereIn('id',
                    $project->members()->pluck('user_id')
                        ->push($project->manager_id)
                        ->filter()
                        ->unique()
                )->select('id', 'name')->get();
                break;
        }

        return view('projects.show', $data);
    }

    public function edit(Project $project)
    {
        return redirect()->route('projects.show', [$project, 'edit' => 1]);
    }

    public function update(Request $request, Project $project)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'status' => 'in:draft,active,on_hold,completed,cancelled',
            'progress' => 'nullable|integer|min:0|max:100',
            'meeting_default_time' => 'nullable|date_format:H:i',
            'meeting_default_duration_minutes' => 'nullable|integer|min:15|max:480',
            'images' => 'nullable|array',
            'images.*' => 'image|max:2048|mimes:jpg,jpeg,png,gif,webp',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'string',
        ]);

        $keptImages = array_values(array_diff($project->images ?? [], $request->input('remove_images', [])));
        $newImages = $this->storeProjectImages($request, Project::MAX_IMAGES - count($keptImages));

        foreach ($request->input('remove_images', []) as $path) {
            if (in_array($path, $project->images ?? [], true)) {
                Storage::disk('public')->delete($path);
            }
        }

        $project->update([
            ...$request->only('name', 'description', 'client_id', 'manager_id', 'status', 'start_date', 'end_date', 'budget', 'progress'),
            'google_meet_enabled' => $request->boolean('google_meet_enabled'),
            'meeting_auto_create' => $request->boolean('meeting_auto_create'),
            'meeting_default_time' => $request->meeting_default_time,
            'meeting_default_duration_minutes' => $request->meeting_default_duration_minutes ?? 60,
            'images' => [...$keptImages, ...$newImages],
        ]);

        if ($request->input('redirect_to') === 'index') {
            return redirect()->route('projects.index')->with('success', 'Proyek berhasil diperbarui.');
        }

        return redirect()->route('projects.show', $project)->with('success', 'Proyek diperbarui.');
    }

    /** Upload file gambar dari $request['images'] ke disk public, dibatasi $limit file (default Project::MAX_IMAGES). Kelebihan diabaikan. */
    private function storeProjectImages(Request $request, ?int $limit = null): array
    {
        if (! $request->hasFile('images')) {
            return [];
        }

        $limit ??= Project::MAX_IMAGES;

        return collect($request->file('images'))
            ->filter(fn ($file) => $file && $file->isValid())
            ->take(max(0, $limit))
            ->map(fn ($file) => $file->store('project-images', 'public'))
            ->filter()
            ->values()
            ->all();
    }

    public function createMeeting(Project $project, GoogleCalendarService $calendar)
    {
        try {
            $calendar->createMeetingForProject($project, auth()->user());
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membuat meeting. Silakan coba lagi.');
        }

        return back()->with('success', 'Meeting berhasil dibuat.');
    }

    public function destroy(Project $project)
    {
        $project->delete();

        return redirect()->route('projects.index')->with('success', 'Proyek dihapus.');
    }

    public function addMember(Request $request, Project $project)
    {
        $request->validate([
            'user_id' => 'required|array|min:1',
            'user_id.*' => 'exists:users,id',
        ]);

        foreach ($request->user_id as $userId) {
            ProjectMember::firstOrCreate(
                ['project_id' => $project->id, 'user_id' => $userId]
            );

            if ((int) $userId !== auth()->id()) {
                $this->notifier->send(
                    $userId,
                    'project_member_added',
                    'Ditambahkan ke Tim Proyek',
                    auth()->user()->name." menambahkan Anda ke tim proyek \"{$project->name}\".",
                    ['project_id' => $project->id]
                );
            }
        }

        return back()->with('success', 'Anggota tim ditambahkan.');
    }

    /**
     * Keluarkan anggota dari tim. Pekerjaan yang BELUM selesai milik anggota itu
     * (task, sprint, milestone, ticket, recurring task) wajib dipindahkan ke
     * anggota tim lain lewat reassign_to. Lead wajib menunjuk new_manager_id.
     * Pekerjaan yang sudah selesai & time log tidak diubah supaya riwayat tetap utuh.
     */
    public function removeMember(Request $request, Project $project, User $user)
    {
        $request->validate([
            'reassign_to' => 'nullable|integer',
            'new_manager_id' => 'nullable|integer',
        ]);

        // Lead yang keluar dari tim wajib menyerahkan posisi Lead ke anggota tim lain;
        // kalau tidak, ia tetap jadi manager_id dan tetap punya akses ke proyek.
        $newManagerId = null;
        if ((int) $project->manager_id === $user->id) {
            $newManagerId = (int) $request->new_manager_id;
            $isOtherMember = $newManagerId && $newManagerId !== $user->id
                && ProjectMember::where('project_id', $project->id)->where('user_id', $newManagerId)->exists();
            if (! $isOtherMember) {
                return back()->with('danger', 'Tunjuk Lead baru dulu dari anggota tim lain.');
            }
        }

        $tasks = $this->openTasksQuery($project)
            ->where(fn ($q) => $q->where('assigned_to', $user->id)
                ->orWhereHas('members', fn ($m) => $m->where('users.id', $user->id)))
            ->get();

        // Sprint, milestone, ticket & recurring task yang ia pegang ikut dipindahkan.
        $owned = collect($this->openOwnedWorkQueries($project))
            ->map(fn ($def) => [$def[1]->where($def[2], $user->id), $def[2]]);
        $itemCount = $tasks->count() + $owned->sum(fn ($o) => (clone $o[0])->count());

        // Pekerjaan yang belum beres wajib dipindahkan ke anggota tim lain (tidak boleh dikosongkan).
        $reassignTo = null;
        if ($itemCount > 0) {
            $reassignTo = (int) $request->reassign_to;
            $isTeam = $reassignTo === (int) $project->manager_id
                || ProjectMember::where('project_id', $project->id)->where('user_id', $reassignTo)->exists();
            if (! $reassignTo || $reassignTo === $user->id || ! $isTeam) {
                return back()->with('danger', 'Pilih anggota tim lain untuk menerima pekerjaan yang belum selesai.');
            }
        }

        DB::transaction(function () use ($tasks, $owned, $project, $user, $reassignTo, $newManagerId) {
            if ($newManagerId) {
                $project->update(['manager_id' => $newManagerId]);
            }

            foreach ($tasks as $task) {
                if ((int) $task->assigned_to === $user->id) {
                    $task->update(['assigned_to' => $reassignTo]);
                }
                if ($task->members()->detach($user->id) && $reassignTo) {
                    $task->members()->syncWithoutDetaching([$reassignTo]);
                }
            }

            foreach ($owned as [$query, $column]) {
                $query->update([$column => $reassignTo]);
            }

            ProjectMember::where('project_id', $project->id)->where('user_id', $user->id)->delete();
        });

        $message = 'Anggota dihapus.';
        if ($itemCount > 0) {
            if ($reassignTo) {
                $receiver = User::find($reassignTo);
                $message .= " {$itemCount} pekerjaan dipindahkan ke {$receiver->name}.";
                if ($reassignTo !== auth()->id()) {
                    $this->notifier->send(
                        $reassignTo,
                        'task_assigned',
                        'Pekerjaan Dipindahkan ke Anda',
                        auth()->user()->name." memindahkan {$itemCount} pekerjaan (task/sprint/milestone/ticket) dari {$user->name} ke Anda di proyek \"{$project->name}\".",
                        ['project_id' => $project->id]
                    );
                }
            } else {
                $message .= " {$itemCount} pekerjaan sekarang tanpa penanggung jawab.";
            }
        }

        if ($newManagerId) {
            $newManager = User::find($newManagerId);
            $message .= " {$newManager->name} sekarang menjadi Lead proyek.";
            if ($newManagerId !== auth()->id()) {
                $this->notifier->send(
                    $newManagerId,
                    'project_lead_assigned',
                    'Anda Menjadi Lead Proyek',
                    auth()->user()->name." menunjuk Anda sebagai Lead proyek \"{$project->name}\" menggantikan {$user->name}.",
                    ['project_id' => $project->id]
                );
            }
        }

        // Yang mengeluarkan dirinya sendiri bisa kehilangan akses ke proyek ini,
        // jadi jangan kembalikan ke halaman proyek (akan 403).
        if (! auth()->user()->can('view', $project->fresh())) {
            return redirect()->route('projects.index')->with('success', $message);
        }

        return back()->with('success', $message);
    }

    /**
     * Pekerjaan proyek selain task yang punya satu penanggung jawab dan belum selesai.
     * Tiap entri: [label, query, kolom penanggung jawab, kolom judul].
     */
    private function openOwnedWorkQueries(Project $project): array
    {
        return [
            ['Sprint', $project->sprints()->where('status', '!=', 'completed'), 'assigned_to', 'name'],
            ['Milestone', $project->milestones()->where('status', '!=', 'completed'), 'assigned_to', 'title'],
            ['Ticket', $project->tickets()->whereNotIn('status', ['resolved', 'closed']), 'assignee_id', 'title'],
            ['Recurring', $project->recurringTasks()->where('is_active', true), 'assigned_to', 'title'],
        ];
    }

    /** Task proyek yang belum selesai: kolom board-nya bukan kolom "done" (atau status != done kalau tanpa kolom). */
    private function openTasksQuery(Project $project)
    {
        return $project->tasks()->where(fn ($q) => $q
            ->whereHas('boardColumn', fn ($c) => $c->where('is_done', false))
            ->orWhere(fn ($q2) => $q2->whereNull('board_column_id')->where('status', '!=', 'done')));
    }

    public function timesheet(Request $request, Project $project)
    {
        $logs = $this->timeLogsQuery($project, $request)
            ->orderByDesc('started_at')
            ->paginate($this->perPage($request), ['*'], 'logs_page')->withQueryString();

        return view('projects.timesheet', array_merge(
            compact('project', 'logs'),
            $this->buildTimesheetData($project, $request)
        ));
    }

    /** Base query for a project's time logs, dengan filter tanggal opsional dari request. */
    private function timeLogsQuery(Project $project, Request $request)
    {
        $from = $request->input('start_date', $request->input('from', $request->input('gantt_start')));
        $to = $request->input('end_date', $request->input('to', $request->input('gantt_end')));

        return TimeLog::with(['user', 'task'])
            ->whereHas('task', fn ($q) => $q->where('project_id', $project->id))
            ->when($from, fn ($q) => $q->whereDate('started_at', '>=', $from))
            ->when($to, fn ($q) => $q->whereDate('started_at', '<=', $to));
    }

    private function buildTimesheetData(Project $project, Request $request): array
    {
        // Filter rentang tanggal Gantt Chart: default bulan berjalan saat ini (1 bulan penuh)
        $startDateInput = $request->input('start_date', $request->input('gantt_start', $request->input('from')));
        $endDateInput = $request->input('end_date', $request->input('gantt_end', $request->input('to')));

        if ($startDateInput && $endDateInput) {
            $ganttStart = \Carbon\Carbon::parse($startDateInput)->startOfDay();
            $ganttEnd = \Carbon\Carbon::parse($endDateInput)->startOfDay();
        } elseif ($startDateInput) {
            $ganttStart = \Carbon\Carbon::parse($startDateInput)->startOfDay();
            $ganttEnd = $ganttStart->copy()->endOfMonth()->startOfDay();
        } else {
            // Default: bulan berjalan saat ini (1 bulan penuh dari tgl 1 sampai akhir bulan)
            $ganttStart = now()->startOfMonth()->startOfDay();
            $ganttEnd = now()->endOfMonth()->startOfDay();
        }

        if ($ganttEnd->lt($ganttStart)) {
            $ganttEnd = $ganttStart->copy()->endOfMonth()->startOfDay();
        }

        $ganttDays = max(1, (int) $ganttStart->diffInDays($ganttEnd) + 1);

        // Agregasi langsung di DB (bukan load semua row time_logs ke PHP) supaya ringan
        // walau time log-nya sudah ribuan baris — cuma butuh total per user, bukan detailnya.
        $summary = $this->timeLogsQuery($project, $request)
            ->selectRaw('user_id, ROUND(SUM(minutes) / 60, 2) as total_hours, COUNT(*) as logs_count')
            ->groupBy('user_id')
            ->with('user')
            ->get()
            ->map(fn ($row) => [
                'user' => $row->user,
                'total_hours' => (float) $row->total_hours,
                'logs_count' => $row->logs_count,
            ])
            ->values();

        // Preview 15 log terbaru buat tab ringkas di halaman project — detail lengkap +
        // pagination ada di halaman Timesheet penuh (route projects.timesheet).
        $recentLogs = $this->timeLogsQuery($project, $request)->orderByDesc('started_at')->limit(15)->get();
        $recentLogsTotal = $this->timeLogsQuery($project, $request)->count();

        // Effective start/end per task: own dates, else its sprint's dates
        $effStart = fn ($t) => $t->start_date ?? $t->due_date ?? $t->sprint?->start_date;
        $effEnd = fn ($t) => $t->due_date ?? $t->start_date ?? $t->sprint?->end_date;

        // Sprints yang overlap dengan rentang [ganttStart, ganttEnd]
        $sprints = $project->sprints()
            ->with(['tasks.assignee'])
            ->orderBy('start_date')
            ->get()
            ->filter(function ($sprint) use ($ganttStart, $ganttEnd) {
                if (!$sprint->start_date && !$sprint->end_date) return true;
                $s = ($sprint->start_date ?? $sprint->end_date)->copy()->startOfDay();
                $e = ($sprint->end_date ?? $sprint->start_date)->copy()->startOfDay();
                return $s->lte($ganttEnd) && $e->gte($ganttStart);
            })
            ->values();

        // Gantt: tasks with start/due dates, or belonging to a sprint, yang overlap dengan [ganttStart, ganttEnd]
        $ganttTasks = $project->tasks()
            ->with(['milestone', 'assignee', 'sprint', 'timeLogs' => fn ($q) => $q->where('is_running', false)->orderBy('started_at')])
            ->where(fn ($q) => $q->whereNotNull('start_date')->orWhereNotNull('due_date')->orWhereNotNull('sprint_id'))
            ->orderBy('milestone_id')
            ->orderBy('start_date')
            ->get()
            ->filter(function ($t) use ($effStart, $effEnd, $ganttStart, $ganttEnd) {
                $s = $effStart($t);
                $e = $effEnd($t);
                if (!$s && !$e) return false;
                $s = ($s ?? $e)->copy()->startOfDay();
                $e = ($e ?? $s)->copy()->startOfDay();
                return $s->lte($ganttEnd) && $e->gte($ganttStart);
            })
            ->values();

        return compact('summary', 'recentLogs', 'recentLogsTotal', 'sprints', 'ganttTasks', 'ganttStart', 'ganttEnd', 'ganttDays');
    }
}
