<?php

use App\Models\Tenant;
use App\Services\TenantCurrencyService;

if (! function_exists('currency_code')) {
    function currency_code(?Tenant $tenant = null): string
    {
        return app(TenantCurrencyService::class)->code($tenant);
    }
}

if (! function_exists('currency_symbol')) {
    function currency_symbol(?Tenant $tenant = null): string
    {
        return app(TenantCurrencyService::class)->symbol($tenant);
    }
}

if (! function_exists('money')) {
    function money(float|int|string|null $amount, int $decimals = 2, ?Tenant $tenant = null): string
    {
        return app(TenantCurrencyService::class)->format($amount, $decimals, $tenant);
    }
}
