<?php

namespace App\Livewire\Settings;

use App\Models\TenantSetting;
use App\Support\TenantContext;
use Livewire\Component;

class KygPageSettings extends Component
{
    public string $subtitle = '';

    public string $accessGuidance = '';

    public string $securityNotice = '';

    public string $verifiedByLabel = '';

    public string $expectedAppearanceText = '';

    public ?string $reportConcernPhone = null;

    public ?string $reportConcernEmail = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $defaults = config('guard_verification.page', []);
        $stored = TenantSetting::query()
            ->where('tenant_id', TenantContext::id())
            ->where('key', 'verification')
            ->value('value')['page'] ?? [];

        $this->subtitle = $stored['subtitle'] ?? $defaults['subtitle'] ?? '';
        $this->accessGuidance = $stored['access_guidance'] ?? $defaults['access_guidance'] ?? '';
        $this->securityNotice = $stored['security_notice'] ?? $defaults['security_notice'] ?? '';
        $this->verifiedByLabel = $stored['verified_by_label'] ?? $defaults['verified_by_label'] ?? '';
        $this->reportConcernPhone = $stored['report_concern_phone'] ?? null;
        $this->reportConcernEmail = $stored['report_concern_email'] ?? null;

        $appearance = $stored['expected_appearance'] ?? $defaults['expected_appearance'] ?? [];
        if (! is_array($appearance)) {
            $appearance = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $appearance) ?: [])));
        }
        $this->expectedAppearanceText = implode("\n", $this->sanitizeAppearanceItems($appearance));
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $this->validate([
            'subtitle' => ['required', 'string', 'max:120'],
            'accessGuidance' => ['required', 'string', 'max:500'],
            'securityNotice' => ['required', 'string', 'max:500'],
            'verifiedByLabel' => ['required', 'string', 'max:80'],
            'expectedAppearanceText' => ['nullable', 'string', 'max:1000'],
            'reportConcernPhone' => ['nullable', 'string', 'max:40'],
            'reportConcernEmail' => ['nullable', 'email', 'max:120'],
        ]);

        $existing = TenantSetting::query()
            ->where('tenant_id', TenantContext::id())
            ->where('key', 'verification')
            ->value('value') ?? [];

        $appearance = $this->sanitizeAppearanceItems(
            array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', $this->expectedAppearanceText) ?: [])))
        );

        $existing['page'] = array_merge($existing['page'] ?? [], [
            'subtitle' => $this->subtitle,
            'access_guidance' => $this->accessGuidance,
            'security_notice' => $this->securityNotice,
            'verified_by_label' => $this->verifiedByLabel,
            'expected_appearance' => $appearance,
            'report_concern_phone' => $this->reportConcernPhone ?: null,
            'report_concern_email' => $this->reportConcernEmail ?: null,
        ]);

        TenantSetting::updateOrCreate(
            ['tenant_id' => TenantContext::id(), 'key' => 'verification'],
            ['value' => $existing],
        );

        session()->flash('status', 'Know Your Guard page settings saved.');
    }

    public function render()
    {
        return view('livewire.settings.kyg-page-settings')->layout('layouts.app');
    }

    /**
     * @param  list<string>  $items
     * @return list<string>
     */
    private function sanitizeAppearanceItems(array $items): array
    {
        $legacyKitPlaceholders = [
            'company radio',
            'bodycam / guard tour device',
            'bodycam',
            'guard tour device',
        ];

        return array_values(array_filter(
            $items,
            fn ($item) => $item !== '' && ! in_array(strtolower(trim((string) $item)), $legacyKitPlaceholders, true)
        ));
    }
}
