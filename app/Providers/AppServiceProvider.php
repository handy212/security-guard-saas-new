<?php

namespace App\Providers;

use App\Contracts\FileScanner;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\ResolveTenant;
use App\Services\FileScanners\ClamAvFileScanner;
use App\Services\FileScanners\NullFileScanner;
use App\Services\PlatformBrandingService;
use App\Services\TenantBrandingService;
use App\Support\TenantContext;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(PlatformBrandingService::class);

        $this->app->singleton(FileScanner::class, function () {
            return match (config('file_scanner.driver', 'null')) {
                'clamav' => new ClamAvFileScanner,
                default => new NullFileScanner,
            };
        });
    }

    public function boot(): void
    {
        ResetPassword::createUrlUsing(fn (CanResetPassword $user, string $token): string => rtrim(config('app.url'), '/').route('password.reset', [
            'token' => $token,
            'email' => $user->getEmailForPasswordReset(),
        ], absolute: false));

        Livewire::addPersistentMiddleware([
            ResolveTenant::class,
            EnsurePlanFeature::class,
        ]);

        View::composer(['layouts.app', 'layouts.portal', 'layouts.guard', 'layouts.guest'], function ($view): void {
            $branding = app(TenantBrandingService::class)->forTenant(TenantContext::current());
            $view->with('tenantBranding', $branding);
        });

        View::composer(['layouts.app', 'layouts.portal', 'layouts.guard', 'layouts.guest', 'auth.login', 'auth.password-reset', 'components.brand-mark', 'partials.brand-assets', 'verify.guard'], function ($view): void {
            $view->with('platformBranding', app(PlatformBrandingService::class));
        });
    }
}
