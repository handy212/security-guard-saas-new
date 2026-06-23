<?php

namespace App\Services;

use App\Models\BillingLimit;
use App\Models\Guard;
use App\Models\Site;
use App\Models\Tenant;
use App\Models\TenantSubscription;

class PlanLimitService
{
    public function limitsForTenant(int $tenantId): array
    {
        $billing = BillingLimit::where('tenant_id', $tenantId)->first();
        $subscription = TenantSubscription::with('plan')->where('tenant_id', $tenantId)->where('status', 'active')->first();
        $plan = $subscription?->plan;

        return [
            'max_guards' => $billing?->max_guards ?? $plan?->max_guards ?? 25,
            'max_sites' => $billing?->max_sites ?? $plan?->max_sites ?? 10,
            'max_clients' => $billing?->max_clients ?? 50,
            'storage_mb' => $billing?->storage_mb ?? 1024,
        ];
    }

    public function canCreateGuard(Tenant $tenant): bool
    {
        $limits = $this->limitsForTenant($tenant->id);
        $count = Guard::where('tenant_id', $tenant->id)->count();

        return $count < $limits['max_guards'];
    }

    public function canCreateSite(Tenant $tenant): bool
    {
        $limits = $this->limitsForTenant($tenant->id);
        $count = Site::where('tenant_id', $tenant->id)->count();

        return $count < $limits['max_sites'];
    }

    public function usageSummary(int $tenantId): array
    {
        $limits = $this->limitsForTenant($tenantId);

        return [
            'guards' => ['used' => Guard::where('tenant_id', $tenantId)->count(), 'max' => $limits['max_guards']],
            'sites' => ['used' => Site::where('tenant_id', $tenantId)->count(), 'max' => $limits['max_sites']],
        ];
    }
}
