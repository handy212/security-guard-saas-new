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
            abort_unless($request->routeIs('api.jwm.events.store'), 403, 'Bridge tokens are restricted to JWM event ingestion.');
        }

        return $next($request);
    }
}
