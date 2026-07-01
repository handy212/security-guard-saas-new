<?php

namespace App\Livewire\Settings;

use App\Services\TwoFactorService;
use Livewire\Component;

class TwoFactorSetup extends Component
{
    public ?string $secret = null;

    public string $code = '';

    public function mount(TwoFactorService $twoFactor): void
    {
        if (! auth()->user()->two_factor_secret) {
            $this->secret = $twoFactor->generateSecret();
        }
    }

    public function enable(TwoFactorService $twoFactor): void
    {
        $this->validate(['code' => 'required|string|size:6']);

        $secret = $this->secret ?? auth()->user()->two_factor_secret;

        abort_unless($secret && $twoFactor->verifyCode($secret, $this->code), 422, 'Invalid authentication code.');

        auth()->user()->update([
            'two_factor_secret' => $secret,
            'two_factor_confirmed_at' => now(),
        ]);

        session(['two_factor_passed' => true]);
        session()->flash('status', 'Two-factor authentication enabled.');
    }

    public function verify(TwoFactorService $twoFactor): void
    {
        $this->validate(['code' => 'required|string|size:6']);

        abort_unless(
            auth()->user()->two_factor_secret && $twoFactor->verifyCode(auth()->user()->two_factor_secret, $this->code),
            422,
            'Invalid authentication code.'
        );

        session(['two_factor_passed' => true]);

        $this->redirectIntended(route('dashboard'));
    }

    public function render()
    {
        return view('livewire.settings.two-factor-setup')->layout('layouts.app');
    }
}
