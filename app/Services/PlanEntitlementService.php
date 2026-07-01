<?php

namespace App\Services;

use App\Models\BillingLimit;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantSubscription;

class PlanEntitlementService
{
    /** @return list<string> */
    public function catalogKeys(): array
    {
        return array_keys(config('plan_entitlements.features', []));
    }

    /** @return array<string, array{label: string, group: string}> */
    public function catalog(): array
    {
        return config('plan_entitlements.features', []);
    }

    /** @return list<string> */
    public function featuresForTenant(int $tenantId): array
    {
        $plan = $this->planForTenant($tenantId);

        if ($plan && is_array($plan->features) && $plan->features !== []) {
            return array_values(array_unique($plan->features));
        }

        return config('plan_entitlements.default_features', []);
    }

    public function tenantHasFeature(int $tenantId, string $feature): bool
    {
        return in_array($feature, $this->featuresForTenant($tenantId), true);
    }

    /** @param list<string> $keys */
    public function labelsFor(array $keys): array
    {
        $catalog = $this->catalog();

        return collect($keys)
            ->map(fn (string $key) => $catalog[$key]['label'] ?? str_replace('_', ' ', ucfirst($key)))
            ->values()
            ->all();
    }

    public function planForTenant(int $tenantId): ?SubscriptionPlan
    {
        $tenant = Tenant::with(['subscription.plan'])->find($tenantId);

        if ($tenant?->subscription?->plan) {
            return $tenant->subscription->plan;
        }

        if ($tenant?->plan_id) {
            return SubscriptionPlan::find($tenant->plan_id);
        }

        return null;
    }

    public function syncBillingLimits(Tenant $tenant, SubscriptionPlan $plan): void
    {
        BillingLimit::updateOrCreate(
            ['tenant_id' => $tenant->id],
            [
                'max_guards' => $plan->max_guards,
                'max_sites' => $plan->max_sites,
                'max_clients' => 50,
                'storage_mb' => 1024,
            ]
        );
    }

    /** @return array<string, list<array{key: string, label: string}>> */
    public function groupedCatalog(): array
    {
        $groups = [];

        foreach ($this->catalog() as $key => $meta) {
            $group = $meta['group'] ?? 'Other';
            $groups[$group][] = ['key' => $key, 'label' => $meta['label']];
        }

        return $groups;
    }

    /** @return array{plan: ?string, guards: array{used: int, max: int, pct: float}, sites: array{used: int, max: int, pct: float}} */
    public function usageSummary(int $tenantId): array
    {
        $limits = app(PlanLimitService::class)->usageSummary($tenantId);
        $plan = $this->planForTenant($tenantId);

        return [
            'plan' => $plan?->name,
            'guards' => [
                'used' => $limits['guards']['used'],
                'max' => $limits['guards']['max'],
                'pct' => $limits['guards']['max'] > 0
                    ? round(($limits['guards']['used'] / $limits['guards']['max']) * 100, 1)
                    : 0,
            ],
            'sites' => [
                'used' => $limits['sites']['used'],
                'max' => $limits['sites']['max'],
                'pct' => $limits['sites']['max'] > 0
                    ? round(($limits['sites']['used'] / $limits['sites']['max']) * 100, 1)
                    : 0,
            ],
        ];
    }
}
