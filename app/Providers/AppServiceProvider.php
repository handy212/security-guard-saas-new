<?php

namespace App\Providers;

use App\Contracts\FileScanner;
use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\ResolveTenant;
use App\Services\FileScanners\ClamAvFileScanner;
use App\Services\FileScanners\NullFileScanner;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FileScanner::class, function () {
            return match (config('file_scanner.driver', 'null')) {
                'clamav' => new ClamAvFileScanner,
                default => new NullFileScanner,
            };
        });
    }

    public function boot(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveTenant::class,
            EnsurePlanFeature::class,
        ]);
    }
}
