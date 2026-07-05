<?php

namespace App\Livewire\Settings;

use App\Models\TenantSetting;
use App\Services\FileUploadService;
use App\Services\GuardIdCardPresenter;
use App\Services\GuardIdCardRenderService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class IdCardSettings extends Component
{
    use WithFileUploads;

    public string $template = 'modern';

    public string $orientation = 'portrait';

    public string $tagline = '';

    public string $brandColor = '#2563eb';

    public string $brandColorDark = '#1e40af';

    public string $emergencyText = '';

    public ?string $phone = null;

    public ?string $phoneSecondary = null;

    public ?string $email = null;

    public ?string $website = null;

    public ?string $address = null;

    public ?string $existingLogoPath = null;

    public $logoFile;

    public string $previewSide = 'front';

    public function mount(GuardIdCardPresenter $presenter): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $settings = TenantSetting::query()
            ->where('tenant_id', TenantContext::id())
            ->where('key', 'id_card')
            ->value('value') ?? [];

        $this->template = $settings['template'] ?? 'modern';
        $this->orientation = in_array($settings['orientation'] ?? 'portrait', GuardIdCardPresenter::ORIENTATIONS, true)
            ? ($settings['orientation'] ?? 'portrait')
            : 'portrait';
        $this->tagline = $settings['tagline'] ?? 'Employee Identification';
        $this->brandColor = $settings['brand_color'] ?? '#2563eb';
        $this->brandColorDark = $settings['brand_color_dark'] ?? '#1e40af';
        $this->emergencyText = $settings['emergency_text'] ?? '';
        $this->phone = $settings['phone'] ?? null;
        $this->phoneSecondary = $settings['phone_secondary'] ?? null;
        $this->email = $settings['email'] ?? null;
        $this->website = $settings['website'] ?? null;
        $this->address = $settings['address'] ?? null;
        $this->existingLogoPath = $settings['logo_path'] ?? null;
    }

    public function setOrientation(string $orientation): void
    {
        if (! in_array($orientation, GuardIdCardPresenter::ORIENTATIONS, true)) {
            return;
        }

        $this->orientation = $orientation;
    }

    public function setTemplate(string $template): void
    {
        if (! in_array($template, GuardIdCardPresenter::TEMPLATES, true)) {
            return;
        }

        $this->template = $template;
    }

    public function setColor(string $primary, string $dark): void
    {
        $this->brandColor = $primary;
        $this->brandColorDark = $dark;
    }

    public function removeLogo(): void
    {
        if ($this->existingLogoPath && Storage::disk('public')->exists($this->existingLogoPath)) {
            Storage::disk('public')->delete($this->existingLogoPath);
        }

        $this->persistSettings($this->currentSettings(['logo_path' => null]));
        $this->existingLogoPath = null;
        $this->logoFile = null;
        session()->flash('status', 'Logo removed.');
    }

    public function save(FileUploadService $files): void
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        $data = $this->validate([
            'template' => 'required|in:modern,minimal,creative',
            'orientation' => 'required|in:portrait,landscape',
            'tagline' => 'required|string|max:120',
            'brandColor' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'brandColorDark' => 'required|regex:/^#[0-9A-Fa-f]{6}$/',
            'emergencyText' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:40',
            'phoneSecondary' => 'nullable|string|max:40',
            'email' => 'nullable|email|max:120',
            'website' => 'nullable|string|max:120',
            'address' => 'nullable|string|max:255',
            'logoFile' => 'nullable|image|max:2048',
        ]);

        $logoPath = $this->existingLogoPath;

        if ($this->logoFile) {
            if ($logoPath && Storage::disk('public')->exists($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }
            $logoPath = $files->storeIdCardLogo(TenantContext::id(), $this->logoFile);
            $this->existingLogoPath = $logoPath;
            $this->logoFile = null;
        }

        $this->persistSettings($this->currentSettings([
            'template' => $data['template'],
            'orientation' => $data['orientation'],
            'tagline' => $data['tagline'],
            'brand_color' => $data['brandColor'],
            'brand_color_dark' => $data['brandColorDark'],
            'emergency_text' => $data['emergencyText'] ?: null,
            'phone' => $data['phone'] ?: null,
            'phone_secondary' => $data['phoneSecondary'] ?: null,
            'email' => $data['email'] ?: null,
            'website' => $data['website'] ?: null,
            'address' => $data['address'] ?: null,
            'logo_path' => $logoPath,
        ]));

        session()->flash('status', 'ID card settings saved.');
    }

    public function render(GuardIdCardPresenter $presenter, GuardIdCardRenderService $renderer): \Illuminate\Contracts\View\View
    {
        $tenant = TenantContext::current();
        abort_unless($tenant, 403);

        $sample = $renderer->forSample($tenant, [
            'tagline' => $this->tagline,
            'template' => $this->template,
            'orientation' => $this->orientation,
            'brand_color' => $this->brandColor,
            'brand_color_dark' => $this->brandColorDark,
            'emergency_text' => $this->emergencyText ?: null,
            'phone' => $this->phone,
            'phone_secondary' => $this->phoneSecondary,
            'email' => $this->email,
            'website' => $this->website,
            'address' => $this->address,
            'logo_path' => $this->existingLogoPath,
        ]);

        $logoUrl = $this->existingLogoPath ? route('files.id-card-logo') : null;
        if ($this->logoFile) {
            try {
                $logoUrl = $this->logoFile->temporaryUrl();
            } catch (\Throwable) {
                // Preview falls back to saved logo when temporary URL is unavailable.
            }
        }

        return view('livewire.settings.id-card-settings', [
            'colorPresets' => $presenter->colorPresets(),
            'previewBrand' => $sample['brand'],
            'previewCard' => $sample['card'],
            'previewQrSvg' => $sample['qrSvg'],
            'logoUrl' => $logoUrl,
        ])->layout('layouts.app');
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function currentSettings(array $overrides = []): array
    {
        return array_merge([
            'template' => $this->template,
            'orientation' => $this->orientation,
            'tagline' => $this->tagline,
            'brand_color' => $this->brandColor,
            'brand_color_dark' => $this->brandColorDark,
            'emergency_text' => $this->emergencyText ?: null,
            'phone' => $this->phone ?: null,
            'phone_secondary' => $this->phoneSecondary ?: null,
            'email' => $this->email ?: null,
            'website' => $this->website ?: null,
            'address' => $this->address ?: null,
            'logo_path' => $this->existingLogoPath,
        ], $overrides);
    }

    private function persistSettings(array $value): void
    {
        TenantSetting::updateOrCreate(
            ['tenant_id' => TenantContext::id(), 'key' => 'id_card'],
            ['value' => $value]
        );
    }
}
