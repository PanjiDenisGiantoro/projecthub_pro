<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\DirectMessageAttachment;
use App\Models\Forum;
use App\Models\ForumMessage;
use App\Models\ForumMessageAttachment;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\MessageRead;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectMessage;
use App\Models\TaskAttachment;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatWebController extends Controller
{
    public function __construct(private NotificationService $notifier)
    {
    }

    private function canAccess(Project $project): bool
    {
        $user = Auth::user();
        if ($user->is_super_admin)
            return true;
        return $project->members()->where('user_id', $user->id)->exists()
            || $project->manager_id === $user->id
            || $project->client_id === $user->id;
    }

    public function index(Request $request)
    {
        $user = Auth::user();

        // ── 1. Proyek Chat ────────────────────────────────────────────────
        // Hanya proyek yang di-assign ke user login (manager, client, atau member)
        $projectQuery = Project::query();
        if (!$user->is_super_admin) {
            $projectQuery->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                    ->orWhere('client_id', $user->id)
                    ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            });
        }

        $rawProjects = $projectQuery
            ->with(['client', 'manager', 'members.user'])
            ->withCount([
                'messages as unread_count' => function ($q) use ($user) {
                    $q->where('user_id', '!=', $user->id)
                        ->whereDoesntHave('reads', fn($r) => $r->where('user_id', $user->id));
                },
                'tasks as total_tasks_count',
                'tasks as completed_tasks_count' => function ($q) {
                    $q->where('status', 'done');
                },
            ])
            ->with(['messages' => fn($q) => $q->with('user')->latest()->limit(1)])
            ->orderBy('name')
            ->get();

        $projects = $rawProjects->map(function ($p) use ($user) {
            $totalTasks = $p->total_tasks_count ?? 0;
            $doneTasks = $p->completed_tasks_count ?? 0;
            $progress = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;
            $memberCount = $p->members->count() + ($p->manager_id ? 1 : 0) + ($p->client_id ? 1 : 0);

            return [
                'type' => 'project',
                'id' => $p->id,
                'slug' => $p->slug,
                'name' => $p->name,
                'client_name' => $p->client?->name,
                'status' => $p->status ?: 'active',
                'deadline' => $p->end_date ? \Carbon\Carbon::parse($p->end_date)->format('d M Y') : 'Tanpa deadline',
                'progress' => $progress,
                'lead_name' => $p->manager?->name ?? 'Belum ditentukan',
                'lead_avatar' => $p->manager?->avatar ? Storage::url($p->manager->avatar) : null,
                'member_count' => $memberCount,
                'avatar' => null,
                'initials' => strtoupper(mb_substr($p->name, 0, 2)),
                'unread_count' => $p->unread_count,
                'last_message' => $p->messages->first() ? [
                    'body' => $p->messages->first()->body ?: '[Berkas]',
                    'is_mine' => $p->messages->first()->user_id === $user->id,
                    'user_name' => $p->messages->first()->user?->name ?? 'User',
                    'time' => $p->messages->first()->created_at->diffForHumans(),
                    'timestamp' => $p->messages->first()->created_at->timestamp,
                ] : null,
            ];
        });

        // ── 2. Direct Chat (Hanya rekan yang satu proyek) ─────────────────
        $teammateUserIds = collect();
        foreach ($rawProjects as $p) {
            if ($p->manager_id)
                $teammateUserIds->push($p->manager_id);
            if ($p->client_id)
                $teammateUserIds->push($p->client_id);
            foreach ($p->members as $pm) {
                if ($pm->user_id)
                    $teammateUserIds->push($pm->user_id);
            }
        }
        $teammateUserIds = $teammateUserIds->unique()->reject(fn($id) => $id == $user->id)->values();

        $peers = User::whereIn('id', $teammateUserIds)
            ->where('is_active', true)
            ->with(['roles'])
            ->orderBy('name')
            ->get();

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->withCount([
                'messages as unread_count' => function ($q) use ($user) {
                    $q->where('user_id', '!=', $user->id)
                        ->whereDoesntHave('reads', fn($r) => $r->where('user_id', $user->id));
                }
            ])
            ->with(['messages' => fn($q) => $q->with('user')->latest()->limit(1)])
            ->get()
            ->keyBy(fn($c) => $c->otherUser($user->id)->id);

        $directMessages = $peers->map(function ($peer) use ($conversations, $user, $rawProjects) {
            $conv = $conversations->get($peer->id);
            $lastMsg = $conv?->messages->first();

            // Shared projects with this peer
            $sharedProjects = $rawProjects->filter(function ($p) use ($peer) {
                return $p->manager_id == $peer->id
                    || $p->client_id == $peer->id
                    || $p->members->contains('user_id', $peer->id);
            })->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                    'status' => $p->status ?: 'active',
                ])->values();

            // Determine peer designation
            $isClient = $peer->hasRole('client');
            $leadInProject = $sharedProjects->first();
            $peerRole = $isClient ? 'Client' : ($peer->id === ($rawProjects->firstWhere('manager_id', $peer->id)?->manager_id) ? 'Project Lead' : 'Member');

            return [
                'type' => 'dm',
                'id' => $peer->id,
                'name' => $peer->name,
                'email' => $peer->email,
                'avatar' => $peer->avatar ? Storage::url($peer->avatar) : null,
                'initials' => strtoupper(mb_substr($peer->name, 0, 2)),
                'unread_count' => $conv->unread_count ?? 0,
                'role_title' => $peerRole,
                'shared_projects' => $sharedProjects,
                'last_message' => $lastMsg ? [
                    'body' => $lastMsg->trashed() ? '[pesan dihapus]' : ($lastMsg->body ?: '[Berkas]'),
                    'is_mine' => $lastMsg->user_id === $user->id,
                    'user_name' => $lastMsg->user?->name ?? 'User',
                    'time' => $lastMsg->created_at->diffForHumans(),
                    'timestamp' => $lastMsg->created_at->timestamp,
                ] : null,
            ];
        });

        // ── 3. Forum (Kanal) ─────────────────────────────────────────────
        $forumQuery = Forum::where('company_id', $user->company_id);
        if (!$user->is_super_admin) {
            $forumQuery->whereHas('members', fn($q) => $q->where('user_id', $user->id));
        }

        $forums = $forumQuery
            ->withCount([
                'messages as unread_count' => function ($q) use ($user) {
                    $q->where('user_id', '!=', $user->id)
                        ->whereDoesntHave('reads', fn($r) => $r->where('user_id', $user->id));
                }
            ])
            ->withCount('members')
            ->with(['creator', 'messages' => fn($q) => $q->with('user')->latest()->limit(1)])
            ->orderBy('name')
            ->get()
            ->map(fn($f) => [
                'type' => 'forum',
                'id' => $f->id,
                'name' => $f->name,
                'description' => $f->description ?: 'Ruang diskusi publik untuk koordinasi tim Flovig.',
                'creator_name' => $f->creator?->name ?? 'Flovig Team',
                'avatar' => null,
                'initials' => strtoupper(mb_substr($f->name, 0, 2)),
                'unread_count' => $f->unread_count,
                'member_count' => $f->members_count,
                'last_message' => $f->messages->first() ? [
                    'body' => $f->messages->first()->trashed() ? '[pesan dihapus]' : ($f->messages->first()->body ?: '[Berkas]'),
                    'is_mine' => $f->messages->first()->user_id === $user->id,
                    'user_name' => $f->messages->first()->user?->name ?? 'User',
                    'time' => $f->messages->first()->created_at->diffForHumans(),
                    'timestamp' => $f->messages->first()->created_at->timestamp,
                ] : null,
            ]);

        // Target item from query parameters
        $initialTarget = null;
        if ($request->filled('project')) {
            $param = $request->query('project');
            $p = $projects->first(fn($item) => $item['id'] == $param || ($item['slug'] ?? null) == $param);
            if ($p)
                $initialTarget = ['type' => 'project', 'id' => $p['id']];
        } elseif ($request->filled('dm') || $request->filled('user')) {
            $dmId = (int) ($request->query('dm') ?: $request->query('user'));
            $d = $directMessages->firstWhere('id', $dmId);
            if ($d)
                $initialTarget = ['type' => 'dm', 'id' => $d['id']];
        } elseif ($request->filled('forum')) {
            $forumId = (int) $request->query('forum');
            $f = $forums->firstWhere('id', $forumId);
            if ($f)
                $initialTarget = ['type' => 'forum', 'id' => $f['id']];
        }

        // Default: first project, first forum, or first dm
        if (!$initialTarget) {
            if ($projects->isNotEmpty()) {
                $initialTarget = ['type' => 'project', 'id' => $projects->first()['id']];
            } elseif ($forums->isNotEmpty()) {
                $initialTarget = ['type' => 'forum', 'id' => $forums->first()['id']];
            } elseif ($directMessages->isNotEmpty()) {
                $initialTarget = ['type' => 'dm', 'id' => $directMessages->first()['id']];
            }
        }

        return view('chat.index', [
            'projects' => $projects->values(),
            'dms' => $directMessages->values(),
            'forums' => $forums->values(),
            'inviteCandidates' => $peers->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'email' => $p->email,
                'avatar' => $p->avatar ? Storage::url($p->avatar) : null,
                'initials' => strtoupper(mb_substr($p->name, 0, 2)),
            ])->values(),
            'initialTarget' => $initialTarget,
        ]);
    }

    public function messages(Request $request, Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $after = $request->integer('after', 0);
        $userId = Auth::id();

        $query = ProjectMessage::withTrashed()
            ->where('project_id', $project->id)
            ->with(['user', 'reactions.user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        if ($after > 0) {
            $messages = $query->where('id', '>', $after)->oldest()->get();
        } else {
            $messages = $query->oldest()->limit(80)->get();
        }

        $readIds = MessageRead::where('user_id', $userId)
            ->whereIn('message_id', $messages->pluck('id'))
            ->pluck('message_id')
            ->flip();

        return response()->json([
            'messages' => $messages->map(fn($m) => $this->formatMessage($m, $userId, $readIds, $project)),
        ]);
    }

    public function details(Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $project->load(['client', 'manager', 'members.user']);
        $totalTasks = $project->tasks()->count();
        $doneTasks = $project->tasks()->where('status', 'done')->count();
        $progress = $totalTasks > 0 ? (int) round(($doneTasks / $totalTasks) * 100) : 0;

        $pinnedMessages = ProjectMessage::withTrashed()
            ->where('project_id', $project->id)
            ->where('is_pinned', true)
            ->with('user')
            ->orderByDesc('pinned_at')
            ->orderByDesc('id')
            ->get()
            ->map(fn($m) => [
                'id' => $m->id,
                'user_id' => $m->user_id,
                'body' => $m->trashed() ? '[Pesan dihapus]' : Str::limit($m->body, 90),
                'user_name' => $m->user?->name ?? 'User',
                'time' => $m->created_at->format('H:i'),
                'date' => $m->created_at->format('d M Y'),
            ]);

        $files = $this->getProjectFiles($project);

        $members = collect();
        if ($project->manager) {
            $members->push([
                'id' => $project->manager->id,
                'name' => $project->manager->name,
                'avatar' => $project->manager->avatar ? Storage::url($project->manager->avatar) : null,
                'initials' => strtoupper(mb_substr($project->manager->name, 0, 2)),
                'role' => 'Project Lead',
                'is_lead' => true,
            ]);
        }
        if ($project->client && $project->client->id !== $project->manager_id) {
            $members->push([
                'id' => $project->client->id,
                'name' => $project->client->name,
                'avatar' => $project->client->avatar ? Storage::url($project->client->avatar) : null,
                'initials' => strtoupper(mb_substr($project->client->name, 0, 2)),
                'role' => 'Client',
                'is_lead' => false,
            ]);
        }
        foreach ($project->members as $pm) {
            $userObj = $pm->user;
            if ($userObj && !$members->contains('id', $userObj->id)) {
                $members->push([
                    'id' => $userObj->id,
                    'name' => $userObj->name,
                    'avatar' => $userObj->avatar ? Storage::url($userObj->avatar) : null,
                    'initials' => strtoupper(mb_substr($userObj->name, 0, 2)),
                    'role' => $pm->role ?: 'Member',
                    'is_lead' => false,
                ]);
            }
        }

        return response()->json([
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'slug' => $project->slug,
                'client_name' => $project->client?->name,
                'status' => $project->status ?: 'active',
                'deadline' => $project->end_date ? \Carbon\Carbon::parse($project->end_date)->format('d M Y') : 'Tanpa deadline',
                'progress' => $progress,
                'total_tasks' => $totalTasks,
                'completed_tasks' => $doneTasks,
                'lead_name' => $project->manager?->name ?? '-',
                'lead_avatar' => $project->manager?->avatar ? Storage::url($project->manager->avatar) : null,
            ],
            'pinned_messages' => $pinnedMessages,
            'files' => $files,
            'members' => $members->values(),
        ]);
    }

    public function togglePin(Project $project, ProjectMessage $message)
    {
        abort_unless($this->canAccess($project), 403);

        $newPinned = !$message->is_pinned;
        $message->update([
            'is_pinned' => $newPinned,
            'pinned_by' => $newPinned ? Auth::id() : null,
            'pinned_at' => $newPinned ? now() : null,
        ]);

        return response()->json([
            'is_pinned' => $newPinned,
            'message_id' => $message->id,
        ]);
    }

    public function getProjectFiles(Project $project): array
    {
        $items = collect();

        // 1. ProjectFile (mandiri)
        try {
            foreach ($project->files()->with('uploader')->latest()->get() as $pf) {
                $isLink = $pf->isLink();
                $ext = $isLink ? 'link' : strtolower(pathinfo($pf->original_name, PATHINFO_EXTENSION));
                $items->push([
                    'id' => 'pf-' . $pf->id,
                    'name' => $pf->original_name,
                    'url' => $pf->url(),
                    'size' => $isLink ? 'Link' : $pf->humanSize(),
                    'raw_size' => (int) $pf->size,
                    'extension' => $ext,
                    'is_image' => $pf->isImage(),
                    'created_at' => $pf->created_at->format('d M Y'),
                    'date_human' => $pf->created_at->diffForHumans(),
                    'source' => 'project',
                    'created_timestamp' => $pf->created_at->timestamp,
                ]);
            }
        } catch (\Throwable $e) {
        }

        // 2. TaskAttachment
        try {
            $taskAtts = TaskAttachment::whereHas('task', fn($q) => $q->where('project_id', $project->id))->latest()->get();
            foreach ($taskAtts as $ta) {
                $ext = strtolower(pathinfo($ta->file_name, PATHINFO_EXTENSION));
                $url = method_exists($ta, 'publicUrl') ? $ta->publicUrl() : (method_exists($ta, 'url') ? $ta->url() : ($ta->file_path ? Storage::url($ta->file_path) : '#'));
                $items->push([
                    'id' => 'ta-' . $ta->id,
                    'name' => $ta->file_name,
                    'url' => $url,
                    'size' => $this->formatSize($ta->file_size),
                    'raw_size' => (int) $ta->file_size,
                    'extension' => $ext,
                    'is_image' => $ta->isImage(),
                    'created_at' => $ta->created_at->format('d M Y'),
                    'date_human' => $ta->created_at->diffForHumans(),
                    'source' => 'task',
                    'created_timestamp' => $ta->created_at->timestamp,
                ]);
            }
        } catch (\Throwable $e) {
        }

        // 3. MessageAttachment (chat)
        try {
            $msgAtts = MessageAttachment::whereHas('message', fn($q) => $q->where('project_id', $project->id))->latest()->get();
            foreach ($msgAtts as $ma) {
                $ext = strtolower(pathinfo($ma->file_name, PATHINFO_EXTENSION));
                $items->push([
                    'id' => 'ma-' . $ma->id,
                    'name' => $ma->file_name,
                    'url' => $ma->url(),
                    'size' => $this->formatSize($ma->file_size),
                    'raw_size' => (int) $ma->file_size,
                    'extension' => $ext,
                    'is_image' => $ma->isImage(),
                    'created_at' => $ma->created_at->format('d M Y'),
                    'date_human' => $ma->created_at->diffForHumans(),
                    'source' => 'chat',
                    'created_timestamp' => $ma->created_at->timestamp,
                ]);
            }
        } catch (\Throwable $e) {
        }

        return $items->sortByDesc('created_timestamp')->values()->toArray();
    }

    private function getUserRoleInProject(?Project $project, int $userId): ?string
    {
        if (!$project)
            return null;
        if ($project->manager_id === $userId)
            return 'Project Lead';
        if ($project->client_id === $userId)
            return 'Client';
        return null; // Ordinary members have no badge as requested
    }

    private function formatMessage(ProjectMessage $m, int $userId, $readIds = null, ?Project $project = null): array
    {
        $reactionGroups = $m->reactions
            ->groupBy('emoji')
            ->map(fn($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'users' => $group->pluck('user.name')->filter()->values()->toArray(),
                'reacted' => $group->contains('user_id', $userId),
            ])->values()->toArray();

        return [
            'id' => $m->id,
            'body' => $m->trashed() ? '' : $m->body,
            'formatted_body' => $m->trashed() ? '' : $this->highlightMentions($m->body),
            'created_at' => $m->created_at->toIso8601String(),
            'time_str' => $m->created_at->format('H:i'),
            'date_str' => $m->created_at->format('d M Y'),
            'edited_at' => $m->edited_at?->format('d M, H:i'),
            'deleted' => $m->trashed(),
            'is_mine' => $m->user_id === $userId,
            'is_pinned' => (bool) $m->is_pinned,
            'pinned_at' => $m->pinned_at?->format('d M, H:i'),
            'user' => [
                'id' => $m->user?->id,
                'name' => $m->user?->name ?? 'Deleted User',
                'avatar' => $m->user?->avatar ? Storage::url($m->user->avatar) : null,
                'initials' => $m->user ? strtoupper(mb_substr($m->user->name, 0, 2)) : '??',
                'role_badge' => $this->getUserRoleInProject($project, $m->user_id),
            ],
            'parent' => $m->parent ? [
                'id' => $m->parent->id,
                'user_id' => $m->parent->user_id,
                'body' => $m->parent->trashed() ? '[Pesan dihapus]' : Str::limit($m->parent->body, 80),
                'user' => $m->parent->user?->name ?? 'User',
            ] : null,
            'reactions' => $reactionGroups,
            'attachments' => $m->attachments->map(fn($a) => [
                'id' => $a->id,
                'name' => $a->file_name,
                'url' => $a->url(),
                'is_image' => $a->isImage(),
                'size' => $this->formatSize($a->file_size),
            ])->toArray(),
            'read_by_me' => $readIds !== null ? $readIds->has($m->id) : false,
        ];
    }

    private function highlightMentions(string $body): string
    {
        $escaped = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        return preg_replace(
            '/@([\w.]+)/',
            '<span class="font-semibold text-blue-600 dark:text-blue-400">@$1</span>',
            $escaped
        );
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024)
            return "{$bytes} B";
        if ($bytes < 1048576)
            return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $request->validate([
            'body' => 'nullable|string|max:5000',
            'parent_id' => 'nullable|exists:project_messages,id',
            'files' => 'nullable|array|max:5',
            'files.*' => 'file|max:2048', // Batas attachment max 2 MB
        ]);

        if (!$request->filled('body') && !$request->hasFile('files')) {
            return response()->json(['error' => 'Pesan kosong.'], 422);
        }

        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id' => Auth::id(),
            'parent_id' => $request->parent_id,
            'body' => $request->body ?? '',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("chat-attachments/{$project->id}", 'public');
                MessageAttachment::create([
                    'message_id' => $message->id,
                    'file_name' => $file->getClientOriginalName(),
                    'file_path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'file_size' => $file->getSize(),
                ]);
            }
        }

        // Notify @mentions
        preg_match_all('/@([\w.]+)/', $message->body, $matches);
        if (!empty($matches[1])) {
            User::whereIn('name', $matches[1])->get()
                ->each(function (User $mentioned) use ($project, $message) {
                    if ($mentioned->id === Auth::id())
                        return;
                    $this->notifier->send(
                        $mentioned->id,
                        'chat_mention',
                        'Anda disebut di chat proyek',
                        Auth::user()->name . " menyebut Anda di chat \"{$project->name}\"",
                        ['project_id' => $project->id, 'message_id' => $message->id]
                    );
                });
        }

        // Notify other project members
        $mentionedIds = User::whereIn('name', $matches[1] ?? [])->pluck('id');
        $memberIds = $project->members()->pluck('user_id')
            ->push($project->manager_id)
            ->filter()
            ->unique()
            ->diff($mentionedIds)
            ->reject(fn($id) => $id === Auth::id());

        foreach ($memberIds as $uid) {
            $this->notifier->send(
                $uid,
                'project_chat',
                'Pesan baru di ' . $project->name,
                Auth::user()->name . ': ' . Str::limit($message->body ?: '[Berkas]', 80),
                ['project_id' => $project->id, 'message_id' => $message->id]
            );
        }

        // Auto-read for sender
        MessageRead::insertOrIgnore([
            [
                'message_id' => $message->id,
                'user_id' => Auth::id(),
                'read_at' => now()->toDateTimeString(),
            ]
        ]);

        $message->load(['user', 'reactions.user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id(), null, $project),
        ], 201);
    }

    public function update(Request $request, Project $project, ProjectMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $request->validate(['body' => 'required|string|max:5000']);

        $message->update(['body' => $request->body, 'edited_at' => now()]);
        $message->load(['user', 'reactions.user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id(), null, $project),
        ]);
    }

    public function destroy(Project $project, ProjectMessage $message)
    {
        abort_unless(
            $message->user_id === Auth::id() || Auth::user()->hasRole(['admin', 'member']),
            403
        );
        $message->delete();
        return response()->json(['ok' => true]);
    }

    public function react(Request $request, Project $project, ProjectMessage $message)
    {
        abort_unless($this->canAccess($project), 403);
        $request->validate(['emoji' => 'required|string|max:10']);

        $existing = MessageReaction::where([
            'message_id' => $message->id,
            'user_id' => Auth::id(),
            'emoji' => $request->emoji,
        ])->first();

        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id' => Auth::id(),
                'emoji' => $request->emoji,
            ]);
        }

        $message->load('reactions.user');
        $reactions = $message->reactions
            ->groupBy('emoji')
            ->map(fn($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
                'users' => $group->pluck('user.name')->filter()->values()->toArray(),
                'reacted' => $group->contains('user_id', Auth::id()),
            ])->values()->toArray();

        return response()->json(['reactions' => $reactions]);
    }

    public function markRead(Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $unreadIds = ProjectMessage::withTrashed()
            ->where('project_id', $project->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', Auth::id()))
            ->pluck('id');

        if ($unreadIds->isNotEmpty()) {
            $now = now()->toDateTimeString();
            $rows = $unreadIds->map(fn($id) => [
                'message_id' => $id,
                'user_id' => Auth::id(),
                'read_at' => $now,
            ])->toArray();
            MessageRead::insertOrIgnore($rows);
        }

        return response()->json(['ok' => true]);
    }

    public function unreadCount()
    {
        $user = Auth::user();

        $projectIds = $user->is_super_admin
            ? Project::pluck('id')
            : Project::where('manager_id', $user->id)
                ->orWhere('client_id', $user->id)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->pluck('id');

        $projectCounts = ProjectMessage::withTrashed()
            ->whereIn('project_id', $projectIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->groupBy('project_id')
            ->selectRaw('project_id, count(*) as count')
            ->pluck('count', 'project_id');
        $projectUnread = $projectCounts->sum();

        $conversationIds = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->pluck('id');

        $dmCounts = [];
        $activeConvs = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->get();
        foreach ($activeConvs as $conv) {
            $peerId = $conv->otherUser($user->id)->id;
            $cnt = DirectMessage::withTrashed()
                ->where('conversation_id', $conv->id)
                ->where('user_id', '!=', $user->id)
                ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
                ->count();
            if ($cnt > 0) {
                $dmCounts[$peerId] = $cnt;
            }
        }
        $dmUnread = array_sum($dmCounts);

        $forumIds = $user->is_super_admin
            ? Forum::where('company_id', $user->company_id)->pluck('id')
            : Forum::whereHas('members', fn($q) => $q->where('user_id', $user->id))->pluck('id');

        $forumCounts = ForumMessage::withTrashed()
            ->whereIn('forum_id', $forumIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->groupBy('forum_id')
            ->selectRaw('forum_id, count(*) as count')
            ->pluck('count', 'forum_id');
        $forumUnread = $forumCounts->sum();

        return response()->json([
            'total' => $projectUnread + $dmUnread + $forumUnread,
            'projects' => $projectCounts,
            'dms' => $dmCounts,
            'forums' => $forumCounts,
        ]);
    }

    public function members(Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $members = User::whereIn(
            'id',
            $project->members()->pluck('user_id')->push($project->manager_id)->filter()->unique()
        )->select('id', 'name')->get();

        return response()->json($members);
    }
}
