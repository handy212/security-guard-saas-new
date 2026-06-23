<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return $next($request);
        }

        if ($user->two_factor_confirmed_at && ! session('two_factor_passed')) {
            if (! $request->routeIs('settings.two-factor', 'logout')) {
                return redirect()->route('settings.two-factor');
            }
        }

        return $next($request);
    }
}
