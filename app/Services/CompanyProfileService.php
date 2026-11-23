<?php

namespace App\Services;

use App\Models\Tenant;
use App\Models\TenantSetting;

class CompanyProfileService
{
    public const KEY = 'company_profile';

    public function __construct(private TenantFileStorageService $files) {}

    /**
     * @return array{name: string, address: ?string, tax_id: ?string, registration_number: ?string, email: ?string, phone: ?string, website: ?string, logo_path: ?string}
     */
    public function forTenant(Tenant $tenant): array
    {
        $stored = TenantSetting::query()->forTenant($tenant->id)->where('key', self::KEY)->value('value') ?? [];

        return [
            'name' => $stored['name'] ?? $tenant->name,
            'address' => $stored['address'] ?? null,
            'tax_id' => $stored['tax_id'] ?? null,
            'registration_number' => $stored['registration_number'] ?? null,
            'email' => $stored['email'] ?? null,
            'phone' => $stored['phone'] ?? null,
            'website' => $stored['website'] ?? null,
            'logo_path' => $stored['logo_path'] ?? null,
        ];
    }

    public function logoDataUri(?string $path): ?string
    {
        if ($path === null) {
            return null;
        }

        $mime = match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            default => null,
        };
        $binary = $mime ? $this->files->disk()->get($path) : null;

        return $binary ? 'data:'.$mime.';base64,'.base64_encode($binary) : null;
    }
}
