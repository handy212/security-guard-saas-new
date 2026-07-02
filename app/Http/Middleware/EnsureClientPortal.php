<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClientPortal
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless(
            $user && (
                $user->hasRole('client')
                || ($user->can('client_portal.view') && ! $user->can('dashboard.view'))
            ),
            403
        );

        return $next($request);
    }
}
