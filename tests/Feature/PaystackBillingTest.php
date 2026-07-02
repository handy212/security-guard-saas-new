<?php

namespace Tests\Feature;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use App\Services\PaystackBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PaystackBillingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Config::set('paystack.secret_key', 'sk_test_guardops');
        Config::set('paystack.public_key', 'pk_test_guardops');
        Config::set('paystack.currency', 'NGN');
    }

    public function test_initialize_checkout_returns_authorization_url(): void
    {
        Http::fake([
            'api.paystack.co/transaction/initialize' => Http::response([
                'status' => true,
                'data' => ['authorization_url' => 'https://checkout.paystack.com/test'],
            ]),
        ]);

        $tenant = Tenant::create(['name' => 'Pay Co', 'slug' => 'pay-co', 'status' => 'active']);
        $plan = SubscriptionPlan::create([
            'name' => 'Starter', 'slug' => 'starter', 'monthly_price' => 9900,
            'max_guards' => 10, 'max_sites' => 5, 'status' => 'active',
        ]);

        $url = app(PaystackBillingService::class)->initializeCheckout($tenant, $plan, 'admin@test.com');

        $this->assertEquals('https://checkout.paystack.com/test', $url);
    }

    public function test_successful_charge_activates_subscription(): void
    {
        $tenant = Tenant::create(['name' => 'Active Co', 'slug' => 'active-co', 'status' => 'active']);
        $plan = SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro', 'monthly_price' => 19900,
            'max_guards' => 50, 'max_sites' => 20, 'status' => 'active',
        ]);

        app(PaystackBillingService::class)->processSuccessfulCharge([
            'status' => 'success',
            'metadata' => ['tenant_id' => $tenant->id, 'plan_id' => $plan->id],
            'customer' => ['customer_code' => 'CUS_test123'],
        ]);

        $this->assertDatabaseHas('tenant_subscriptions', [
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
        ]);
        $this->assertEquals('CUS_test123', $tenant->fresh()->paystack_customer_code);
    }

    public function test_webhook_rejects_invalid_signature(): void
    {
        Config::set('paystack.webhook_secret', 'whsec_test');

        $response = $this->postJson('/paystack/webhook', [
            'event' => 'charge.success',
            'data' => [],
        ], ['x-paystack-signature' => 'invalid']);

        $response->assertForbidden();
    }

    public function test_webhook_accepts_valid_charge_success(): void
    {
        Config::set('paystack.webhook_secret', 'whsec_test');

        $tenant = Tenant::create(['name' => 'Hook Co', 'slug' => 'hook-co', 'status' => 'active']);
        $plan = SubscriptionPlan::create([
            'name' => 'Starter', 'slug' => 'starter-hook', 'monthly_price' => 5000,
            'max_guards' => 10, 'max_sites' => 5, 'status' => 'active',
        ]);

        $payload = json_encode([
            'event' => 'charge.success',
            'data' => [
                'status' => 'success',
                'metadata' => ['tenant_id' => $tenant->id, 'plan_id' => $plan->id],
                'customer' => ['customer_code' => 'CUS_hook'],
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'whsec_test');

        $response = $this->call(
            'POST',
            '/paystack/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => $signature],
            $payload
        );

        $response->assertOk();
        $this->assertEquals('active', TenantSubscription::where('tenant_id', $tenant->id)->value('status'));
    }

    public function test_successful_charge_creates_recurring_subscription_when_plan_configured(): void
    {
        Http::fake([
            'api.paystack.co/subscription' => Http::response([
                'status' => true,
                'data' => ['subscription_code' => 'SUB_recurring123'],
            ]),
        ]);

        $tenant = Tenant::create(['name' => 'Recurring Co', 'slug' => 'recurring-co', 'status' => 'active']);
        $plan = SubscriptionPlan::create([
            'name' => 'Pro', 'slug' => 'pro-recurring', 'paystack_plan_code' => 'PLN_pro',
            'monthly_price' => 19900, 'max_guards' => 50, 'max_sites' => 20, 'status' => 'active',
        ]);

        app(PaystackBillingService::class)->processSuccessfulCharge([
            'status' => 'success',
            'metadata' => ['tenant_id' => $tenant->id, 'plan_id' => $plan->id],
            'customer' => ['customer_code' => 'CUS_recurring', 'email' => 'admin@test.com'],
            'authorization' => ['authorization_code' => 'AUTH_recurring'],
        ]);

        $this->assertEquals('SUB_recurring123', $tenant->fresh()->paystack_subscription_code);
    }

    public function test_subscription_disable_webhook_cancels_local_subscription(): void
    {
        Config::set('paystack.webhook_secret', 'whsec_test');

        $tenant = Tenant::create([
            'name' => 'Cancel Co', 'slug' => 'cancel-co', 'status' => 'active',
            'paystack_subscription_code' => 'SUB_cancel_me',
        ]);
        $plan = SubscriptionPlan::create([
            'name' => 'Starter', 'slug' => 'starter-cancel', 'monthly_price' => 5000,
            'max_guards' => 10, 'max_sites' => 5, 'status' => 'active',
        ]);
        TenantSubscription::create([
            'tenant_id' => $tenant->id,
            'subscription_plan_id' => $plan->id,
            'status' => 'active',
            'starts_at' => now(),
            'ends_at' => now()->addMonth(),
        ]);

        $payload = json_encode([
            'event' => 'subscription.disable',
            'data' => [
                'subscription_code' => 'SUB_cancel_me',
                'metadata' => ['tenant_id' => $tenant->id],
            ],
        ]);

        $signature = hash_hmac('sha512', $payload, 'whsec_test');

        $this->call(
            'POST',
            '/paystack/webhook',
            [],
            [],
            [],
            ['CONTENT_TYPE' => 'application/json', 'HTTP_X_PAYSTACK_SIGNATURE' => $signature],
            $payload
        )->assertOk();

        $this->assertEquals('cancelled', TenantSubscription::where('tenant_id', $tenant->id)->value('status'));
        $this->assertNull($tenant->fresh()->paystack_subscription_code);
    }
}
