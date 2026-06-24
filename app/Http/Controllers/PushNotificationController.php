<?php

namespace App\Http\Controllers;

use App\Jobs\SendPushNotification;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    /**
     * Save a browser push subscription.
     */
    public function subscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|url|max:2048',
            'keys.p256dh' => 'required|string',
            'keys.auth' => 'required|string',
        ]);

        $endpointHash = hash('sha256', $request->input('endpoint'));

        // Shared-device guard: one browser endpoint can only belong to one user at a time.
        // Kick any other user off this endpoint before claiming it.
        PushSubscription::where('endpoint_hash', $endpointHash)
            ->where('user_id', '!=', $request->user()->id)
            ->delete();

        $sub = PushSubscription::updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'endpoint_hash' => $endpointHash,
            ],
            [
                'endpoint' => $request->input('endpoint'),
                'p256dh_key' => $request->input('keys.p256dh'),
                'auth_token' => $request->input('keys.auth'),
            ]
        );

        \Log::info('Push subscribe: user_id=' . $request->user()->id
            . ' sub_id=' . $sub->id
            . ' endpoint=' . substr($request->input('endpoint'), 0, 80));

        return response()->json(['success' => true]);
    }

    /**
     * Remove a browser push subscription.
     */
    public function unsubscribe(Request $request)
    {
        $request->validate([
            'endpoint' => 'required|string',
        ]);

        PushSubscription::where('user_id', $request->user()->id)
            ->where('endpoint_hash', hash('sha256', $request->input('endpoint')))
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * Seller: notification history.
     */
    public function sellerIndex(Request $request)
    {
        $auctioneer = $request->user()->auctioneer;

        $notifications = PushNotification::where('sender_type', 'auctioneer')
            ->where('sender_id', $request->user()->id)
            ->orderByDesc('created_at')
            ->paginate(20);

        $followerCount = $auctioneer->followers()->count();

        return view('seller.notifications', compact('notifications', 'followerCount', 'auctioneer'));
    }

    /**
     * Seller: send notification to followers.
     */
    public function sellerSend(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:1000',
            'url' => 'nullable|string|max:255',
        ]);

        $auctioneer = $request->user()->auctioneer;

        $notification = PushNotification::create([
            'sender_type' => 'auctioneer',
            'sender_id' => $request->user()->id,
            'auctioneer_id' => $auctioneer->id,
            'audience' => 'followers',
            'title' => $request->input('title'),
            'body' => $request->input('body'),
            'url' => $request->input('url'),
        ]);

        // Attempt browser push delivery (best-effort, may fail on shared hosting)
        try {
            SendPushNotification::dispatch($notification);
        } catch (\Throwable $e) {
            // Browser push failed — in-app bell notification still works
        }

        return back()->with('success', 'Notification sent to your followers.');
    }
}
