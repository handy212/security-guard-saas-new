<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSetting;

class TenantBrandingService
{
    public const THEME_KEY = 'organization_theme';

    public function __construct(private GuardIdCardPresenter $presenter) {}

    /**
     * @return array{name: string, initial: string, color: string, tagline: string, theme: array{light: ?string, dark: ?string}}
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
            'theme' => $this->themeForTenant($tenant->getKey()),
        ];
    }

    /** @return array{light: ?string, dark: ?string} */
    public function themeForTenant(int $tenantId): array
    {
        $theme = TenantSetting::query()
            ->forTenant($tenantId)
            ->where('key', self::THEME_KEY)
            ->value('value');

        return [
            'light' => $this->normalizeThemeColor($theme['light'] ?? null),
            'dark' => $this->normalizeThemeColor($theme['dark'] ?? null),
        ];
    }

    private function normalizeThemeColor(mixed $color): ?string
    {
        return is_string($color) && preg_match('/\A#[0-9a-f]{6}\z/i', $color) ? strtolower($color) : null;
    }
}
