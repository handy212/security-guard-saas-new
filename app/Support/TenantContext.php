<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    public static function current(): ?Tenant
    {
        if (app()->bound('currentTenant')) {
            return app('currentTenant');
        }

        $user = Auth::user();
        if ($user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                app()->instance('currentTenant', $tenant);
            }

            return $tenant;
        }

        return null;
    }

    public static function id(): int
    {
        $tenant = self::current();

        if (! $tenant) {
            abort(403, 'Tenant context is required.');
        }

        return $tenant->id;
    }

    public static function userId(): int
    {
        $userId = Auth::id();

        if (! $userId) {
            abort(403, 'Authentication is required.');
        }

        return $userId;
    }
}
