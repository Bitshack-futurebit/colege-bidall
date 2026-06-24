<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushNotification;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function unread(Request $request)
    {
        $user = $request->user();

        $notifications = PushNotification::forUser($user)
            ->whereDoesntHave('readBy', fn ($q) => $q->where('user_id', $user->id))
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn ($n) => $this->formatNotification($n));

        return response()->json([
            'count' => $notifications->count(),
            'notifications' => $notifications,
        ]);
    }

    public function history(Request $request)
    {
        $user = $request->user();

        $readIds = $user->readNotifications()
            ->pluck('push_notification_id')
            ->flip();

        $notifications = PushNotification::forUser($user)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(fn ($n) => $this->formatNotification($n) + [
                'date' => $n->created_at->format('d M Y, H:i'),
                'read' => $readIds->has($n->id),
            ]);

        return response()->json($notifications);
    }

    private function formatNotification(PushNotification $n): array
    {
        return [
            'id' => $n->id,
            'title' => $n->title,
            'body' => $n->body,
            'type' => $n->sender_type === 'admin' ? 'info' : 'success',
            'link' => $n->url,
            'time' => $n->created_at->diffForHumans(),
        ];
    }

    public function markRead(Request $request, PushNotification $pushNotification)
    {
        $pushNotification->readBy()->syncWithoutDetaching([
            $request->user()->id => ['read_at' => now()],
        ]);

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request)
    {
        $user = $request->user();
        $unread = PushNotification::forUser($user)
            ->whereDoesntHave('readBy', fn ($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        $data = $unread->mapWithKeys(fn ($id) => [$id => ['read_at' => now()]])->toArray();
        $user->readNotifications()->syncWithoutDetaching($data);

        return response()->json(['ok' => true]);
    }
}
