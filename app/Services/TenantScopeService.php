<?php

namespace App\Services;

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
}
