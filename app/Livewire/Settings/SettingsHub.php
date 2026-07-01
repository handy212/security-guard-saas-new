<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class SettingsHub extends Component
{
    public function render()
    {
        abort_unless(auth()->user()->can('settings.manage'), 403);

        return view('livewire.settings.settings-hub')->layout('layouts.app');
    }
}
