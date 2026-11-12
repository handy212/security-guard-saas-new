<?php

namespace App\Http\Middleware;

use App\Services\PlanEntitlementService;
use App\Support\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanFeature
{
    public function __construct(private PlanEntitlementService $entitlements)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->user()?->hasRole('super-admin') && ! TenantContext::isViewingAsTenant()) {
            return $next($request);
        }

        $tenant = TenantContext::current();

        if (! $tenant) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        $routes = config('plan_entitlements.routes', []);
        $feature = $routeName ? ($routes[$routeName] ?? null) : null;
        if ($routeName === 'schedules.index') {
            $tab = $request->query('tab', 'roster');
            $panel = is_string($tab) ? (\App\Livewire\Scheduling\SchedulerWorkspace::PANELS[$tab] ?? null) : null;
            $feature = $panel ? ($routes[$panel[1]] ?? null) : $feature;
        }


        if ($feature && ! $this->entitlements->tenantHasFeature($tenant->id, $feature)) {
            abort(403, 'This feature is not included in your subscription plan.');
        }

        return $next($request);
    }
}
