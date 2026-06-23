<div>
    <x-page-header title="Subscription & Plan Limits" description="Manage your SaaS plan and monitor usage." />

    <div class="grid gap-4 p-6 md:grid-cols-2">
        <x-stat-card label="Guards used" :value="$usage['guards']['used'].' / '.$usage['guards']['max']" />
        <x-stat-card label="Sites used" :value="$usage['sites']['used'].' / '.$usage['sites']['max']" tone="info" />
    </div>

    @unless($stripeConfigured)
        <div class="mx-6 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Stripe is not configured. Add <code>STRIPE_SECRET</code> and <code>STRIPE_KEY</code> to enable checkout.
        </div>
    @endunless

    <div class="grid gap-4 p-6 md:grid-cols-3">
        @foreach($plans as $plan)
            <div class="rounded-xl border bg-white p-5">
                <h3 class="text-lg font-bold">{{ $plan->name }}</h3>
                <div class="mt-2 text-3xl font-black">${{ number_format($plan->monthly_price, 0) }}<span class="text-sm font-normal text-slate-500">/mo</span></div>
                <ul class="mt-4 space-y-1 text-sm text-slate-600">
                    <li>Up to {{ $plan->max_guards }} guards</li>
                    <li>Up to {{ $plan->max_sites }} sites</li>
                </ul>
                <button wire:click="checkout({{ $plan->id }})" class="mt-4 w-full rounded-lg bg-slate-900 py-2 text-sm font-medium text-white">Choose plan</button>
            </div>
        @endforeach
    </div>
</div>
