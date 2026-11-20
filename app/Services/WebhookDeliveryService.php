<?php

namespace App\Services;

use App\Models\WebhookSubscription;
use App\Rules\SafeWebhookUrl;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
            $validation = Validator::make(
                ['target_url' => $subscription->target_url],
                ['target_url' => ['required', new SafeWebhookUrl]]
            );

            if ($validation->fails()) {
                Log::warning('Unsafe webhook destination blocked', [
                    'subscription_id' => $subscription->id,
                    'tenant_id' => $subscription->tenant_id,
                ]);

                continue;
            }

            $body = json_encode(['event' => $event, 'payload' => $payload]);
            $signature = hash_hmac('sha256', $body, $subscription->secret);

            $response = Http::timeout(10)
                ->withoutRedirecting()
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
