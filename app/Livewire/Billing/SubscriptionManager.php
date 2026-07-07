<?php

namespace App\Livewire\Billing;

use App\Models\SubscriptionPlan;
use App\Models\TenantSubscription;
use App\Services\PaystackBillingService;
use App\Services\PlanEntitlementService;
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
            session()->flash('status', 'Online checkout is not available right now. Please contact support to upgrade your plan.');
        }
    }

    public function cancelSubscription(PaystackBillingService $paystack): void
    {
        $tenant = app('currentTenant');

        if ($paystack->disableSubscription($tenant)) {
            session()->flash('status', 'Subscription cancelled.');
        } else {
            session()->flash('status', 'Unable to cancel subscription. Contact support if this persists.');
        }
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $entitlements = app(PlanEntitlementService::class);
        $activeSubscription = TenantSubscription::with('plan')->where('tenant_id', $tenantId)->first();
        $currentPlan = $activeSubscription?->plan ?? $entitlements->planForTenant($tenantId);

        return view('livewire.billing.subscription-manager', [
            'plans' => SubscriptionPlan::where('status', 'active')->orderBy('monthly_price')->get(),
            'usage' => $entitlements->usageSummary($tenantId),
            'paystackConfigured' => app(PaystackBillingService::class)->isConfigured(),
            'currency' => config('paystack.currency', 'NGN'),
            'activeSubscription' => $activeSubscription,
            'currentPlan' => $currentPlan,
            'currentPlanId' => $currentPlan?->id,
            'entitlements' => $entitlements,
        ])->layout('layouts.app');
    }
}
