<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;
use Illuminate\Support\Collection;

class PlatformMetricsService
{
    /** @return array{total: int, active: int, suspended: int, on_trial: int, without_plan: int, mrr: float} */
    public function tenantSummary(): array
    {
        $tenants = Tenant::with('subscription')->get();

        return [
            'total' => $tenants->count(),
            'active' => $tenants->where('status', 'active')->count(),
            'suspended' => $tenants->where('status', 'suspended')->count(),
            'on_trial' => TenantSubscription::where('status', 'trial')->count(),
            'without_plan' => $tenants->filter(fn (Tenant $t) => ! $t->plan_id && ! $t->subscription)->count(),
            'mrr' => $this->estimatedMrr(),
        ];
    }

    public function estimatedMrr(): float
    {
        return (float) TenantSubscription::query()
            ->whereIn('status', ['active', 'trial'])
            ->with('plan')
            ->get()
            ->sum(fn (TenantSubscription $sub) => (float) ($sub->plan?->monthly_price ?? 0));
    }

    /** @return Collection<int, TenantSubscription> */
    public function expiringTrials(int $withinDays = 14): Collection
    {
        return TenantSubscription::query()
            ->where('status', 'trial')
            ->whereNotNull('trial_ends_at')
            ->whereBetween('trial_ends_at', [now(), now()->addDays($withinDays)])
            ->with(['tenant', 'plan'])
            ->orderBy('trial_ends_at')
            ->get();
    }

    /** @return Collection<int, Tenant> */
    public function recentTenants(int $limit = 5): Collection
    {
        return Tenant::query()
            ->with('subscription.plan')
            ->withCount(['users', 'guards'])
            ->latest()
            ->limit($limit)
            ->get();
    }

    /** @return Collection<int, SubscriptionPlan> */
    public function planUsage(): Collection
    {
        return SubscriptionPlan::query()
            ->withCount('subscriptions')
            ->orderByDesc('subscriptions_count')
            ->get();
    }
}
