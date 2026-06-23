<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class StripeBillingService
{
    public function isConfigured(): bool
    {
        return filled(config('stripe.secret'));
    }

    public function createCheckoutSession(Tenant $tenant, SubscriptionPlan $plan): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::withToken(config('stripe.secret'))
            ->asForm()
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'mode' => 'subscription',
                'success_url' => url('/billing/subscription?success=1'),
                'cancel_url' => url('/billing/subscription?cancelled=1'),
                'customer_email' => $tenant->users()->value('email'),
                'line_items[0][price_data][currency]' => config('stripe.currency', 'usd'),
                'line_items[0][price_data][product_data][name]' => $plan->name,
                'line_items[0][price_data][unit_amount]' => (int) ($plan->monthly_price * 100),
                'line_items[0][price_data][recurring][interval]' => 'month',
                'line_items[0][quantity]' => 1,
                'metadata[tenant_id]' => $tenant->id,
                'metadata[plan_id]' => $plan->id,
            ]);

        if (! $response->successful()) {
            Log::warning('Stripe checkout failed', ['body' => $response->body()]);

            return null;
        }

        return $response->json('url');
    }

    public function syncSubscription(Tenant $tenant, string $stripeSubscriptionId, string $status = 'active'): TenantSubscription
    {
        $tenant->update(['stripe_subscription_id' => $stripeSubscriptionId]);

        return TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['subscription_plan_id' => $tenant->plan_id, 'status' => $status, 'starts_at' => now()]
        );
    }
}
