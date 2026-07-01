<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Services\TenantDomainResolver;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(private TenantDomainResolver $domainResolver)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = null;

        if ($user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }

        if (! $tenant) {
            $tenant = $this->domainResolver->resolveFromRequest($request);
        }

        if (! $tenant && $user?->hasRole('super-admin') && $request->header('X-Tenant')) {
            $tenant = Tenant::query()
                ->where('slug', $request->header('X-Tenant'))
                ->where('status', 'active')
                ->first();
        }

        if (! $tenant && $user?->hasRole('super-admin') && $user->tenant_id === null) {
            $switchedSlug = session('platform_tenant_slug');
            if ($switchedSlug && ! TenantContext::isSaasRequest($request)) {
                $tenant = Tenant::query()
                    ->where('slug', $switchedSlug)
                    ->where('status', 'active')
                    ->first();
                if ($tenant) {
                    app()->instance('currentTenant', $tenant);

                    return $next($request);
                }

                session()->forget('platform_tenant_slug');
            }

            if ($request->routeIs('dashboard')) {
                return redirect()->route('saas.tenants');
            }

            if (TenantContext::isSaasRequest($request)) {
                return $next($request);
            }
        }

        if (! $tenant && TenantContext::isSaasRequest($request) && $user?->hasRole('super-admin')) {
            return $next($request);
        }

        if (! $tenant) {
            abort(403, 'Tenant context is required.');
        }

        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
