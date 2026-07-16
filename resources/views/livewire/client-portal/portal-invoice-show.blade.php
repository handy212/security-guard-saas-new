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

        <div class="rounded-xl border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <h3 class="mb-3 text-sm font-semibold text-zinc-900 dark:text-zinc-100">Payment history</h3>
            @forelse ($invoice->payments as $payment)
                <div class="flex justify-between gap-3 border-t border-zinc-100 py-2 text-sm first:border-0 dark:border-zinc-800" wire:key="portal-pay-{{ $payment->id }}">
                    <div>
                        <div class="font-medium">₦{{ number_format($payment->amount, 2) }} · {{ str_replace('_', ' ', $payment->payment_method) }}</div>
                        <div class="text-xs text-zinc-500">{{ $payment->paid_at?->format('M j, Y') }}</div>
                    </div>
                </div>
            @empty
                <p class="text-sm text-zinc-500">
                    @if ($balance > 0)
                        No payments recorded yet. Balance due: ₦{{ number_format($balance, 2) }}.
                    @else
                        This invoice is fully paid.
                    @endif
                </p>
            @endforelse
        </div>
    </div>
</div>
