<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Forum;
use App\Models\ForumMessage;
use App\Models\ForumMessageAttachment;
use App\Models\ForumMessageRead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForumWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    private function canAccess(Forum $forum): bool
    {
        $user = Auth::user();
        if ($user->is_super_admin) return true;
        return $forum->members()->where('user_id', $user->id)->exists();
    }

    private function canManage(Forum $forum): bool
    {
        $user = Auth::user();
        return $user->is_super_admin || $forum->created_by === $user->id;
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'         => 'required|string|max:150',
            'description'  => 'nullable|string|max:500',
            'member_ids'   => 'nullable|array',
            'member_ids.*' => 'exists:users,id',
        ]);

        $user = Auth::user();

        // Strip leading hash if user typed it
        $cleanName = ltrim(trim($request->name), '#');

        $forum = Forum::create([
            'name'        => $cleanName,
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
                'Ditambahkan ke Kanal Forum',
                $user->name . " menambahkan Anda ke forum \"#{$forum->name}\".",
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
                'Ditambahkan ke Kanal Forum',
                Auth::user()->name . " menambahkan Anda ke forum \"#{$forum->name}\".",
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

        $members = $forum->members()->select('users.id', 'users.name', 'users.avatar')->get()
            ->map(fn($u) => [
                'id'       => $u->id,
                'name'     => $u->name,
                'avatar'   => $u->avatar ? Storage::url($u->avatar) : null,
                'initials' => strtoupper(mb_substr($u->name, 0, 2)),
                'role'     => $u->id === $forum->created_by ? 'Lead' : 'Member',
            ]);

        return response()->json($members);
    }

    public function details(Forum $forum)
    {
        abort_unless($this->canAccess($forum), 403);

        $forum->load(['creator', 'members']);

        $pinnedMessages = ForumMessage::withTrashed()
            ->where('forum_id', $forum->id)
            ->where('is_pinned', true)
            ->with('user')
            ->latest('pinned_at')
            ->get()
            ->map(fn($m) => [
                'id'        => $m->id,
                'user_id'   => $m->user_id,
                'body'      => $m->trashed() ? '[Pesan dihapus]' : Str::limit($m->body, 90),
                'user_name' => $m->user?->name ?? 'User',
                'time'      => $m->created_at->format('H:i'),
                'date'      => $m->created_at->format('d M Y'),
            ]);

        $files = ForumMessageAttachment::whereHas('message', fn($q) => $q->where('forum_id', $forum->id))
            ->latest()
            ->get()
            ->map(fn($a) => [
                'id'         => $a->id,
                'name'       => $a->file_name,
                'url'        => $a->url(),
                'size'       => $this->formatSize($a->file_size),
                'extension'  => strtolower(pathinfo($a->file_name, PATHINFO_EXTENSION)),
                'is_image'   => $a->isImage(),
                'created_at' => $a->created_at->format('d M Y'),
                'date_human' => $a->created_at->diffForHumans(),
            ]);

        $members = $forum->members->map(fn($u) => [
            'id'       => $u->id,
            'name'     => $u->name,
            'avatar'   => $u->avatar ? Storage::url($u->avatar) : null,
            'initials' => strtoupper(mb_substr($u->name, 0, 2)),
            'role'     => $u->id === $forum->created_by ? 'Lead' : 'Member',
        ]);

        return response()->json([
            'forum' => [
                'id'           => $forum->id,
                'name'         => $forum->name,
                'description'  => $forum->description ?: 'Ruang diskusi publik untuk koordinasi tim Flovig.',
                'creator_name' => $forum->creator?->name ?? 'Flovig Team',
                'channel_type' => 'Publik Tim',
                'status'       => 'active',
                'member_count' => $members->count(),
            ],
            'pinned_messages' => $pinnedMessages,
            'files'           => $files,
            'members'         => $members->values(),
        ]);
    }

    public function togglePin(Forum $forum, ForumMessage $message)
    {
        abort_unless($this->canAccess($forum), 403);
        abort_unless($message->forum_id === $forum->id, 403);

        $newPinned = !$message->is_pinned;
        $message->update([
            'is_pinned' => $newPinned,
            'pinned_by' => $newPinned ? Auth::id() : null,
            'pinned_at' => $newPinned ? now() : null,
        ]);

        return response()->json([
            'is_pinned'  => $newPinned,
            'message_id' => $message->id,
        ]);
    }

    public function messages(Request $request, Forum $forum)
    {
        abort_unless($this->canAccess($forum), 403);

        $after  = $request->integer('after', 0);
        $userId = Auth::id();

        $query = ForumMessage::withTrashed()
            ->where('forum_id', $forum->id)
            ->with(['user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

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
            'body'      => 'nullable|string|max:5000',
            'parent_id' => 'nullable|exists:forum_messages,id',
            'files'     => 'nullable|array|max:5',
            'files.*'   => 'file|max:2048', // Max 2 MB
        ]);

        if (!$request->filled('body') && !$request->hasFile('files')) {
            return response()->json(['error' => 'Pesan kosong.'], 422);
        }

        $message = ForumMessage::create([
            'forum_id'  => $forum->id,
            'user_id'   => Auth::id(),
            'parent_id' => $request->parent_id,
            'body'      => $request->body ?? '',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("chat-attachments/forum/{$forum->id}", 'public');
                ForumMessageAttachment::create([
                    'message_id' => $message->id,
                    'file_name'  => $file->getClientOriginalName(),
                    'file_path'  => $path,
                    'mime_type'  => $file->getMimeType(),
                    'file_size'  => $file->getSize(),
                ]);
            }
        }

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
                'Pesan baru di #' . $forum->name,
                Auth::user()->name . ': ' . Str::limit($message->body ?: '[Berkas]', 80),
                ['forum_id' => $forum->id, 'message_id' => $message->id],
            );
        }

        $message->load(['user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ], 201);
    }

    public function update(Request $request, Forum $forum, ForumMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $request->validate(['body' => 'required|string|max:5000']);

        $message->update(['body' => $request->body, 'edited_at' => now()]);
        $message->load(['user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

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

    private function formatSize(int $bytes): string
    {
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1048576) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
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

    private function formatForum(Forum $f, int $userId): array
    {
        return [
            'type'         => 'forum',
            'id'           => $f->id,
            'name'         => $f->name,
            'description'  => $f->description ?: 'Ruang diskusi publik untuk koordinasi tim Flovig.',
            'creator_name' => $f->creator?->name ?? 'Flovig Team',
            'avatar'       => null,
            'initials'     => strtoupper(mb_substr($f->name, 0, 2)),
            'unread_count' => 0,
            'member_count' => $f->members()->count(),
            'last_message' => null,
        ];
    }

    private function formatMessage(ForumMessage $m, int $userId, $readIds = null): array
    {
        return [
            'id'             => $m->id,
            'body'           => $m->trashed() ? '' : $m->body,
            'formatted_body' => $m->trashed() ? '' : $this->highlightMentions($m->body),
            'created_at'     => $m->created_at->toIso8601String(),
            'time_str'       => $m->created_at->format('H:i'),
            'date_str'       => $m->created_at->format('d M Y'),
            'edited_at'      => $m->edited_at?->format('d M, H:i'),
            'deleted'        => $m->trashed(),
            'is_mine'        => $m->user_id === $userId,
            'is_pinned'      => (bool) $m->is_pinned,
            'pinned_at'      => $m->pinned_at?->format('d M, H:i'),
            'user'           => [
                'id'       => $m->user?->id,
                'name'     => $m->user?->name ?? 'Deleted User',
                'avatar'   => $m->user?->avatar ? Storage::url($m->user->avatar) : null,
                'initials' => $m->user ? strtoupper(mb_substr($m->user->name, 0, 2)) : '??',
            ],
            'parent'         => $m->parent ? [
                'id'      => $m->parent->id,
                'user_id' => $m->parent->user_id,
                'body'    => $m->parent->trashed() ? '[Pesan dihapus]' : Str::limit($m->parent->body, 80),
                'user'    => $m->parent->user?->name ?? 'User',
            ] : null,
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
}
