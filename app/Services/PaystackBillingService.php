<?php

namespace App\Services;

use App\Services\PlanEntitlementService;
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
        $tenant->update([
            'plan_id' => $plan->id,
            'paystack_customer_code' => $paymentData['customer']['customer_code'] ?? $tenant->paystack_customer_code,
        ]);

        app(PlanEntitlementService::class)->syncBillingLimits($tenant, $plan);

        return TenantSubscription::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'starts_at' => now(),
                'ends_at' => now()->addMonth(),
            ]
        );
    }

    public function handleWebhookEvent(string $event, array $data): void
    {
        if ($event === 'charge.success') {
            $this->processSuccessfulCharge($data);
        }

        if (in_array($event, ['subscription.disable', 'invoice.payment_failed'], true)) {
            $tenantId = data_get($data, 'metadata.tenant_id');
            if ($tenantId) {
                TenantSubscription::where('tenant_id', $tenantId)->update(['status' => 'past_due']);
            }
        }
    }

    public function processSuccessfulCharge(array $data): ?TenantSubscription
    {
        $metadata = $data['metadata'] ?? [];
        $tenantId = $metadata['tenant_id'] ?? null;
        $planId = $metadata['plan_id'] ?? null;

        if (! $tenantId || ! $planId) {
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

    private function amountInSubunit(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
