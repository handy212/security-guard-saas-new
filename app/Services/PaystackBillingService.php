<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PaystackBillingService
{
    public function isConfigured(): bool
    {
        return filled(config('paystack.secret_key'));
    }

    public function initializeCheckout(Tenant $tenant, SubscriptionPlan $plan, string $email): ?string
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $reference = 'guardops_'.$tenant->id.'_'.time();

        $response = Http::withToken(config('paystack.secret_key'))
            ->post(config('paystack.base_url').'/transaction/initialize', [
                'email' => $email,
                'amount' => $this->amountInSubunit($plan->monthly_price),
                'currency' => config('paystack.currency', 'NGN'),
                'reference' => $reference,
                'callback_url' => route('billing.paystack.callback'),
                'metadata' => [
                    'tenant_id' => $tenant->id,
                    'plan_id' => $plan->id,
                    'plan_slug' => $plan->slug,
                ],
                'channels' => ['card', 'bank', 'ussd', 'bank_transfer'],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack initialize failed', ['body' => $response->body()]);

            return null;
        }

        return $response->json('data.authorization_url');
    }

    public function verifyTransaction(string $reference): ?array
    {
        if (! $this->isConfigured()) {
            return null;
        }

        $response = Http::withToken(config('paystack.secret_key'))
            ->get(config('paystack.base_url').'/transaction/verify/'.$reference);

        if (! $response->successful() || ! $response->json('status')) {
            return null;
        }

        $data = $response->json('data');

        return $data['status'] === 'success' ? $data : null;
    }

    public function verifyWebhookSignature(Request $request): bool
    {
        $secret = config('paystack.webhook_secret') ?: config('paystack.secret_key');
        $signature = $request->header('x-paystack-signature');

        if (! $secret || ! $signature) {
            return false;
        }

        return hash_equals($signature, hash_hmac('sha512', $request->getContent(), $secret));
    }

    public function activateFromPayment(Tenant $tenant, SubscriptionPlan $plan, array $paymentData): TenantSubscription
    {
        $customerCode = $paymentData['customer']['customer_code'] ?? $tenant->paystack_customer_code;

        $tenant->update([
            'plan_id' => $plan->id,
            'paystack_customer_code' => $customerCode,
        ]);

        app(PlanEntitlementService::class)->syncBillingLimits($tenant, $plan);

        $subscription = TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]
        );

        $authorizationCode = data_get($paymentData, 'authorization.authorization_code');
        $email = data_get($paymentData, 'customer.email') ?? $tenant->users()->value('email');

        if ($plan->paystack_plan_code && $authorizationCode && $email) {
            $this->createRecurringSubscription($tenant, $plan, $email, $authorizationCode);
        }

        return $subscription->fresh();
    }

    public function createRecurringSubscription(
        Tenant $tenant,
        SubscriptionPlan $plan,
        string $email,
        string $authorizationCode,
    ): ?string {
        if (! $this->isConfigured() || ! $plan->paystack_plan_code) {
            return null;
        }

        $customerCode = $tenant->paystack_customer_code ?: $this->createCustomer($email, $tenant);

        if (! $customerCode) {
            return null;
        }

        $response = Http::withToken(config('paystack.secret_key'))
            ->post(config('paystack.base_url').'/subscription', [
                'customer' => $customerCode,
                'plan' => $plan->paystack_plan_code,
                'authorization' => $authorizationCode,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack subscription create failed', ['body' => $response->body(), 'tenant_id' => $tenant->id]);

            return null;
        }

        $subscriptionCode = $response->json('data.subscription_code');

        $tenant->update([
            'paystack_customer_code' => $customerCode,
            'paystack_subscription_code' => $subscriptionCode,
        ]);

        return $subscriptionCode;
    }

    public function disableSubscription(Tenant $tenant): bool
    {
        if (! $this->isConfigured() || ! $tenant->paystack_subscription_code) {
            return false;
        }

        $response = Http::withToken(config('paystack.secret_key'))
            ->post(config('paystack.base_url').'/subscription/disable', [
                'code' => $tenant->paystack_subscription_code,
                'token' => $tenant->paystack_subscription_code,
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            Log::warning('Paystack subscription disable failed', ['body' => $response->body(), 'tenant_id' => $tenant->id]);

            return false;
        }

        TenantSubscription::where('tenant_id', $tenant->id)->update(['status' => 'cancelled', 'ends_at' => now()]);
        $tenant->update(['paystack_subscription_code' => null]);

        return true;
    }

    public function syncSubscriptionFromPaystack(array $data): ?TenantSubscription
    {
        $subscriptionCode = data_get($data, 'subscription_code') ?? data_get($data, 'code');
        $tenantId = data_get($data, 'metadata.tenant_id');

        $tenant = $tenantId
            ? Tenant::find($tenantId)
            : Tenant::where('paystack_subscription_code', $subscriptionCode)->first();

        if (! $tenant) {
            return null;
        }

        $planCode = data_get($data, 'plan.plan_code') ?? data_get($data, 'plan');
        $plan = $planCode
            ? SubscriptionPlan::where('paystack_plan_code', $planCode)->first()
            : SubscriptionPlan::find($tenant->plan_id);

        if (! $plan) {
            return null;
        }

        $status = match (data_get($data, 'status')) {
            'active' => 'active',
            'non-renewing' => 'cancelled',
            'attention' => 'past_due',
            default => 'active',
        };

        $tenant->update([
            'plan_id' => $plan->id,
            'paystack_subscription_code' => $subscriptionCode ?? $tenant->paystack_subscription_code,
        ]);

        app(PlanEntitlementService::class)->syncBillingLimits($tenant, $plan);

        return TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => $status,
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]
        );
    }

    public function handleWebhookEvent(string $event, array $data): void
    {
        match ($event) {
            'charge.success' => $this->processSuccessfulCharge($data),
            'subscription.create' => $this->syncSubscriptionFromPaystack($data),
            'subscription.not_renew', 'subscription.disable' => $this->handleSubscriptionCancelled($data),
            'invoice.payment_failed' => $this->handlePaymentFailed($data),
            default => null,
        };
    }

    public function processSuccessfulCharge(array $data): ?TenantSubscription
    {
        $metadata = $data['metadata'] ?? [];
        $tenantId = $metadata['tenant_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;

        if (! $tenantId || ! $planId) {
            if ($subscriptionCode = data_get($data, 'subscription_code')) {
                return $this->renewFromSubscriptionCharge($subscriptionCode);
            }

            return null;
        }

        $tenant = Tenant::find($tenantId);
        $plan = SubscriptionPlan::find($planId);

        if (! $tenant || ! $plan) {
            return null;
        }

        if (($data['status'] ?? '') !== 'success') {
            return null;
        }

        return $this->activateFromPayment($tenant, $plan, $data);
    }

    public function renewFromSubscriptionCharge(string $subscriptionCode): ?TenantSubscription
    {
        $tenant = Tenant::where('paystack_subscription_code', $subscriptionCode)->first();

        if (! $tenant) {
            return null;
        }

        $subscription = TenantSubscription::where('tenant_id', $tenant->id)->first();

        if (! $subscription) {
            return null;
        }

        $subscription->update([
            'status' => 'active',
            'ends_at' => now()->addMonth(),
        ]);

        return $subscription->fresh();
    }

    private function handleSubscriptionCancelled(array $data): void
    {
        $subscriptionCode = data_get($data, 'subscription_code') ?? data_get($data, 'code');
        $tenantId = data_get($data, 'metadata.tenant_id');

        $tenant = $tenantId
            ? Tenant::find($tenantId)
            : Tenant::where('paystack_subscription_code', $subscriptionCode)->first();

        if (! $tenant) {
            return;
        }

        TenantSubscription::where('tenant_id', $tenant->id)->update([
            'status' => 'cancelled',
            'ends_at' => now(),
        ]);

        $tenant->update(['paystack_subscription_code' => null]);
    }

    private function handlePaymentFailed(array $data): void
    {
        $tenantId = data_get($data, 'metadata.tenant_id');
        $subscriptionCode = data_get($data, 'subscription.subscription_code');

        $tenant = $tenantId
            ? Tenant::find($tenantId)
            : Tenant::where('paystack_subscription_code', $subscriptionCode)->first();

        if ($tenant) {
            TenantSubscription::where('tenant_id', $tenant->id)->update(['status' => 'past_due']);
        }
    }

    private function createCustomer(string $email, Tenant $tenant): ?string
    {
        $response = Http::withToken(config('paystack.secret_key'))
            ->post(config('paystack.base_url').'/customer', [
                'email' => $email,
                'first_name' => $tenant->name,
                'metadata' => ['tenant_id' => $tenant->id],
            ]);

        if (! $response->successful() || ! $response->json('status')) {
            return null;
        }

        return $response->json('data.customer_code');
    }

    private function amountInSubunit(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
