<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Guard;
use App\Models\GuardVerificationToken;
use App\Models\Tenant;
use App\Models\TenantSetting;

class GuardIdCardPresenter
{
    public const TEMPLATES = ['modern', 'minimal', 'creative'];

    public const ORIENTATIONS = ['portrait', 'landscape'];

    /**
     * @return array{
     *     company_name: string,
     *     tagline: string,
     *     template: string,
     *     orientation: string,
     *     brand_color: string,
     *     brand_color_dark: string,
     *     emergency_text: string,
     *     phone: ?string,
     *     phone_secondary: ?string,
     *     email: ?string,
     *     website: ?string,
     *     address: ?string,
     *     logo_path: ?string,
     *     logo_url: ?string,
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
        $brandColor = $this->normalizeBrandColor($settings['brand_color'] ?? '#2563eb');
        $template = $settings['template'] ?? 'modern';
        $orientation = $settings['orientation'] ?? 'portrait';

        if (! in_array($template, self::TEMPLATES, true)) {
            $template = 'modern';
        }

        if (! in_array($orientation, self::ORIENTATIONS, true)) {
            $orientation = 'portrait';
        }

        return [
            'company_name' => $companyName,
            'tagline' => $settings['tagline'] ?? 'Employee Identification',
            'template' => $template,
            'orientation' => $orientation,
            'brand_color' => $brandColor,
            'brand_color_dark' => $this->normalizeBrandColor(
                $settings['brand_color_dark'] ?? $this->darkenColor($brandColor)
            ),
            'emergency_text' => $settings['emergency_text'] ?? $defaultEmergency,
            'phone' => $branch?->phone ?: ($settings['phone'] ?? null),
            'phone_secondary' => $settings['phone_secondary'] ?? null,
            'email' => $branch?->email ?: ($settings['email'] ?? null),
            'website' => $settings['website'] ?? ($tenant->domain ? 'www.'.$tenant->domain : null),
            'address' => $branch?->address ?: ($settings['address'] ?? null),
            'logo_path' => $settings['logo_path'] ?? null,
            'logo_url' => isset($settings['logo_path']) ? route('files.id-card-logo') : null,
        ];
    }

    /**
     * @return array{
     *     employee_id: string,
     *     name: string,
     *     role: string,
     *     issue_date: string,
     *     initial: string,
     * }
     */
    public function cardData(Guard $guard, ?GuardVerificationToken $token = null): array
    {
        return [
            'employee_id' => $guard->employee_number ?: ('ID-'.$guard->id),
            'name' => $guard->full_name,
            'role' => $guard->rank ?: ($guard->branch?->name ?: 'Security Officer'),
            'issue_date' => $guard->verified_at?->format('M j, Y') ?? '--',
            'initial' => strtoupper(substr($guard->first_name, 0, 1)),
        ];
    }

    /**
     * Sample guard data for settings preview and demos.
     *
     * @return array{
     *     employee_id: string,
     *     name: string,
     *     role: string,
     *     issue_date: string,
     *     initial: string,
     * }
     */
    public function sampleCardData(): array
    {
        return [
            'employee_id' => 'EMP-2024-001',
            'name' => 'John Doe',
            'role' => 'Senior Officer',
            'issue_date' => now()->format('M j, Y'),
            'initial' => 'J',
        ];
    }

    /**
     * Live preview branding with unsaved form overrides (settings page).
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    public function brandingWithOverrides(Tenant $tenant, array $overrides = [], ?Branch $branch = null): array
    {
        $brand = $this->branding($tenant, $branch);

        if (array_key_exists('emergency_text', $overrides) && empty($overrides['emergency_text'])) {
            $overrides['emergency_text'] = "This card is the property of {$tenant->name}. In case of emergency or if found, kindly contact:";
        }

        if (array_key_exists('logo_path', $overrides)) {
            $overrides['logo_url'] = ! empty($overrides['logo_path'])
                ? route('files.id-card-logo')
                : null;
        }

        return array_merge($brand, $overrides);
    }

    public function previewScale(string $orientation = 'portrait'): float
    {
        return $orientation === 'landscape' ? 0.72 : 1.0;
    }

    /**
     * @return array<string, array{primary: string, dark: string}>
     */
    public function colorPresets(): array
    {
        return [
            'blue' => ['primary' => '#2563eb', 'dark' => '#1e40af'],
            'green' => ['primary' => '#059669', 'dark' => '#065f46'],
            'red' => ['primary' => '#dc2626', 'dark' => '#991b1b'],
            'purple' => ['primary' => '#7c3aed', 'dark' => '#5b21b6'],
            'orange' => ['primary' => '#ea580c', 'dark' => '#9a3412'],
            'slate' => ['primary' => '#0f172a', 'dark' => '#020617'],
            'pink' => ['primary' => '#ec4899', 'dark' => '#be185d'],
            'cyan' => ['primary' => '#0891b2', 'dark' => '#155e75'],
        ];
    }

    /**
     * CR80 layout dimensions for portrait or landscape.
     *
     * @return array{width_mm: float, height_mm: float, design_width_px: int, design_height_px: int}
     */
    public function layout(string $orientation = 'portrait'): array
    {
        $portraitWidthMm = (float) config('id_card.paper_width_mm');
        $portraitHeightMm = (float) config('id_card.paper_height_mm');
        $portraitDesignW = (int) config('id_card.design_width_px');
        $portraitDesignH = (int) config('id_card.design_height_px');

        if ($orientation === 'landscape') {
            return [
                'width_mm' => $portraitHeightMm,
                'height_mm' => $portraitWidthMm,
                'design_width_px' => $portraitDesignH,
                'design_height_px' => $portraitDesignW,
            ];
        }

        return [
            'width_mm' => $portraitWidthMm,
            'height_mm' => $portraitHeightMm,
            'design_width_px' => $portraitDesignW,
            'design_height_px' => $portraitDesignH,
        ];
    }

    public function printScale(string $orientation = 'portrait'): float
    {
        $layout = $this->layout($orientation);
        $mmPerPx = 25.4 / 96;

        return round($layout['width_mm'] / ($layout['design_width_px'] * $mmPerPx), 6);
    }

    private function normalizeBrandColor(string $color): string
    {
        return preg_match('/^#[0-9A-Fa-f]{6}$/', $color) ? $color : '#2563eb';
    }

    private function darkenColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - 30);
        $g = max(0, hexdec(substr($hex, 2, 2)) - 30);
        $b = max(0, hexdec(substr($hex, 4, 2)) - 30);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
