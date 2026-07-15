<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\Forum;
use App\Models\MessageAttachment;
use App\Models\MessageReaction;
use App\Models\MessageRead;
use App\Models\Project;
use App\Models\ProjectMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ChatWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    private function canAccess(Project $project): bool
    {
        $user = Auth::user();
        if ($user->hasRole(['admin', 'manager'])) return true;
        return $project->members()->where('user_id', $user->id)->exists()
            || $project->manager_id === $user->id
            || $project->client_id === $user->id;
    }

    public function index()
    {
        $user = Auth::user();

        // ── Proyek ────────────────────────────────────────────────────────
        $projectQuery = Project::query();
        if (!$user->hasRole(['admin', 'manager'])) {
            $projectQuery->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhere('client_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            });
        }

        $projects = $projectQuery
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->whereDoesntHave('reads', fn($r) => $r->where('user_id', $user->id));
            }])
            ->with(['messages' => fn($q) => $q->with('user')->latest()->limit(1)])
            ->get()
            ->map(fn($p) => [
                'type'         => 'project',
                'id'           => $p->id,
                'name'         => $p->name,
                'avatar'       => null,
                'initials'     => strtoupper(mb_substr($p->name, 0, 2)),
                'unread_count' => $p->unread_count,
                'last_message' => $p->messages->first() ? [
                    'body'      => $p->messages->first()->body ?: '[file]',
                    'is_mine'   => $p->messages->first()->user_id === $user->id,
                    'user_name' => $p->messages->first()->user?->name ?? 'User',
                    'time'      => $p->messages->first()->created_at->diffForHumans(),
                    'timestamp' => $p->messages->first()->created_at->timestamp,
                ] : null,
            ]);

        // ── Pesan (DM) ───────────────────────────────────────────────────
        $peers = User::where('company_id', $user->company_id)
            ->where('id', '!=', $user->id)
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->orderBy('name')
            ->get(['id', 'name', 'avatar']);

        $conversations = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)
                  ->whereDoesntHave('reads', fn($r) => $r->where('user_id', $user->id));
            }])
            ->with(['messages' => fn($q) => $q->with('user')->latest()->limit(1)])
            ->get()
            ->keyBy(fn($c) => $c->otherUser($user->id)->id);

        $directMessages = $peers->map(function ($peer) use ($conversations, $user) {
            $conv    = $conversations->get($peer->id);
            $lastMsg = $conv?->messages->first();

            return [
                'type'         => 'dm',
                'id'           => $peer->id,
                'name'         => $peer->name,
                'avatar'       => $peer->avatar ? Storage::url($peer->avatar) : null,
                'initials'     => strtoupper(mb_substr($peer->name, 0, 2)),
                'unread_count' => $conv->unread_count ?? 0,
                'last_message' => $lastMsg ? [
                    'body'      => $lastMsg->trashed() ? '[pesan dihapus]' : ($lastMsg->body ?: ''),
                    'is_mine'   => $lastMsg->user_id === $user->id,
                    'user_name' => $lastMsg->user?->name ?? 'User',
                    'time'      => $lastMsg->created_at->diffForHumans(),
                    'timestamp' => $lastMsg->created_at->timestamp,
                ] : null,
            ];
        });

        // ── Forum ────────────────────────────────────────────────────────
        $forumQuery = Forum::where('company_id', $user->company_id);
        if (!$user->hasRole(['admin', 'manager'])) {
            $forumQuery->whereHas('members', fn($q) => $q->where('user_id', $user->id));
        }

        $forums = $forumQuery
            ->withCount(['messages as unread_count' => function ($q) use ($user) {
                $q->where('user_id', '!=', $user->id)
                  ->whereDoesntHave('reads', fn($r) => $r->where('user_id', $user->id));
            }])
            ->withCount('members')
            ->with(['messages' => fn($q) => $q->with('user')->latest()->limit(1)])
            ->get()
            ->map(fn($f) => [
                'type'         => 'forum',
                'id'           => $f->id,
                'name'         => $f->name,
                'avatar'       => null,
                'initials'     => strtoupper(mb_substr($f->name, 0, 2)),
                'unread_count' => $f->unread_count,
                'member_count' => $f->members_count,
                'last_message' => $f->messages->first() ? [
                    'body'      => $f->messages->first()->trashed() ? '[pesan dihapus]' : ($f->messages->first()->body ?: ''),
                    'is_mine'   => $f->messages->first()->user_id === $user->id,
                    'user_name' => $f->messages->first()->user?->name ?? 'User',
                    'time'      => $f->messages->first()->created_at->diffForHumans(),
                    'timestamp' => $f->messages->first()->created_at->timestamp,
                ] : null,
            ]);

        return view('chat.index', [
            'projects'  => $projects->values(),
            'dms'       => $directMessages->values(),
            'forums'    => $forums->values(),
            'allPeers'  => $peers,
        ]);
    }

    public function messages(Request $request, Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $after  = $request->integer('after', 0);
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
            'messages' => $messages->map(fn($m) => $this->formatMessage($m, $userId, $readIds)),
        ]);
    }

    private function formatMessage(ProjectMessage $m, int $userId, $readIds = null): array
    {
        $reactionGroups = $m->reactions
            ->groupBy('emoji')
            ->map(fn($group, $emoji) => [
                'emoji'   => $emoji,
                'count'   => $group->count(),
                'users'   => $group->pluck('user.name')->filter()->values()->toArray(),
                'reacted' => $group->contains('user_id', $userId),
            ])->values()->toArray();

        return [
            'id'             => $m->id,
            'body'           => $m->trashed() ? '' : $m->body,
            'formatted_body' => $m->trashed() ? '' : $this->highlightMentions($m->body),
            'created_at'     => $m->created_at->format('d M, H:i'),
            'edited_at'      => $m->edited_at?->format('d M, H:i'),
            'deleted'        => $m->trashed(),
            'is_mine'        => $m->user_id === $userId,
            'user'           => [
                'id'       => $m->user?->id,
                'name'     => $m->user?->name ?? 'Deleted User',
                'avatar'   => $m->user?->avatar ? Storage::url($m->user->avatar) : null,
                'initials' => $m->user ? strtoupper(mb_substr($m->user->name, 0, 2)) : '??',
            ],
            'parent'         => $m->parent ? [
                'id'   => $m->parent->id,
                'body' => $m->parent->trashed() ? '[Pesan dihapus]' : Str::limit($m->parent->body, 80),
                'user' => $m->parent->user?->name ?? 'User',
            ] : null,
            'reactions'      => $reactionGroups,
            'attachments'    => $m->attachments->map(fn($a) => [
                'id'       => $a->id,
                'name'     => $a->file_name,
                'url'      => $a->url(),
                'is_image' => $a->isImage(),
                'size'     => $this->formatSize($a->file_size),
            ])->toArray(),
            'read_by_me'     => $readIds !== null ? $readIds->has($m->id) : false,
        ];
    }

    private function highlightMentions(string $body): string
    {
        $escaped = htmlspecialchars($body, ENT_QUOTES, 'UTF-8');
        return preg_replace(
            '/@([\w.]+)/',
            '<span class="font-semibold text-indigo-600">@$1</span>',
            $escaped
        );
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }

    public function store(Request $request, Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $request->validate([
            'body'      => 'nullable|string|max:5000',
            'parent_id' => 'nullable|exists:project_messages,id',
            'files'     => 'nullable|array|max:5',
            'files.*'   => 'file|max:10240',
        ]);

        if (!$request->filled('body') && !$request->hasFile('files')) {
            return response()->json(['error' => 'Pesan kosong.'], 422);
        }

        $message = ProjectMessage::create([
            'project_id' => $project->id,
            'user_id'    => Auth::id(),
            'parent_id'  => $request->parent_id,
            'body'       => $request->body ?? '',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("chat-attachments/{$project->id}", 'public');
                MessageAttachment::create([
                    'message_id' => $message->id,
                    'file_name'  => $file->getClientOriginalName(),
                    'file_path'  => $path,
                    'mime_type'  => $file->getMimeType(),
                    'file_size'  => $file->getSize(),
                ]);
            }
        }

        // Notify @mentions
        preg_match_all('/@([\w.]+)/', $message->body, $matches);
        if (!empty($matches[1])) {
            User::whereIn('name', $matches[1])->get()
                ->each(function (User $mentioned) use ($project, $message) {
                    if ($mentioned->id === Auth::id()) return;
                    $this->notifier->send(
                        $mentioned->id,
                        'chat_mention',
                        'Anda disebut di chat proyek',
                        Auth::user()->name . " menyebut Anda di chat \"{$project->name}\"",
                        ['project_id' => $project->id, 'message_id' => $message->id]
                    );
                });
        }

        // Notify other project members (excluding sender & already mentioned)
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
                Auth::user()->name . ': ' . Str::limit($message->body ?: '[file]', 80),
                ['project_id' => $project->id, 'message_id' => $message->id]
            );
        }

        // Auto-read for sender
        MessageRead::insertOrIgnore([[
            'message_id' => $message->id,
            'user_id'    => Auth::id(),
            'read_at'    => now()->toDateTimeString(),
        ]]);

        $message->load(['user', 'reactions.user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ], 201);
    }

    public function update(Request $request, Project $project, ProjectMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $request->validate(['body' => 'required|string|max:5000']);

        $message->update(['body' => $request->body, 'edited_at' => now()]);
        $message->load(['user', 'reactions.user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ]);
    }

    public function destroy(Project $project, ProjectMessage $message)
    {
        abort_unless(
            $message->user_id === Auth::id() || Auth::user()->hasRole(['admin', 'manager']),
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
            'user_id'    => Auth::id(),
            'emoji'      => $request->emoji,
        ])->first();

        if ($existing) {
            $existing->delete();
        } else {
            MessageReaction::create([
                'message_id' => $message->id,
                'user_id'    => Auth::id(),
                'emoji'      => $request->emoji,
            ]);
        }

        $message->load('reactions.user');
        $reactions = $message->reactions
            ->groupBy('emoji')
            ->map(fn($group, $emoji) => [
                'emoji'   => $emoji,
                'count'   => $group->count(),
                'users'   => $group->pluck('user.name')->filter()->values()->toArray(),
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
            $now  = now()->toDateTimeString();
            $rows = $unreadIds->map(fn($id) => [
                'message_id' => $id,
                'user_id'    => Auth::id(),
                'read_at'    => $now,
            ])->toArray();
            MessageRead::insertOrIgnore($rows);
        }

        return response()->json(['ok' => true]);
    }

    public function unreadCount()
    {
        $user = Auth::user();

        $projectIds = $user->hasRole(['admin', 'manager'])
            ? Project::pluck('id')
            : Project::where('manager_id', $user->id)
                ->orWhere('client_id', $user->id)
                ->orWhereHas('members', fn($q) => $q->where('user_id', $user->id))
                ->pluck('id');

        $projectUnread = ProjectMessage::withTrashed()
            ->whereIn('project_id', $projectIds)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->count();

        $conversationIds = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->pluck('id');

        $dmUnread = DirectMessage::withTrashed()
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->count();

        $forumIds = $user->hasRole(['admin', 'manager'])
            ? Forum::where('company_id', $user->company_id)->pluck('id')
            : Forum::whereHas('members', fn($q) => $q->where('user_id', $user->id))->pluck('id');

        $forumUnread = \App\Models\ForumMessage::withTrashed()
            ->whereIn('forum_id', $forumIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->count();

        return response()->json(['total' => $projectUnread + $dmUnread + $forumUnread]);
    }

    public function members(Project $project)
    {
        abort_unless($this->canAccess($project), 403);

        $members = User::whereIn('id',
            $project->members()->pluck('user_id')->push($project->manager_id)->filter()->unique()
        )->select('id', 'name')->get();

        return response()->json($members);
    }
}
