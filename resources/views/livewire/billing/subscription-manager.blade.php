@php
    $currencySymbol = match ($currency) {
        'NGN' => '₦',
        'GHS' => 'GH₵',
        'USD' => '$',
        default => $currency.' ',
    };
@endphp

<div>
    <x-page-shell
        title="Your GuardOps plan"
        description="Manage your platform subscription and usage limits. Client invoices and guard payroll are under Finance."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'Your plan'],
        ]"
    >
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            @unless($paystackConfigured)
                <div class="rounded-lg border border-zinc-200 bg-zinc-50 px-4 py-3 text-sm text-zinc-700" role="status">
                    Online checkout is not available for your account yet. Contact GuardOps support to change or upgrade your plan.
                </div>
            @endunless

            <x-section-card title="Current plan" description="Your organization's active GuardOps subscription.">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="min-w-0 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-zinc-900">
                                {{ $currentPlan?->name ?? 'No plan selected' }}
                            </h3>
                            @if ($activeSubscription?->status)
                                <x-badge :status="$activeSubscription->status" />
                            @endif
                        </div>

                        @if ($activeSubscription?->trial_ends_at && $activeSubscription->status === 'trial')
                            <p class="text-sm text-zinc-600">
                                Trial ends {{ $activeSubscription->trial_ends_at->format('M j, Y') }}
                                @if ($activeSubscription->trial_ends_at->isFuture())
                                    ({{ $activeSubscription->trial_ends_at->diffForHumans() }})
                                @endif
                            </p>
                        @elseif ($activeSubscription?->ends_at && $activeSubscription->status === 'active')
                            <p class="text-sm text-zinc-600">
                                Renews {{ $activeSubscription->ends_at->format('M j, Y') }}
                            </p>
                        @else
                            <p class="text-sm text-zinc-500">
                                Choose a plan below to unlock modules and raise guard/site limits.
                            </p>
                        @endif
                    </div>

                    @if ($activeSubscription?->status === 'active')
                        <button
                            type="button"
                            wire:click="cancelSubscription"
                            wire:confirm="Cancel your recurring subscription? You will keep access until the current billing period ends."
                            class="shrink-0 rounded-lg border border-red-200 px-3 py-1.5 text-xs font-medium text-red-700 hover:bg-red-50"
                        >
                            Cancel subscription
                        </button>
                    @endif
                </div>

                <div class="mt-5 grid gap-4 sm:grid-cols-2">
                    @foreach (['guards' => 'Guards', 'sites' => 'Sites'] as $key => $label)
                        @php
                            $row = $usage[$key];
                            $pct = min(100, (float) ($row['pct'] ?? 0));
                            $barTone = $pct >= 100 ? 'bg-red-500' : ($pct >= 80 ? 'bg-amber-500' : 'bg-accent-600');
                        @endphp
                        <div>
                            <div class="mb-1 flex items-center justify-between text-sm">
                                <span class="font-medium text-zinc-700">{{ $label }}</span>
                                <span class="tabular-nums text-zinc-600">{{ $row['used'] }} / {{ $row['max'] }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-zinc-100">
                                <div class="{{ $barTone }} h-full rounded-full transition-all" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-section-card>

            <div>
                <div class="mb-3">
                    <h2 class="text-sm font-semibold text-zinc-900">Available plans</h2>
                    <p class="text-xs text-zinc-500">Upgrade or switch plans. Payments are processed securely via Paystack.</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    @foreach ($plans as $plan)
                        @php
                            $isCurrent = $currentPlanId === $plan->id;
                            $featureLabels = is_array($plan->features)
                                ? $entitlements->labelsFor($plan->features)
                                : [];
                            $previewFeatures = array_slice($featureLabels, 0, 8);
                            $moreFeatures = max(0, count($featureLabels) - count($previewFeatures));
                        @endphp
                        <div @class([
                            'flex flex-col rounded-xl border p-5',
                            'border-accent-300 bg-accent-50/40 ring-1 ring-accent-200' => $isCurrent,
                            'border-zinc-200 bg-white' => ! $isCurrent,
                        ])>
                            <div class="flex items-start justify-between gap-2">
                                <h3 class="text-base font-bold text-zinc-900">{{ $plan->name }}</h3>
                                @if ($isCurrent)
                                    <span class="rounded-full bg-accent-100 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-accent-800">Current</span>
                                @endif
                            </div>

                            <div class="mt-2">
                                <span class="text-3xl font-black tabular-nums text-zinc-900">
                                    {{ $currencySymbol }}{{ number_format($plan->monthly_price, 0) }}
                                </span>
                                <span class="text-sm text-zinc-500">/ month</span>
                                @if ($plan->annual_price)
                                    <p class="mt-1 text-xs text-zinc-500">
                                        or {{ $currencySymbol }}{{ number_format($plan->annual_price, 0) }}/year
                                    </p>
                                @endif
                            </div>

                            <ul class="mt-4 space-y-1.5 text-sm text-zinc-600">
                                <li>Up to {{ $plan->max_guards ?? '∞' }} guards</li>
                                <li>Up to {{ $plan->max_sites ?? '∞' }} sites</li>
                                @foreach ($previewFeatures as $feature)
                                    <li class="flex items-start gap-1.5 text-zinc-500">
                                        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                                @if ($moreFeatures > 0)
                                    <li class="text-xs text-zinc-400">+ {{ $moreFeatures }} more modules</li>
                                @endif
                            </ul>

                            <div class="mt-5 pt-2">
                                @if ($isCurrent)
                                    <x-button class="w-full" variant="secondary" disabled>Current plan</x-button>
                                @else
                                    <x-button wire:click="checkout({{ $plan->id }})" class="w-full" :disabled="! $paystackConfigured">
                                        {{ $currentPlan ? 'Switch to '.$plan->name : 'Choose '.$plan->name }}
                                    </x-button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <p class="text-xs text-zinc-400">
                Payments support cards, bank transfer, USSD, and mobile money via Paystack.
                This subscription is separate from client invoicing and guard payroll under Finance.
            </p>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
