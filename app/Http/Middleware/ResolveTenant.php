<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $tenant = null;

        if ($user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
        }

        if (! $tenant && $user?->hasRole('super-admin') && $request->header('X-Tenant')) {
            $tenant = Tenant::where('slug', $request->header('X-Tenant'))->first();
        }

        if (! $tenant && $request->routeIs('saas.*') && $user?->hasRole('super-admin')) {
            app()->instance('currentTenant', null);

            return $next($request);
        }

        if (! $tenant) {
            abort(403, 'Tenant context is required.');
        }

        app()->instance('currentTenant', $tenant);

        return $next($request);
    }
}
