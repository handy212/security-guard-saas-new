<div>
    <x-portal-page-header
        :title="$invoice->invoice_number"
        :description="$pageDescription"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('client-portal.invoices')">Back to invoices</x-button>
            <x-button wire:click="downloadPdf">Download PDF</x-button>
        </x-slot:actions>
    </x-portal-page-header>

    <x-flash-status type="success" />

    <div class="mx-auto max-w-3xl space-y-4">
        <x-billing.invoice-document :invoice="$invoice" />

        <x-section-card title="Payment history" flush>
            @forelse ($invoice->payments as $payment)
                <div class="list-row" wire:key="portal-pay-{{ $payment->id }}">
                    <div class="min-w-0 flex-1">
                        <div class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">
                            ₦{{ number_format($payment->amount, 2) }}
                            <span class="font-normal text-zinc-500 dark:text-zinc-400">· {{ str_replace('_', ' ', $payment->payment_method) }}</span>
                        </div>
                        <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $payment->paid_at?->format('M j, Y') }}</div>
                    </div>
                </div>
            @empty
                <div class="p-3">
                    <p class="text-sm text-zinc-500 dark:text-zinc-400">
                        @if ($balance > 0)
                            No payments recorded yet. Balance due: <span class="font-medium tabular-nums text-zinc-700 dark:text-zinc-200">₦{{ number_format($balance, 2) }}</span>.
                        @else
                            This invoice is fully paid.
                        @endif
                    </p>
                </div>
            @endforelse
        </x-section-card>
    </div>
</div>
