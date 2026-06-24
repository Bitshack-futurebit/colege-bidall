<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotification;
use App\Models\Auctioneer;
use App\Models\PushNotification;
use App\Models\PushSubscription;
use App\Models\User;
use Illuminate\Http\Request;

class PushNotificationController extends Controller
{
    public function index()
    {
        $notifications = PushNotification::orderByDesc('created_at')->paginate(20);

        $audienceCounts = [
            'all_users' => PushSubscription::count(),
            'all_bidders' => PushSubscription::whereHas('user', fn ($q) => $q->where('role', 'bidder'))->count(),
            'all_auctioneers' => PushSubscription::whereHas('user', fn ($q) => $q->where('role', 'auctioneer'))->count(),
            'all_admins' => PushSubscription::whereHas('user', fn ($q) => $q->where('role', 'admin'))->count(),
        ];

        $auctioneers = Auctioneer::with('user')->orderBy('business_name')->get();

        return view('admin.notifications', compact('notifications', 'audienceCounts', 'auctioneers'));
    }

    public function send(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:100',
            'body' => 'required|string|max:1000',
            'url' => 'nullable|string|max:255',
            'audience' => 'required|in:all_users,all_bidders,all_auctioneers,all_admins,followers',
            'auctioneer_id' => 'required_if:audience,followers|nullable|exists:auctioneers,id',
        ]);

        $notification = PushNotification::create([
            'sender_type' => 'admin',
            'sender_id' => $request->user()->id,
            'auctioneer_id' => $request->input('audience') === 'followers' ? $request->input('auctioneer_id') : null,
            'audience' => $request->input('audience'),
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

        return back()->with('success', 'Notification sent.');
    }
}
