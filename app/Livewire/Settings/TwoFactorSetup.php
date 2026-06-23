<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Str;
use Livewire\Component;

class TwoFactorSetup extends Component
{
    public ?string $secret = null;

    public string $code = '';

    public function mount(): void
    {
        if (! auth()->user()->two_factor_secret) {
            $this->secret = Str::upper(Str::random(16));
        }
    }

    public function enable(): void
    {
        $this->validate(['code' => 'required|string|min:6']);

        auth()->user()->update([
            'two_factor_secret' => $this->secret ?? auth()->user()->two_factor_secret,
            'two_factor_confirmed_at' => now(),
        ]);

        session(['two_factor_passed' => true]);
        session()->flash('status', 'Two-factor authentication enabled.');
    }

    public function verify(): void
    {
        $this->validate(['code' => 'required|string|min:6']);
        session(['two_factor_passed' => true]);

        $this->redirectIntended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.settings.two-factor-setup')->layout('layouts.app');
    }
}
