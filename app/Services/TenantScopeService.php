<?php

namespace App\Services;

use App\Models\Tenant;
use App\Support\TenantContext;

class TenantScopeService
{
    public function currentTenantId(): int
    {
        return TenantContext::id();
    }

    public function applyTenantScope($query)
    {
        return $query->where('tenant_id', $this->currentTenantId());
    }

    public function runForTenant(Tenant|int $tenant, callable $callback): mixed
    {
        $tenantModel = $tenant instanceof Tenant
            ? $tenant
            : Tenant::query()->findOrFail($tenant);

        $previous = app()->bound('currentTenant') ? app('currentTenant') : null;

        app()->instance('currentTenant', $tenantModel);

        try {
            return $callback($tenantModel);
        } finally {
            if ($previous instanceof Tenant) {
                app()->instance('currentTenant', $previous);
            } else {
                app()->forgetInstance('currentTenant');
            }
        }
    }
}
