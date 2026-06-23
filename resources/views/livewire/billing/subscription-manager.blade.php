<div>
    <x-page-header title="Subscription & Plan Limits" description="Pay with Paystack — cards, bank transfer, USSD, and mobile money.">
        <x-slot:actions>
            @if($activeSubscription)
                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-800">
                    {{ ucfirst($activeSubscription->status) }} — {{ $activeSubscription->plan?->name }}
                </span>
            @endif
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-4 p-6 md:grid-cols-2">
        <x-stat-card label="Guards used" :value="$usage['guards']['used'].' / '.$usage['guards']['max']" />
        <x-stat-card label="Sites used" :value="$usage['sites']['used'].' / '.$usage['sites']['max']" tone="info" />
    </div>

    @unless($paystackConfigured)
        <div class="mx-6 mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            Paystack is not configured. Add <code>PAYSTACK_SECRET_KEY</code> and <code>PAYSTACK_PUBLIC_KEY</code> to enable payments.
        </div>
    @endunless

    <div class="grid gap-4 p-6 md:grid-cols-3">
        @foreach($plans as $plan)
            <div class="rounded-xl border bg-white p-5 shadow-sm transition hover:shadow-md">
                <h3 class="text-lg font-bold">{{ $plan->name }}</h3>
                <div class="mt-2 text-3xl font-black">
                    {{ $currency === 'NGN' ? '₦' : ($currency === 'GHS' ? 'GH₵' : $currency.' ') }}{{ number_format($plan->monthly_price, 0) }}
                    <span class="text-sm font-normal text-slate-500">/mo</span>
                </div>
                <ul class="mt-4 space-y-1 text-sm text-slate-600">
                    <li>Up to {{ $plan->max_guards }} guards</li>
                    <li>Up to {{ $plan->max_sites }} sites</li>
                    @if(is_array($plan->features))
                        @foreach(array_slice($plan->features, 0, 3) as $feature)
                            <li class="text-slate-500">{{ str_replace('_', ' ', $feature) }}</li>
                        @endforeach
                    @endif
                </ul>
                <button wire:click="checkout({{ $plan->id }})" class="mt-4 w-full rounded-lg bg-slate-900 py-2.5 text-sm font-medium text-white hover:bg-slate-800">
                    Pay with Paystack
                </button>
            </div>
        @endforeach
    </div>

    <div class="px-6 pb-8">
        <x-section-card title="Payment methods" description="Powered by Paystack">
            <div class="grid gap-3 text-sm text-slate-600 md:grid-cols-4">
                <div class="rounded-lg border bg-slate-50 p-3">Debit / credit cards</div>
                <div class="rounded-lg border bg-slate-50 p-3">Bank transfer</div>
                <div class="rounded-lg border bg-slate-50 p-3">USSD</div>
                <div class="rounded-lg border bg-slate-50 p-3">Mobile money</div>
            </div>
        </x-section-card>
    </div>
</div>
