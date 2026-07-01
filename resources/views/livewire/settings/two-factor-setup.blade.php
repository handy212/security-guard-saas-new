<div>
    <x-page-shell title="Two-Factor Authentication" description="Protect admin accounts with an additional verification step.">
        <x-settings-nav />

        <x-form-card title="Authenticator setup" description="Use Google Authenticator, Authy, or any TOTP app." class="mx-auto max-w-lg">
            @if(auth()->user()->two_factor_confirmed_at)
                <div class="mb-4 rounded-lg bg-emerald-50 px-3 py-2 text-sm text-emerald-800 ring-1 ring-emerald-600/20">
                    Two-factor authentication is enabled on your account.
                </div>
                <form wire:submit="verify" class="space-y-4">
                    <x-input wire:model="code" label="Verification code" placeholder="000000" autocomplete="one-time-code" />
                    <x-button type="submit" class="w-full">Verify session</x-button>
                </form>
            @else
                <p class="text-sm text-zinc-600">Save this setup key in your authenticator app:</p>
                <code class="mt-3 block rounded-lg bg-zinc-100 px-3 py-2 text-sm font-mono text-zinc-800">{{ $secret }}</code>
                <form wire:submit="enable" class="mt-6 space-y-4">
                    <x-input wire:model="code" label="Confirm with code" placeholder="Enter 6-digit code" autocomplete="one-time-code" />
                    <x-button type="submit" class="w-full">Enable 2FA</x-button>
                </form>
            @endif
        </x-form-card>
    </x-page-shell>
</div>
