<?php

namespace App\Services;

use App\Models\Tenant;

class TenantBrandingService
{
    public function __construct(private GuardIdCardPresenter $presenter) {}

    /**
     * @return array{name: string, initial: string, color: string, tagline: string}
     */
    public function forTenant(?Tenant $tenant): ?array
    {
        if (! $tenant) {
            return null;
        }

        $brand = $this->presenter->branding($tenant);

        return [
            'name' => $brand['company_name'],
            'initial' => strtoupper(substr($brand['company_name'], 0, 1)),
            'color' => $brand['brand_color'],
            'tagline' => $brand['tagline'],
        ];
    }
}
