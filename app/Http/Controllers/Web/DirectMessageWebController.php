<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\DirectMessage;
use App\Models\DirectMessageRead;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
            ->with(['user', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

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

    public function store(Request $request, User $peer)
    {
        $this->assertPeer($peer);

        $request->validate([
            'body'      => 'required|string|max:5000',
            'parent_id' => 'nullable|exists:direct_messages,id',
        ]);

        $conversation = Conversation::between(Auth::user(), $peer);

        $message = DirectMessage::create([
            'conversation_id' => $conversation->id,
            'user_id'         => Auth::id(),
            'parent_id'       => $request->parent_id,
            'body'            => $request->body,
        ]);

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
            Str::limit($message->body, 80),
            ['conversation_id' => $conversation->id, 'from_user_id' => Auth::id()],
            push: true,
        );

        $message->load(['user', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

        return response()->json([
            'message' => $this->formatMessage($message, Auth::id()),
        ], 201);
    }

    public function update(Request $request, User $peer, DirectMessage $message)
    {
        abort_unless($message->user_id === Auth::id(), 403);
        $request->validate(['body' => 'required|string|max:5000']);

        $message->update(['body' => $request->body, 'edited_at' => now()]);
        $message->load(['user', 'parent' => fn($q) => $q->withTrashed()->with('user')]);

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

    private function formatMessage(DirectMessage $m, int $userId, $readIds = null): array
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
