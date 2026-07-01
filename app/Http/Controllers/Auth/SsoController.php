<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Sso\OidcAuthenticationService;
use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SsoController extends Controller
{
    public function redirect(OidcAuthenticationService $sso): RedirectResponse
    {
        abort_unless($sso->isEnabled(), 404);

        return redirect()->away($sso->authorizationRedirectUrl());
    }

    public function callback(Request $request, OidcAuthenticationService $sso): RedirectResponse
    {
        abort_unless($sso->isEnabled(), 404);

        $request->validate([
            'code' => ['required', 'string'],
            'state' => ['required', 'string'],
        ]);

        try {
            $user = $sso->authenticateFromCallback($request->string('code')->toString(), $request->string('state')->toString());
        } catch (\Throwable $e) {
            return redirect()->route('login')->withErrors(['email' => $e->getMessage()]);
        }

        Auth::login($user, true);
        $request->session()->regenerate();

        $home = TenantContext::isPlatformAdmin()
            ? route('saas.tenants')
            : route('dashboard');

        return redirect()->intended($home);
    }
}
