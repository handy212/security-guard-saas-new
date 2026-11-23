<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\TenantSetting;
use App\Services\AuditLogService;
use App\Services\TenantBrandingService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Livewire\Component;

class ThemeSettings extends Component
{
    use AuthorizesModuleAccess;

    public ?string $lightColor = null;

    public ?string $darkColor = null;

    public function mount(TenantBrandingService $branding): void
    {
        $this->authorizePermission('settings.manage');
        $theme = $branding->themeForTenant(TenantContext::id());
        $this->lightColor = $theme['light'];
        $this->darkColor = $theme['dark'];
    }

    public function save(AuditLogService $audit): void
    {
        $this->authorizePermission('settings.manage');
        $tenantId = TenantContext::id();
        $validated = $this->validate([
            'lightColor' => ['nullable', 'regex:/\A#[0-9a-f]{6}\z/i'],
            'darkColor' => ['nullable', 'regex:/\A#[0-9a-f]{6}\z/i'],
        ], [
            'lightColor.regex' => 'Enter a six-digit hex color, such as #0f766e.',
            'darkColor.regex' => 'Enter a six-digit hex color, such as #0f766e.',
        ]);

        $theme = [
            'light' => filled($validated['lightColor']) ? strtolower($validated['lightColor']) : null,
            'dark' => filled($validated['darkColor']) ? strtolower($validated['darkColor']) : null,
        ];

        DB::transaction(function () use ($tenantId, $theme, $audit): void {
            $setting = TenantSetting::updateOrCreate(
                ['tenant_id' => $tenantId, 'key' => TenantBrandingService::THEME_KEY],
                ['value' => $theme],
            );
            $audit->record('settings.theme.updated', $setting, $theme);
        });

        session()->flash('status', 'Organization color scheme saved.');
        $this->redirectRoute('settings.themes');
    }

    public function render(): View
    {
        return view('livewire.settings.theme-settings')->layout('layouts.app');
    }
}
