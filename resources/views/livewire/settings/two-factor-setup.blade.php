<div class="p-6">
    <x-page-header title="Two-Factor Authentication" description="Protect admin accounts with an additional verification step." />

    <div class="mx-auto mt-6 max-w-lg rounded-xl border bg-white p-6">
        @if(auth()->user()->two_factor_confirmed_at)
            <p class="text-sm text-emerald-700">Two-factor authentication is enabled on your account.</p>
            <form wire:submit="verify" class="mt-4 space-y-3">
                <input wire:model="code" class="w-full rounded-lg border px-3 py-2" placeholder="Enter verification code">
                <button class="w-full rounded-lg bg-slate-900 py-2 text-white">Verify session</button>
            </form>
        @else
            <p class="text-sm text-slate-600">Save this setup key in your authenticator app:</p>
            <code class="mt-2 block rounded bg-slate-100 p-3 text-sm">{{ $secret }}</code>
            <form wire:submit="enable" class="mt-4 space-y-3">
                <input wire:model="code" class="w-full rounded-lg border px-3 py-2" placeholder="Enter code to confirm">
                <button class="w-full rounded-lg bg-slate-900 py-2 text-white">Enable 2FA</button>
            </form>
        @endif
    </div>
</div>
