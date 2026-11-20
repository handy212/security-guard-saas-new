<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Services\AuditLogService;
use App\Services\TenantCurrencyService;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Component;

class SettingsHub extends Component
{
    use AuthorizesModuleAccess;

    public string $currency = 'GHS';

    public function mount(): void
    {
        $this->authorizePermission('settings.manage');
        $this->currency = app(TenantCurrencyService::class)->code();
    }

    public function saveCurrency(AuditLogService $audit, TenantCurrencyService $currencies): void
    {
        $this->authorizePermission('settings.manage');
        $data = $this->validate([
            'currency' => ['required', Rule::in($currencies->supportedCodes())],
        ]);

        $tenant = TenantContext::current() ?? auth()->user()->tenant;
        $tenant->update(['currency' => $data['currency']]);
        $audit->record('settings.currency.updated', $tenant, ['currency' => $data['currency']]);
        session()->flash('status', 'Organization currency updated.');
    }

    public function render()
    {
        return view('livewire.settings.settings-hub', [
            'currencyOptions' => app(TenantCurrencyService::class)->options(),
        ])->layout('layouts.app');
    }
}
