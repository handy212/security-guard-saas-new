<?php

namespace App\Http\Middleware;

use App\Services\TenantRoleProvisioner;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SyncPermissionsTeamId
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            setPermissionsTeamId(TenantRoleProvisioner::PLATFORM_TENANT_ID);

            return $next($request);
        }

        setPermissionsTeamId($user->tenant_id ?? TenantRoleProvisioner::PLATFORM_TENANT_ID);

        return $next($request);
    }
}
