<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\TenantSetting;
use App\Services\AuditLogService;
use App\Services\CompanyProfileService;
use App\Services\TenantFileStorageService;
use App\Support\TenantContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;

class CompanyProfile extends Component
{
    use AuthorizesModuleAccess, WithFileUploads;

    public array $profile = [];

    public ?UploadedFile $logo = null;

    public bool $removeLogo = false;

    public function mount(CompanyProfileService $profiles): void
    {
        $this->authorizePermission('settings.manage');
        $this->profile = collect($profiles->forTenant(TenantContext::current()))->except('logo_path')->all();
    }

    public function save(TenantFileStorageService $files, AuditLogService $audit): void
    {
        $this->authorizePermission('settings.manage');
        $data = $this->validate([
            'profile' => ['required', 'array:name,address,tax_id,registration_number,email,phone,website'],
            'profile.name' => ['required', 'string', 'max:255'],
            'profile.address' => ['nullable', 'string', 'max:500'],
            'profile.tax_id' => ['nullable', 'string', 'max:80'],
            'profile.registration_number' => ['nullable', 'string', 'max:80'],
            'profile.email' => ['nullable', 'email', 'max:255'],
            'profile.phone' => ['nullable', 'string', 'max:40'],
            'profile.website' => ['nullable', 'url:http,https', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:png,jpg,jpeg', 'max:1024', 'dimensions:max_width=2000,max_height=2000'],
        ]);

        $tenantId = TenantContext::id();
        $newLogo = $this->logo ? $files->store($this->logo, "tenants/{$tenantId}/document-logos") : null;

        try {
            DB::transaction(function () use ($tenantId, $newLogo, $data, $audit): void {
                $setting = TenantSetting::query()->firstOrCreate(
                    ['tenant_id' => $tenantId, 'key' => CompanyProfileService::KEY],
                    ['value' => []],
                );
                $setting = TenantSetting::query()->lockForUpdate()->findOrFail($setting->id);
                $logoPath = $newLogo ?? ($this->removeLogo ? null : ($setting->value['logo_path'] ?? null));

                $setting->update(['value' => $data['profile'] + ['logo_path' => $logoPath]]);
                $audit->record('settings.company-profile.updated', $setting);
            });
        } catch (\Throwable $exception) {
            if ($newLogo !== null) {
                $files->delete($newLogo);
            }

            throw $exception;
        }

        $this->reset('logo', 'removeLogo');
        session()->flash('status', 'Company profile saved.');
    }

    public function render(CompanyProfileService $profiles): View
    {
        $profile = $profiles->forTenant(TenantContext::current());

        return view('livewire.settings.company-profile', [
            'currentLogo' => $profiles->logoDataUri($profile['logo_path']),
        ])->layout('layouts.app');
    }
}
