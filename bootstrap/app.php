<?php

use App\Http\Middleware\EnforcePlanLimits;
use App\Http\Middleware\EnsureClientPortal;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\EnsurePlatformAdmin;
use App\Http\Middleware\RequireTwoFactor;
use App\Http\Middleware\ResolveTenant;
use App\Http\Middleware\SyncPermissionsTeamId;
use App\Services\NotificationDispatcher;
use App\Services\SiteProtectionService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\TokenMismatchException;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->trimStrings(except: ['external_id', 'guard_external_id', 'checkpoint_external_id']);

        $middleware->web(append: [
            SyncPermissionsTeamId::class,
        ]);

        $middleware->alias([
            'tenant' => ResolveTenant::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'plan.limits' => EnforcePlanLimits::class,
            'client.portal' => EnsureClientPortal::class,
            'two-factor' => RequireTwoFactor::class,
            'platform' => EnsurePlatformAdmin::class,
            'plan.feature' => EnsurePlanFeature::class,
        ]);
    })
    ->withSchedule(function (Schedule $schedule): void {
        $schedule->call(fn () => app(NotificationDispatcher::class)->escalateDueAlarms())
            ->name('guardops:escalate-operational-alerts')->everyTenSeconds()
            ->when(NotificationDispatcher::alarmsEnabled(...))->withoutOverlapping();
        $schedule->call(fn () => app(SiteProtectionService::class)->monitor(app(NotificationDispatcher::class)))
            ->name('guardops:monitor-site-protection')->everyMinute()
            ->when(NotificationDispatcher::alarmsEnabled(...))->withoutOverlapping();
        $schedule->command('guardops:analytics-snapshot')->dailyAt('01:00');
        $schedule->command('guardops:compliance-expiry')->dailyAt('07:00');
        $schedule->command('guardops:missed-patrols')->everyFifteenMinutes();
        $schedule->command('guardops:detect-idle-guards')->everyFiveMinutes();
        $schedule->command('guardops:deliver-client-reports')->hourly();
        if (config('client_profile.report_scheduler_enabled')) {
            $schedule->command('guardops:deliver-scheduled-client-reports')->hourly()->withoutOverlapping();
        }

        $localPatriotEnvironment = is_readable(base_path('.env.patriot.local'));
        if (config('services.patriot.polling_enabled') || $localPatriotEnvironment) {
            $options = ['--scheduled', '--no-interaction'];
            if (! config('services.patriot.polling_enabled')) {
                $options[] = '--env=patriot.local';
            }

            $schedule->command('guardops:patriot-import', $options)
                ->everyMinute()
                ->withoutOverlapping(3)
                ->onFailure(fn () => logger()->warning('Scheduled Patriot dispatch import failed.'));
        }

        if (config('services.patriot.writeback_enabled') || $localPatriotEnvironment) {
            $options = ['--no-interaction'];
            if (! config('services.patriot.writeback_enabled')) {
                $options[] = '--env=patriot.local';
            }
            $schedule->command('guardops:patriot-send-updates', $options)
                ->everyMinute()
                ->withoutOverlapping(5)
                ->onFailure(fn () => logger()->warning('Patriot dispatch write-back requires attention.'));
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Laravel maps TokenMismatchException → HttpException(419) before render callbacks.
        $exceptions->render(function (Throwable $e, Request $request) {
            $isCsrfMismatch = $e instanceof TokenMismatchException
                || ($e instanceof HttpExceptionInterface && $e->getStatusCode() === 419);

            if (! $isCsrfMismatch) {
                return null;
            }

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
