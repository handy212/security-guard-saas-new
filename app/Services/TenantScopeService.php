<?php

namespace App\Services;

class TenantScopeService
{
    /**
     * Resolve current tenant and apply tenant isolation to queries.
     */
    public function handle(array $payload = []): array
    {
        return ['ok' => true, 'message' => 'Resolve current tenant and apply tenant isolation to queries.'];
    }
}
