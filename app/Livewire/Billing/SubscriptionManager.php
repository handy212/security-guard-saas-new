<?php

namespace App\Livewire\Billing;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Services\PaystackBillingService;
use App\Services\PlanLimitService;
use App\Support\TenantContext;
use Livewire\Component;

class SubscriptionManager extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
    }

    public function checkout(int $planId, PaystackBillingService $paystack): void
    {
        $tenant = app('currentTenant');
        $plan = SubscriptionPlan::findOrFail($planId);
        $email = auth()->user()->email;

        $url = $paystack->initializeCheckout($tenant, $plan, $email);

        if ($url) {
            $this->redirect($url);
        } else {
            session()->flash('status', 'Paystack is not configured. Set PAYSTACK_SECRET_KEY and PAYSTACK_PUBLIC_KEY in your environment.');
        }
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.billing.subscription-manager', [
            'plans' => SubscriptionPlan::where('status', 'active')->orderBy('monthly_price')->get(),
            'usage' => app(PlanLimitService::class)->usageSummary($tenantId),
            'paystackConfigured' => app(PaystackBillingService::class)->isConfigured(),
            'currency' => config('paystack.currency', 'NGN'),
            'activeSubscription' => TenantSubscription::with('plan')->where('tenant_id', $tenantId)->first(),
        ])->layout('layouts.app');
    }
}
