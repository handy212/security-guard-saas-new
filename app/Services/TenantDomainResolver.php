<?php

namespace App\Services;

use App\Models\Tenant;
use Illuminate\Http\Request;

class TenantDomainResolver
{
    public function resolveFromRequest(Request $request): ?Tenant
    {
        $host = strtolower($request->getHost());

        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return null;
        }

        $baseDomain = strtolower((string) config('tenancy.base_domain'));

        if ($baseDomain && $host === $baseDomain) {
            return null;
        }

        if ($baseDomain && str_ends_with($host, '.'.$baseDomain)) {
            $subdomain = str_replace('.'.$baseDomain, '', $host);

            return Tenant::query()
                ->where('subdomain', $subdomain)
                ->where('status', 'active')
                ->first();
        }

        return Tenant::query()
            ->where('domain', $host)
            ->where('status', 'active')
            ->first();
    }
}
