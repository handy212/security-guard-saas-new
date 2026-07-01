<div>
    <x-page-shell title="Subscription & Plan Limits" description="Pay with Paystack — cards, bank transfer, USSD, and mobile money.">
        <x-slot:actions>
            @if($activeSubscription)
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                    {{ ucfirst($activeSubscription->status) }} — {{ $activeSubscription->plan?->name }}
                </span>
            @endif
        </x-slot:actions>

        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Guards used" :value="$usage['guards']['used'].' / '.$usage['guards']['max']" icon="guards" :tone="$usage['guards']['used'] >= $usage['guards']['max'] ? 'danger' : 'default'" />
            <x-stat-card compact label="Sites used" :value="$usage['sites']['used'].' / '.$usage['sites']['max']" icon="sites" tone="info" />
            <x-stat-card compact label="Plans available" :value="$plans->count()" icon="plan" />
            <x-stat-card compact label="Paystack" :value="$paystackConfigured ? 'Ready' : 'Setup'" icon="billing" :tone="$paystackConfigured ? 'success' : 'warning'" />
        </div>

        @unless($paystackConfigured)
            <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                Paystack is not configured. Add <code>PAYSTACK_SECRET_KEY</code> and <code>PAYSTACK_PUBLIC_KEY</code> to enable payments.
            </div>
        @endunless

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @foreach($plans as $plan)
                <div class="flex flex-col rounded-xl border border-zinc-200 bg-white p-5 shadow-sm">
                    <h3 class="text-lg font-bold text-zinc-900">{{ $plan->name }}</h3>
                    <div class="mt-2 text-3xl font-black text-zinc-900">
                        {{ $currency === 'NGN' ? '₦' : ($currency === 'GHS' ? 'GH₵' : $currency.' ') }}{{ number_format($plan->monthly_price, 0) }}
                        <span class="text-sm font-normal text-zinc-500">/mo</span>
                    </div>
                    <ul class="mt-4 flex-1 space-y-1 text-sm text-zinc-600">
                        <li>Up to {{ $plan->max_guards ?? '∞' }} guards</li>
                        <li>Up to {{ $plan->max_sites ?? '∞' }} sites</li>
                        @if(is_array($plan->features))
                            @foreach($entitlements->labelsFor($plan->features) as $label)
                                <li class="text-zinc-500">✓ {{ $label }}</li>
                            @endforeach
                        @endif
                    </ul>
                    <button wire:click="checkout({{ $plan->id }})" class="mt-4 w-full rounded-lg bg-zinc-900 py-2.5 text-sm font-medium text-white hover:bg-zinc-800">
                        Pay with Paystack
                    </button>
                </div>
            @endforeach
        </div>

        <x-section-card title="Payment methods" description="Powered by Paystack">
            <div class="grid gap-3 text-sm text-zinc-600 md:grid-cols-4">
                <div class="rounded-lg border bg-zinc-50 p-3">Debit / credit cards</div>
                <div class="rounded-lg border bg-zinc-50 p-3">Bank transfer</div>
                <div class="rounded-lg border bg-zinc-50 p-3">USSD</div>
                <div class="rounded-lg border bg-zinc-50 p-3">Mobile money</div>
            </div>
        </x-section-card>
    </x-page-shell>
</div>
