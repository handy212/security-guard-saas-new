<?php

namespace App\Livewire\Settings;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use Livewire\Component;

class SettingsHub extends Component
{
    use AuthorizesModuleAccess;

    public function mount(): void
    {
        $this->authorizePermission('settings.manage');
    }

    public function render()
    {
        return view('livewire.settings.settings-hub')->layout('layouts.app');
    }
}
