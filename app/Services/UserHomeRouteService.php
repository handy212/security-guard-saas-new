<?php

namespace App\Services;

use App\Models\User;
use App\Support\TenantContext;

class UserHomeRouteService
{
    public function resolve(?User $user): string
    {
        if (! $user) {
            return route('login');
        }

        if (TenantContext::isPlatformAdmin() && $user->can('tenants.manage')) {
            return route('saas.tenants');
        }

        if ($user->hasRole('client') || ($user->can('client_portal.view') && ! $user->can('dashboard.view'))) {
            return route('client-portal.dashboard');
        }

        if ($user->hasRole('guard') || ($user->can('mobile.use') && ! $user->can('dashboard.view'))) {
            return route('guard.mobile');
        }

        if ($user->hasRole('supervisor') && $user->can('dispatch.manage')) {
            return route('dispatch.control-room');
        }

        if ($user->can('dispatch.manage') && ! $user->can('settings.manage')) {
            return route('dispatch.control-room');
        }

        return route('dashboard');
    }
}
