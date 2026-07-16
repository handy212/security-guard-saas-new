<?php

namespace App\Notifications\Channels;

use App\Models\PushSubscription;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class PushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('notifications.push.enabled')) {
            return;
        }

        $data = $notification->toPush($notifiable);
        if (! $data) {
            return;
        }

        $publicKey = config('notifications.push.vapid.public_key');
        $privateKey = config('notifications.push.vapid.private_key');

        if (! $publicKey || ! $privateKey) {
            Log::debug('Push skipped: VAPID keys not configured', ['user_id' => $notifiable->id ?? null]);

            return;
        }

        $payload = json_encode([
            'title' => $data['title'] ?? 'GuardCore Pro',
            'body' => $data['body'] ?? '',
            'url' => $data['action_url'] ?? '/dashboard',
        ]);

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => config('notifications.push.vapid.subject'),
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ]);

        $subscriptions = PushSubscription::where('user_id', $notifiable->id)->get();

        foreach ($subscriptions as $row) {
            try {
                $subscription = Subscription::create([
                    'endpoint' => $row->endpoint,
                    'publicKey' => $row->public_key,
                    'authToken' => $row->auth_token,
                    'contentEncoding' => $row->content_encoding ?: 'aesgcm',
                ]);

                $report = $webPush->sendOneNotification($subscription, $payload);

                if ($report->isSubscriptionExpired()) {
                    $row->delete();
                } elseif (! $report->isSuccess()) {
                    Log::warning('Push notification failed', [
                        'endpoint' => $row->endpoint,
                        'reason' => $report->getReason(),
                    ]);
                }
            } catch (\Throwable $e) {
                Log::warning('Push notification error', [
                    'endpoint' => $row->endpoint,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
