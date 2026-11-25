<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\HttpFoundation\Response;

class RestrictJwmBridgeToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->user()?->currentAccessToken();
        if ($token instanceof PersonalAccessToken && $token->exists && in_array('jwm:ingest', $token->abilities, true)) {
            abort(403, 'This patrol reader token is no longer supported.');
        }

        return $next($request);
    }
}
