<?php

namespace App\Services;

use App\Models\EquipmentAssignment;
use App\Models\Guard;
use App\Models\Site;
use App\Models\TenantSetting;
use App\Models\User;
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
        $statusTone = $this->statusTone($guard, $isVerified, $currentAssignment);
        $issuedAssets = $this->issuedAssetsForAssignment($currentAssignment);
        $supervisor = $this->supervisorForAssignment($currentAssignment, $guard->tenant_id);

        return [
            'companyName' => $branding['company_name'],
            'companyNameUpper' => strtoupper($branding['company_name']),
            'tagline' => $page['subtitle'],
            'logoUrl' => $branding['logo_path'] ? $this->publicLogoUrl($guard, $token) : null,
            'brandColor' => $branding['brand_color'],
            'guard' => $guard,
            'branchName' => $guard->branch?->name,
            'currentAssignment' => $currentAssignment,
            'issuedAssets' => $issuedAssets,
            'supervisor' => $supervisor,
            'skills' => $skills,
            'isVerified' => $isVerified,
            'isAuthorisedToday' => $isAuthorisedToday,
            'isUnassigned' => $statusTone === 'unassigned',
            'statusTone' => $statusTone,
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
        return $this->statusTone($guard, $isVerified, $currentAssignment) === 'authorised';
    }

    /**
     * Public KYG status: suspended | unassigned | authorised | pending.
     */
    public function statusTone(Guard $guard, bool $isVerified, ?array $currentAssignment): string
    {
        if ($guard->verification_status === 'suspended') {
            return 'suspended';
        }

        if (! $isVerified || $guard->status !== 'active') {
            return 'pending';
        }

        if ($currentAssignment === null) {
            return 'unassigned';
        }

        return 'authorised';
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
     * Currently issued kit for the active shift assignment only.
     *
     * @param  array{shift_assignment_id?: int}|null  $currentAssignment
     * @return list<array{label: string, tag: ?string}>
     */
    public function issuedAssetsForAssignment(?array $currentAssignment): array
    {
        $shiftAssignmentId = $currentAssignment['shift_assignment_id'] ?? null;

        if (! $shiftAssignmentId) {
            return [];
        }

        return EquipmentAssignment::query()
            ->where('shift_assignment_id', $shiftAssignmentId)
            ->where('status', 'issued')
            ->whereNull('returned_at')
            ->with('asset')
            ->orderBy('issued_at')
            ->get()
            ->map(function (EquipmentAssignment $assignment) {
                $asset = $assignment->asset;
                if (! $asset) {
                    return null;
                }

                $name = trim((string) ($asset->name ?: $asset->asset_tag));
                if ($name === '') {
                    return null;
                }

                return [
                    'label' => $name,
                    'tag' => $asset->asset_tag ?: null,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Site supervisor for the current assignment (option B).
     *
     * @param  array{site_id?: int}|null  $currentAssignment
     * @return array{name: string, phone: ?string, email: ?string, role_label: string}|null
     */
    public function supervisorForAssignment(?array $currentAssignment, int $tenantId): ?array
    {
        $siteId = $currentAssignment['site_id'] ?? null;

        if (! $siteId) {
            return null;
        }

        $site = Site::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($siteId)
            ->with('supervisor')
            ->first();

        $user = $site?->supervisor;

        if (! $user instanceof User || $user->status !== 'active') {
            return null;
        }

        $phone = $user->phone ? trim((string) $user->phone) : null;
        $email = $user->email ? trim((string) $user->email) : null;

        if (! $phone && ! $email) {
            return null;
        }

        return [
            'name' => $user->name,
            'phone' => $phone ?: null,
            'email' => $email ?: null,
            'role_label' => 'Site supervisor',
        ];
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
        if (! is_array($appearance) || $appearance === []) {
            $appearance = $defaults['expected_appearance'] ?? [];
        }

        // Strip legacy kit placeholders — radios/bodycams belong under issued shift assets.
        $legacyKitPlaceholders = [
            'company radio',
            'bodycam / guard tour device',
            'bodycam',
            'guard tour device',
        ];
        $merged['expected_appearance'] = array_values(array_filter(
            $appearance,
            fn ($item) => ! in_array(strtolower(trim((string) $item)), $legacyKitPlaceholders, true)
        ));

        $merged['report_concern_phone'] = $stored['report_concern_phone'] ?? null;
        $merged['report_concern_email'] = $stored['report_concern_email'] ?? null;

        return $merged;
    }
}
