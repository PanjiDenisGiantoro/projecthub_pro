<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumMessage;
use App\Models\ForumMessageRead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ForumWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    private function canAccess(Forum $forum): bool
    {
        $user = Auth::user();
        if ($user->hasRole(['admin', 'manager'])) return true;
        return $forum->members()->where('user_id', $user->id)->exists();
    }

    private function canManage(Forum $forum): bool
    {
        $user = Auth::user();
        return $user->hasRole(['admin', 'manager']) || $forum->created_by === $user->id;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:150',
            'description' => 'nullable|string|max:500',
            'member_ids'  => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $user = Auth::user();

        $forum = Forum::create([
            'name'        => $request->name,
            'description' => $request->description,
            'company_id'  => $user->company_id,
            'created_by'  => $user->id,
        ]);

        $memberIds = User::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->whereIn('id', $request->member_ids ?? [])
            ->pluck('id')
            ->push($user->id)
            ->unique();

        $forum->members()->sync($memberIds);

        foreach ($memberIds as $uid) {
            if ($uid === $user->id) continue;
            $this->notifier->send(
                $uid,
                'forum_invite',
                'Ditambahkan ke Forum',
                $user->name . " menambahkan Anda ke forum \"{$forum->name}\".",
                ['forum_id' => $forum->id],
            );
        }

        return response()->json([
            'forum' => $this->formatForum($forum->fresh(), $user->id),
        ], 201);
    }

    public function addMember(Request $request, Forum $forum)
    {
        abort_unless($this->canManage($forum), 403);
        $request->validate(['user_id' => 'required|exists:users,id']);

        $peer = User::findOrFail($request->user_id);
        abort_unless($peer->company_id === $forum->company_id && $peer->is_active, 422);

        $forum->members()->syncWithoutDetaching([$peer->id]);

        if ($peer->id !== Auth::id()) {
            $this->notifier->send(
                $peer->id,
                'forum_invite',
                'Ditambahkan ke Forum',
                Auth::user()->name . " menambahkan Anda ke forum \"{$forum->name}\".",
                ['forum_id' => $forum->id],
            );
        }

        return response()->json(['ok' => true]);
    }

    public function removeMember(Forum $forum, User $user)
    {
        abort_unless($this->canManage($forum) || $user->id === Auth::id(), 403);
        $forum->members()->detach($user->id);

        return response()->json(['ok' => true]);
    }

    public function members(Forum $forum)
    {
        abort_unless($this->canAccess($forum), 403);

        return response()->json(
            $forum->members()->select('users.id', 'users.name', 'users.avatar')->get()
        );
    }

    public function messages(Request $request, Forum $forum)
    {
        abort_unless($this->canAccess($forum), 403);

        $after  = $request->integer('after', 0);
        $userId = Auth::id();

        $query = ForumMessage::withTrashed()
            ->where('forum_id', $forum->id)
            ->with(['user', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        if ($after > 0) {
            $messages = $query->where('id', '>', $after)->oldest()->get();
        } else {
            $messages = $query->oldest()->limit(80)->get();
        }

        $readIds = ForumMessageRead::where('user_id', $userId)
            ->whereIn('message_id', $messages->pluck('id'))
            ->pluck('message_id')
            ->flip();

        return response()->json([
            'messages' => $messages->map(fn($m) => $this->formatMessage($m, $userId, $readIds)),
        ]);
    }

    public function storeMessage(Request $request, Forum $forum)
    {
        abort_unless($this->canAccess($forum), 403);

        $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:forum_messages,id',
        ]);

        $message = ForumMessage::create([
            'forum_id'  => $forum->id,
            'user_id'   => Auth::id(),
            'parent_id' => $request->parent_id,
            'body'      => $request->body,
        ]);

        $forum->update(['last_message_at' => now()]);

        ForumMessageRead::insertOrIgnore([[
            'message_id' => $message->id,
            'user_id'    => Auth::id(),
            'read_at'    => now()->toDateTimeString(),
        ]]);

        $memberIds = $forum->members()->pluck('users.id')->reject(fn($id) => $id === Auth::id());
        foreach ($memberIds as $uid) {
            $this->notifier->send(
                $uid,
                'forum_message',
                'Pesan baru di ' . $forum->name,
                Auth::user()->name . ': ' . Str::limit($message->body, 80),
                ['forum_id' => $forum->id, 'message_id' => $message->id],
            );
        }

        $message->load(['user', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ], 201);
    }

    public function update(Request $request, Forum $forum, ForumMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $request->validate(['body' => 'required|string|max:5000']);

        $message->update(['body' => $request->body, 'edited_at' => now()]);
        $message->load(['user', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ]);
    }

    public function destroy(Forum $forum, ForumMessage $message)
    {
        abort_unless(
            $message->user_id === Auth::id() || $this->canManage($forum),
            403
        );
        $message->delete();

        return response()->json(['ok' => true]);
    }

    public function markRead(Forum $forum)
    {
        abort_unless($this->canAccess($forum), 403);

        $unreadIds = ForumMessage::withTrashed()
            ->where('forum_id', $forum->id)
            ->where('user_id', '!=', Auth::id())
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', Auth::id()))
            ->pluck('id');

        if ($unreadIds->isNotEmpty()) {
            $now  = now()->toDateTimeString();
            $rows = $unreadIds->map(fn($id) => [
                'message_id' => $id,
                'user_id'    => Auth::id(),
                'read_at'    => $now,
            ])->toArray();
            ForumMessageRead::insertOrIgnore($rows);
        }

        return response()->json(['ok' => true]);
    }

    private function formatForum(Forum $forum, int $userId): array
    {
        return [
            'id'           => $forum->id,
            'name'         => $forum->name,
            'description'  => $forum->description,
            'member_count' => $forum->members()->count(),
        ];
    }

    private function formatMessage(ForumMessage $m, int $userId, $readIds = null): array
    {
        return [
            'id'             => $m->id,
            'body'           => $m->trashed() ? '' : $m->body,
            'formatted_body' => $m->trashed() ? '' : e($m->body),
            'created_at'     => $m->created_at->format('d M, H:i'),
            'edited_at'      => $m->edited_at?->format('d M, H:i'),
            'deleted'        => $m->trashed(),
            'is_mine'        => $m->user_id === $userId,
            'user'           => [
                'id'       => $m->user?->id,
                'name'     => $m->user?->name ?? 'Deleted User',
                'avatar'   => $m->user?->avatar ? \Illuminate\Support\Facades\Storage::url($m->user->avatar) : null,
                'initials' => $m->user ? strtoupper(mb_substr($m->user->name, 0, 2)) : '??',
            ],
            'parent'         => $m->parent ? [
                'id'   => $m->parent->id,
                'body' => $m->parent->trashed() ? '[Pesan dihapus]' : Str::limit($m->parent->body, 80),
                'user' => $m->parent->user?->name ?? 'User',
            ] : null,
            'read_by_me'     => $readIds !== null ? $readIds->has($m->id) : false,
        ];
    }
}
