<?php

namespace App\Services;

use App\Models\Tenant;
use App\Support\TenantContext;
use Illuminate\Support\Str;

class TenantCurrencyService
{
    private const SUPPORTED = [
        'GHS' => ['name' => 'Ghana cedi', 'symbol' => 'GH₵'],
        'NGN' => ['name' => 'Nigerian naira', 'symbol' => '₦'],
        'USD' => ['name' => 'US dollar', 'symbol' => '$'],
        'EUR' => ['name' => 'Euro', 'symbol' => '€'],
        'GBP' => ['name' => 'Pound sterling', 'symbol' => '£'],
    ];

    public function code(?Tenant $tenant = null): string
    {
        $code = strtoupper((string) ($tenant?->currency ?? TenantContext::current()?->currency ?? config('paystack.currency', 'GHS')));

        return array_key_exists($code, self::SUPPORTED) ? $code : 'GHS';
    }

    public function symbol(?Tenant $tenant = null): string
    {
        return self::SUPPORTED[$this->code($tenant)]['symbol'];
    }

    public function format(float|int|string|null $amount, int $decimals = 2, ?Tenant $tenant = null): string
    {
        return $this->symbol($tenant).number_format((float) $amount, $decimals);
    }

    public function options(): array
    {
        return collect(self::SUPPORTED)
            ->mapWithKeys(fn (array $currency, string $code) => [$code => Str::finish($currency['name'], ' ('.$code.')')])
            ->all();
    }

    public function supportedCodes(): array
    {
        return array_keys(self::SUPPORTED);
    }
}
