<?php

namespace App\Support;

use App\Models\Tenant;
use App\Services\TenantRoleProvisioner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TenantContext
{
    public static function current(): ?Tenant
    {
        if (app()->bound('currentTenant')) {
            $tenant = app('currentTenant');

            return $tenant instanceof Tenant ? $tenant : null;
        }

        $user = Auth::user();
        if ($user?->tenant_id) {
            $tenant = Tenant::find($user->tenant_id);
            if ($tenant) {
                app()->instance('currentTenant', $tenant);
            }

            return $tenant;
        }

        if (self::isPlatformAdmin() && ($slug = self::switchedTenantSlug()) && ! self::isPlatformConsole()) {
            return Tenant::query()
                ->where('slug', $slug)
                ->where('status', 'active')
                ->first();
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

    public static function isPlatformAdmin(): bool
    {
        $user = Auth::user();

        if (! $user || $user->tenant_id !== null) {
            return false;
        }

        setPermissionsTeamId(TenantRoleProvisioner::PLATFORM_TENANT_ID);

        return $user->hasRole('super-admin');
    }

    public static function switchedTenantSlug(): ?string
    {
        return session('platform_tenant_slug');
    }

    public static function isViewingAsTenant(): bool
    {
        if (! self::isPlatformAdmin() || self::switchedTenantSlug() === null) {
            return false;
        }

        if (self::isSaasRequest()) {
            return false;
        }

        return self::current() instanceof Tenant;
    }

    public static function switchedTenant(): ?Tenant
    {
        $slug = self::switchedTenantSlug();

        return $slug ? Tenant::where('slug', $slug)->first() : null;
    }

    public static function isSaasRequest(?Request $request = null): bool
    {
        $request = $request ?? request();

        return $request->is('saas', 'saas/*');
    }

    public static function isPlatformConsole(): bool
    {
        return self::isPlatformAdmin() && self::isSaasRequest();
    }

    public static function enterTenant(Tenant $tenant): void
    {
        abort_unless($tenant->status === 'active', 403, 'Cannot enter a suspended tenant.');

        // Callers redirect after enter, so rotating the session/CSRF is safe.
        session()->regenerate();
        session(['platform_tenant_slug' => $tenant->slug]);
    }

    public static function exitTenant(): void
    {
        // Do not rotate the session/CSRF here — suspend/exit can run inside Livewire
        // (e.g. TenantManagement::clearTenantSession). Full-page exits regenerate in
        // PlatformTenantContextController::exit after this call.
        session()->forget('platform_tenant_slug');
    }
}
