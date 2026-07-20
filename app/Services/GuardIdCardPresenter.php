<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\Guard;
use App\Models\GuardVerificationToken;
use App\Models\Tenant;
use App\Models\TenantSetting;

class GuardIdCardPresenter
{
    public const TEMPLATES = ['modern', 'minimal', 'creative', 'premium'];

    public const ORIENTATIONS = ['portrait', 'landscape'];

    public const PREMIUM_ORIENTATION = 'landscape';

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
     *     back_logo_path: ?string,
     *     back_logo_url: ?string,
     *     signature_path: ?string,
     *     signature_url: ?string,
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

        if ($template === 'premium') {
            $orientation = self::PREMIUM_ORIENTATION;
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
            'back_logo_path' => $settings['back_logo_path'] ?? null,
            'back_logo_url' => isset($settings['back_logo_path']) ? route('files.id-card-back-logo') : null,
            'signature_path' => $settings['signature_path'] ?? null,
            'signature_url' => isset($settings['signature_path']) ? route('files.id-card-signature') : null,
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
            'role' => $guard->rank
                ?: ($guard->dutyTypeLabel().($guard->branch?->name ? ' · '.$guard->branch->name : '')),
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

        if (array_key_exists('back_logo_path', $overrides)) {
            $overrides['back_logo_url'] = ! empty($overrides['back_logo_path'])
                ? route('files.id-card-back-logo')
                : null;
        }

        if (array_key_exists('signature_path', $overrides)) {
            $overrides['signature_url'] = ! empty($overrides['signature_path'])
                ? route('files.id-card-signature')
                : null;
        }

        $merged = array_merge($brand, $overrides);

        if (($merged['template'] ?? 'modern') === 'premium') {
            $merged['orientation'] = self::PREMIUM_ORIENTATION;
        }

        return $merged;
    }

    public function previewScale(string $orientation = 'portrait'): float
    {
        return $orientation === 'landscape' ? 0.72 : 1.0;
    }

    /**
     * @return array<string, array{primary: string, dark: string, label: string}>
     */
    public function colorPresets(): array
    {
        return [
            'blue' => ['primary' => '#2563eb', 'dark' => '#1e40af', 'label' => 'Blue'],
            'indigo' => ['primary' => '#4f46e5', 'dark' => '#3730a3', 'label' => 'Indigo'],
            'navy' => ['primary' => '#1e3a8a', 'dark' => '#172554', 'label' => 'Navy'],
            'cyan' => ['primary' => '#0891b2', 'dark' => '#155e75', 'label' => 'Cyan'],
            'teal' => ['primary' => '#0d9488', 'dark' => '#115e59', 'label' => 'Teal'],
            'green' => ['primary' => '#059669', 'dark' => '#065f46', 'label' => 'Green'],
            'lime' => ['primary' => '#65a30d', 'dark' => '#3f6212', 'label' => 'Lime'],
            'amber' => ['primary' => '#d97706', 'dark' => '#92400e', 'label' => 'Amber'],
            'orange' => ['primary' => '#ea580c', 'dark' => '#9a3412', 'label' => 'Orange'],
            'red' => ['primary' => '#dc2626', 'dark' => '#991b1b', 'label' => 'Red'],
            'rose' => ['primary' => '#e11d48', 'dark' => '#9f1239', 'label' => 'Rose'],
            'pink' => ['primary' => '#ec4899', 'dark' => '#be185d', 'label' => 'Pink'],
            'purple' => ['primary' => '#7c3aed', 'dark' => '#5b21b6', 'label' => 'Purple'],
            'violet' => ['primary' => '#6d28d9', 'dark' => '#4c1d95', 'label' => 'Violet'],
            'slate' => ['primary' => '#334155', 'dark' => '#0f172a', 'label' => 'Slate'],
            'charcoal' => ['primary' => '#18181b', 'dark' => '#09090b', 'label' => 'Charcoal'],
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

    public function darkenColor(string $hex): string
    {
        $hex = ltrim($hex, '#');
        $r = max(0, hexdec(substr($hex, 0, 2)) - 30);
        $g = max(0, hexdec(substr($hex, 2, 2)) - 30);
        $b = max(0, hexdec(substr($hex, 4, 2)) - 30);

        return sprintf('#%02x%02x%02x', $r, $g, $b);
    }
}
