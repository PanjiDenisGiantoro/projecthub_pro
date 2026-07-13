<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $request->user()->updatePushSubscription(
            endpoint: $request->input('endpoint'),
            key: $request->input('keys.p256dh'),
            token: $request->input('keys.auth'),
        );

        return response()->json(['message' => 'Subscribed.']);
    }

    public function destroy(Request $request)
    {
        $request->validate(['endpoint' => 'required|string']);

        $request->user()->deletePushSubscription($request->input('endpoint'));

        return response()->json(['message' => 'Unsubscribed.']);
    }
}
