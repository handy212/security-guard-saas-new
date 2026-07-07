<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\TenantSetting;
use Illuminate\Support\Collection;

class GuardVerificationPagePresenter
{
    public function __construct(
        private GuardIdCardPresenter $idCardPresenter,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function present(
        Guard $guard,
        string $token,
        ?array $currentAssignment,
        Collection $skills,
        bool $isVerified,
        ?\Illuminate\Support\Carbon $verifiedAt,
        \Illuminate\Support\Carbon $scannedAt,
        ?string $photoUrl,
    ): array {
        $guard->loadMissing(['tenant', 'branch']);

        $tenant = $guard->tenant;
        $branding = $this->idCardPresenter->branding($tenant, $guard->branch);
        $page = $this->pageSettings($guard->tenant_id, $branding['company_name']);

        $phones = array_values(array_filter([
            $branding['phone'],
            $branding['phone_secondary'],
        ]));

        $reportPhone = ($page['report_concern_phone'] ?? null) ?: ($branding['phone_secondary'] ?: $branding['phone']);
        $reportEmail = ($page['report_concern_email'] ?? null) ?: $branding['email'];

        $isAuthorisedToday = $this->isAuthorisedToday($guard, $isVerified, $currentAssignment);

        return [
            'companyName' => $branding['company_name'],
            'companyNameUpper' => strtoupper($branding['company_name']),
            'tagline' => $page['subtitle'],
            'logoUrl' => $branding['logo_path'] ? $this->publicLogoUrl($guard, $token) : null,
            'brandColor' => $branding['brand_color'],
            'guard' => $guard,
            'branchName' => $guard->branch?->name,
            'currentAssignment' => $currentAssignment,
            'skills' => $skills,
            'isVerified' => $isVerified,
            'isAuthorisedToday' => $isAuthorisedToday,
            'verifiedAt' => $verifiedAt,
            'scannedAt' => $scannedAt,
            'photoUrl' => $photoUrl,
            'phones' => $phones,
            'reportPhone' => $reportPhone,
            'reportEmail' => $reportEmail,
            'primaryPhone' => $branding['phone'],
            'page' => $page,
        ];
    }

    public function isAuthorisedToday(Guard $guard, bool $isVerified, ?array $currentAssignment): bool
    {
        if (! $isVerified || $guard->status !== 'active') {
            return false;
        }

        if ($guard->show_current_assignment) {
            return $currentAssignment !== null;
        }

        return true;
    }

    public function publicLogoUrl(Guard $guard, string $token): ?string
    {
        $slug = $guard->tenant?->slug;

        if ($slug) {
            return route('guard.verify.logo', ['tenant' => $slug, 'token' => $token]);
        }

        return route('guard.verify.logo.legacy', $token);
    }

    /**
     * @return array<string, mixed>
     */
    private function pageSettings(int $tenantId, string $companyName): array
    {
        $defaults = config('guard_verification.page', []);
        $stored = TenantSetting::query()
            ->where('tenant_id', $tenantId)
            ->where('key', 'verification')
            ->value('value')['page'] ?? [];

        $merged = array_merge($defaults, array_filter($stored, fn ($value) => $value !== null && $value !== ''));

        $merged['access_guidance'] = str_replace(
            ['{company}', 'Response One'],
            $companyName,
            $merged['access_guidance'] ?? $defaults['access_guidance']
        );

        $merged['security_notice'] = $merged['security_notice'] ?? $defaults['security_notice'];

        $appearance = $stored['expected_appearance'] ?? null;
        if (is_string($appearance)) {
            $appearance = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $appearance) ?: [])));
        }
        $merged['expected_appearance'] = ! empty($appearance)
            ? $appearance
            : ($defaults['expected_appearance'] ?? []);

        $merged['report_concern_phone'] = $stored['report_concern_phone'] ?? null;
        $merged['report_concern_email'] = $stored['report_concern_email'] ?? null;

        return $merged;
    }
}
