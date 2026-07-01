<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Tenant;
use App\Models\TenantSetting;

class GuardIdCardPresenter
{
    /**
     * @return array{
     *     company_name: string,
     *     tagline: string,
     *     brand_color: string,
     *     emergency_text: string,
     *     phone: ?string,
     *     phone_secondary: ?string,
     *     email: ?string,
     *     website: ?string,
     *     address: ?string,
     * }
     */
    public function branding(Tenant $tenant, ?Branch $branch = null): array
    {
        $settings = TenantSetting::query()
            ->where('tenant_id', $tenant->id)
            ->where('key', 'id_card')
            ->value('value') ?? [];

        $companyName = $tenant->name;
        $defaultEmergency = "This card is the property of {$companyName}. In case of emergency or if found, kindly contact:";

        return [
            'company_name' => $companyName,
            'tagline' => $settings['tagline'] ?? 'Stay connected. Stay protected.',
            'brand_color' => $settings['brand_color'] ?? '#8C1D2F',
            'emergency_text' => $settings['emergency_text'] ?? $defaultEmergency,
            'phone' => $branch?->phone ?: ($settings['phone'] ?? null),
            'phone_secondary' => $settings['phone_secondary'] ?? null,
            'email' => $branch?->email ?: ($settings['email'] ?? null),
            'website' => $settings['website'] ?? ($tenant->domain ? 'www.'.$tenant->domain : null),
            'address' => $branch?->address ?: ($settings['address'] ?? null),
        ];
    }
}
