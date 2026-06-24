<?php

namespace App\Jobs;

use App\Models\PushNotification;
use App\Models\PushSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class SendPushNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public PushNotification $notification
    ) {}

    public function handle(): void
    {
        $vapid = config('webpush.vapid');

        if (empty($vapid['public_key']) || empty($vapid['private_key'])) {
            \Log::warning('SendPushNotification: VAPID keys not configured');
            return;
        }

        $targetUserIds = $this->notification->getTargetUserIds();
        \Log::info('SendPushNotification: audience=' . $this->notification->audience
            . ' auctioneer_id=' . $this->notification->auctioneer_id
            . ' target_users=' . $targetUserIds->count()
            . ' user_ids=[' . $targetUserIds->implode(',') . ']');

        $subscriptions = PushSubscription::whereIn('user_id', $targetUserIds)->get();
        \Log::info('SendPushNotification: found ' . $subscriptions->count() . ' push subscriptions');

        if ($subscriptions->isEmpty()) {
            \Log::warning('SendPushNotification: no subscriptions found — nothing to send');
            return;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $vapid['subject'],
                'publicKey' => $vapid['public_key'],
                'privateKey' => $vapid['private_key'],
            ],
        ]);

        // Precompute each user's current unread count so the SW can set the app icon badge
        // (the red circle with a number on the home-screen icon, WhatsApp-style).
        $userIds = $subscriptions->pluck('user_id')->unique();
        $users = \App\Models\User::whereIn('id', $userIds)->get()->keyBy('id');
        $unreadCounts = [];
        foreach ($users as $uid => $user) {
            $unreadCounts[$uid] = \App\Models\PushNotification::forUser($user)
                ->whereDoesntHave('readBy', fn ($q) => $q->where('user_id', $uid))
                ->count();
        }

        foreach ($subscriptions as $sub) {
            // +1 because this new notification hasn't been recorded as read yet
            $unreadForUser = ($unreadCounts[$sub->user_id] ?? 0) + 1;

            $payload = json_encode([
                'title' => $this->notification->title,
                'body' => $this->notification->body,
                'url' => $this->notification->url ?? '/',
                'unread_count' => $unreadForUser,
            ]);

            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'publicKey' => $sub->p256dh_key,
                    'authToken' => $sub->auth_token,
                ]),
                $payload
            );
        }

        $sent = 0;
        $failed = 0;
        $expiredEndpoints = [];

        foreach ($webPush->flush() as $report) {
            if ($report->isSuccess()) {
                $sent++;
                \Log::info('SendPushNotification: OK — HTTP ' . ($report->getResponse()?->getStatusCode() ?? '?')
                    . ' endpoint=' . substr($report->getEndpoint(), 0, 80));
            } else {
                $failed++;
                \Log::warning('SendPushNotification: delivery failed — '
                    . $report->getReason()
                    . ' (HTTP ' . ($report->getResponse()?->getStatusCode() ?? 'null') . ')'
                    . ' endpoint=' . substr($report->getEndpoint(), 0, 80));

                // 410 Gone or 404 = subscription expired, clean it up
                $statusCode = $report->getResponse()?->getStatusCode();
                if (in_array($statusCode, [404, 410])) {
                    $expiredEndpoints[] = $report->getEndpoint();
                }
            }
        }

        // Clean up expired subscriptions
        if (!empty($expiredEndpoints)) {
            PushSubscription::whereIn('endpoint', $expiredEndpoints)->delete();
        }

        \Log::info("SendPushNotification: done — sent={$sent} failed={$failed}");

        $this->notification->update([
            'sent_count' => $sent,
            'failed_count' => $failed,
        ]);
    }
}
