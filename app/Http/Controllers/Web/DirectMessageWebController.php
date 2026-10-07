<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\DirectMessageAttachment;
use App\Models\DirectMessageRead;
use App\Models\Project;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DirectMessageWebController extends Controller
{
    public function __construct(private NotificationService $notifier) {}

    private function assertPeer(User $peer): void
    {
        abort_if($peer->id === Auth::id(), 404);
        abort_unless($peer->company_id === Auth::user()->company_id, 404);
        abort_unless($peer->is_active, 404);
    }

    public function messages(Request $request, User $peer)
    {
        $this->assertPeer($peer);
        $conversation = Conversation::between(Auth::user(), $peer);

        $after  = $request->integer('after', 0);
        $userId = Auth::id();

        $query = DirectMessage::withTrashed()
            ->where('conversation_id', $conversation->id)
            ->with(['user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        if ($after > 0) {
            $messages = $query->where('id', '>', $after)->oldest()->get();
        } else {
            $messages = $query->oldest()->limit(80)->get();
        }

        $readIds = DirectMessageRead::where('user_id', $userId)
            ->whereIn('message_id', $messages->pluck('id'))
            ->pluck('message_id')
            ->flip();

        return response()->json([
            'conversation_id' => $conversation->id,
            'messages'        => $messages->map(fn($m) => $this->formatMessage($m, $userId, $readIds)),
        ]);
    }

    public function details(User $peer)
    {
        $this->assertPeer($peer);
        $user = Auth::user();
        $conversation = Conversation::between($user, $peer);

        // Shared projects between user and peer
        $sharedProjects = Project::query()
            ->where(function ($q) use ($user) {
                $q->where('manager_id', $user->id)
                  ->orWhere('client_id', $user->id)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $user->id));
            })
            ->where(function ($q) use ($peer) {
                $q->where('manager_id', $peer->id)
                  ->orWhere('client_id', $peer->id)
                  ->orWhereHas('members', fn($m) => $m->where('user_id', $peer->id));
            })
            ->get(['id', 'name', 'slug', 'status'])
            ->map(fn($p) => [
                'id'     => $p->id,
                'name'   => $p->name,
                'slug'   => $p->slug,
                'status' => $p->status ?: 'active',
            ]);

        // Pinned messages in this conversation
        $pinnedMessages = DirectMessage::withTrashed()
            ->where('conversation_id', $conversation->id)
            ->where('is_pinned', true)
            ->with('user')
            ->latest('pinned_at')
            ->get()
            ->map(fn($m) => [
                'id'        => $m->id,
                'body'      => $m->trashed() ? '[Pesan dihapus]' : Str::limit($m->body, 90),
                'user_name' => $m->user?->name ?? 'User',
                'time'      => $m->created_at->format('H:i'),
                'date'      => $m->created_at->format('d M Y'),
            ]);

        // Files shared in direct message
        $files = DirectMessageAttachment::whereHas('message', function ($q) use ($conversation) {
            $q->where('conversation_id', $conversation->id);
        })
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

        $isClient = $peer->hasRole('client');
        $roleTitle = $isClient ? 'Client' : 'Member';

        return response()->json([
            'peer' => [
                'id'              => $peer->id,
                'name'            => $peer->name,
                'email'           => $peer->email,
                'avatar'          => $peer->avatar ? Storage::url($peer->avatar) : null,
                'initials'        => strtoupper(mb_substr($peer->name, 0, 2)),
                'role_title'      => $roleTitle,
                'department'      => $peer->department ?? null,
                'shared_projects' => $sharedProjects,
            ],
            'pinned_messages' => $pinnedMessages,
            'files'           => $files,
        ]);
    }

    public function togglePin(User $peer, DirectMessage $message)
    {
        $this->assertPeer($peer);
        $conversation = Conversation::between(Auth::user(), $peer);
        abort_unless($message->conversation_id === $conversation->id, 403);

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

    public function store(Request $request, User $peer)
    {
        $this->assertPeer($peer);

        $request->validate([
            'body'      => 'nullable|string|max:5000',
            'parent_id' => 'nullable|exists:direct_messages,id',
            'files'     => 'nullable|array|max:5',
            'files.*'   => 'file|max:2048', // Max 2 MB
        ]);

        if (!$request->filled('body') && !$request->hasFile('files')) {
            return response()->json(['error' => 'Pesan kosong.'], 422);
        }

        $conversation = Conversation::between(Auth::user(), $peer);

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'user_id'         => Auth::id(),
            'parent_id'       => $request->parent_id,
            'body'            => $request->body ?? '',
        ]);

        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store("chat-attachments/dm/{$conversation->id}", 'public');
                DirectMessageAttachment::create([
                    'message_id' => $message->id,
                    'file_name'  => $file->getClientOriginalName(),
                    'file_path'  => $path,
                    'mime_type'  => $file->getMimeType(),
                    'file_size'  => $file->getSize(),
                ]);
            }
        }

        $conversation->update(['last_message_at' => now()]);

        DirectMessageRead::insertOrIgnore([[
            'message_id' => $message->id,
            'user_id'    => Auth::id(),
            'read_at'    => now()->toDateTimeString(),
        ]]);

        $this->notifier->send(
            $peer->id,
            'direct_message',
            'Pesan baru dari ' . Auth::user()->name,
            Str::limit($message->body ?: '[Berkas]', 80),
            ['conversation_id' => $conversation->id, 'from_user_id' => Auth::id()],
            push: true,
        );

        $message->load(['user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ], 201);
    }

    public function update(Request $request, User $peer, DirectMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $request->validate(['body' => 'required|string|max:5000']);

        $message->update(['body' => $request->body, 'edited_at' => now()]);
        $message->load(['user', 'attachments', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ]);
    }

    public function destroy(User $peer, DirectMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $message->delete();

        return response()->json(['ok' => true]);
    }

    public function markRead(User $peer)
    {
        $this->assertPeer($peer);
        $conversation = Conversation::between(Auth::user(), $peer);

        $unreadIds = DirectMessage::withTrashed()
            ->where('conversation_id', $conversation->id)
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
            DirectMessageRead::insertOrIgnore($rows);
        }

        return response()->json(['ok' => true]);
    }

    public function unreadCount()
    {
        $user = Auth::user();

        $conversationIds = Conversation::where('user_one_id', $user->id)
            ->orWhere('user_two_id', $user->id)
            ->pluck('id');

        $total = DirectMessage::withTrashed()
            ->whereIn('conversation_id', $conversationIds)
            ->where('user_id', '!=', $user->id)
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->count();

        return response()->json(['total' => $total]);
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

    private function formatMessage(DirectMessage $m, int $userId, $readIds = null): array
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
