<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Console\Scheduling\Schedule;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'tenant' => \App\Http\Middleware\ResolveTenant::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'plan.limits' => \App\Http\Middleware\EnforcePlanLimits::class,
            'client.portal' => \App\Http\Middleware\EnsureClientPortal::class,
            'two-factor' => \App\Http\Middleware\RequireTwoFactor::class,
            'platform' => \App\Http\Middleware\EnsurePlatformAdmin::class,
            'plan.feature' => \App\Http\Middleware\EnsurePlanFeature::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->command('guardops:analytics-snapshot')->dailyAt('01:00');
        $schedule->command('guardops:compliance-expiry')->dailyAt('07:00');
        $schedule->command('guardops:missed-patrols')->everyFifteenMinutes();
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Session\TokenMismatchException $e, \Illuminate\Http\Request $request) {
            $isLivewire = $request->hasHeader('X-Livewire') || $request->is('livewire/*');

            if ($request->expectsJson() || $isLivewire) {
                return response()->json(['message' => 'Your session expired. Please refresh and try again.'], 419);
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your session expired. Please sign in again.'])
                ->withInput($request->except('password', '_token'));
        });
    })->create();
