<?php

namespace App\Http\Controllers;

use App\Models\PhNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->phNotifications()
            ->when($request->unread_only, fn($q) => $q->unread())
            ->latest()
            ->paginate(30);

        return response()->json($notifications);
    }

    public function markRead(Request $request, PhNotification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            abort(403);
        }

        $notification->update(['read_at' => now()]);

        return response()->json($notification);
    }

    public function markAllRead(Request $request)
    {
        $request->user()->phNotifications()->unread()->update(['read_at' => now()]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function unreadCount(Request $request)
    {
        // Dibuka langsung lewat address bar (bukan dipanggil via fetch oleh aplikasi)
        if ($request->header('Sec-Fetch-Mode') === 'navigate') {
            return redirect()->route('dashboard');
        }

        return response()->json([
            'count' => $request->user()->phNotifications()->unread()->count(),
        ]);
    }
}
