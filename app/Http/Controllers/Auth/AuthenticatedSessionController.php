<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Services\TenantBrandingService;
use App\Services\TenantDomainResolver;
use App\Services\UserHomeRouteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(Request $request, TenantDomainResolver $domains, TenantBrandingService $branding): View
    {
        $tenant = $domains->resolveFromRequest($request);
        $organization = $request->query('organization');

        if ($tenant === null && is_string($organization) && $organization !== '') {
            $tenant = Tenant::query()
                ->where('status', 'active')
                ->where('slug', $organization)
                ->first();
        } elseif ($tenant === null && $request->session()->has('login_brand_tenant_id')) {
            $tenant = Tenant::query()
                ->where('status', 'active')
                ->find($request->session()->get('login_brand_tenant_id'));
        }

        return view('auth.login', [
            'tenantBranding' => $branding->forTenant($tenant),
        ]);
    }

    public function store(Request $request, UserHomeRouteService $homeRoute): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        $user = Auth::user();
        $user?->forceFill(['last_login_at' => now()])->save();

        return redirect()->intended($homeRoute->resolve($user));
    }

    public function destroy(Request $request): RedirectResponse
    {
        $tenantId = $request->user()?->tenant_id;

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($tenantId !== null) {
            $request->session()->put('login_brand_tenant_id', $tenantId);
        }

        return redirect()->route('login');
    }
}
