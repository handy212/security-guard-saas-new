<?php

namespace App\Livewire\Billing;

use App\Models\SubscriptionPlan;
use App\Services\PlanLimitService;
use App\Services\StripeBillingService;
use App\Support\TenantContext;
use Livewire\Component;

class SubscriptionManager extends Component
{
    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
    }

    public function checkout(int $planId, StripeBillingService $stripe): void
    {
        $tenant = app('currentTenant');
        $plan = SubscriptionPlan::findOrFail($planId);
        $url = $stripe->createCheckoutSession($tenant, $plan);

        if ($url) {
            $this->redirect($url);
        } else {
            session()->flash('status', 'Stripe is not configured. Set STRIPE_SECRET in your environment.');
        }
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.billing.subscription-manager', [
            'plans' => SubscriptionPlan::where('status', 'active')->orderBy('monthly_price')->get(),
            'usage' => app(PlanLimitService::class)->usageSummary($tenantId),
            'stripeConfigured' => app(StripeBillingService::class)->isConfigured(),
        ])->layout('layouts.app');
    }
}
