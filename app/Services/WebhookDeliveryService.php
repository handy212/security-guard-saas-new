<?php

namespace App\Services;

use App\Models\WebhookSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WebhookDeliveryService
{
    public function dispatch(int $tenantId, string $event, array $payload): void
    {
        $subscriptions = WebhookSubscription::query()
            ->where('tenant_id', $tenantId)
            ->where('event', $event)
            ->where('is_active', true)
            ->get();

        foreach ($subscriptions as $subscription) {
            $body = json_encode(['event' => $event, 'payload' => $payload]);
            $signature = hash_hmac('sha256', $body, $subscription->secret);

            $response = Http::timeout(10)
                ->withHeaders(['X-GuardOps-Signature' => $signature])
                ->post($subscription->target_url, json_decode($body, true));

            if ($response->successful()) {
                $subscription->update(['last_delivered_at' => now()]);
            }
        }
    }

    public static function generateSecret(): string
    {
        return Str::random(40);
    }
}
