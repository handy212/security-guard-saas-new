<?php

namespace App\Providers;

use App\Http\Middleware\EnsurePlanFeature;
use App\Http\Middleware\ResolveTenant;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Livewire::addPersistentMiddleware([
            ResolveTenant::class,
            EnsurePlanFeature::class,
        ]);
    }
}
