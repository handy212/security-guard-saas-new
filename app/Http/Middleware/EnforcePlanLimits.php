<?php

namespace App\Http\Middleware;

use App\Services\PlanLimitService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforcePlanLimits
{
    public function __construct(private PlanLimitService $limits) {}

    public function handle(Request $request, Closure $next, string $resource): Response
    {
        $tenant = app('currentTenant');

        if (! $tenant) {
            return $next($request);
        }

        $allowed = match ($resource) {
            'guard' => $this->limits->canCreateGuard($tenant),
            'site' => $this->limits->canCreateSite($tenant),
            default => true,
        };

        abort_unless($allowed, 402, 'Plan limit reached. Upgrade your subscription to add more '.$resource.'s.');

        return $next($request);
    }
}
