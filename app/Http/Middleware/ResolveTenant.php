<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;

class ResolveTenant
{
    public function handle(Request $request, Closure $next)
    {
        $tenant = null;

        if ($request->user()?->tenant_id) {
            $tenant = Tenant::find($request->user()->tenant_id);
        }

        if (! $tenant && $request->header('X-Tenant')) {
            $tenant = Tenant::where('slug', $request->header('X-Tenant'))->first();
        }

        app()->instance('currentTenant', $tenant);
        return $next($request);
    }
}
