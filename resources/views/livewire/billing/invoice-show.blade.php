<div>
    <x-page-shell
        :title="$invoice->invoice_number"
        :description="($invoice->clientAccount?->name ?? 'Invoice').' · Document view'"
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Invoices', 'href' => route('billing.invoices')],
            ['label' => $invoice->invoice_number],
        ]"
    >
        <x-slot:actions>
            @if ($status === 'draft')
                <x-button variant="secondary" :href="route('billing.invoices.edit', $invoice)">Edit</x-button>
                <x-button variant="secondary" wire:click="markSent">Mark as sent</x-button>
            @endif
            @if (in_array($status, ['draft', 'sent', 'partial', 'overdue'], true))
                <x-button wire:click="openSend">Send invoice</x-button>
            @endif
            @if (! in_array($status, ['paid', 'void'], true))
                <x-button variant="secondary" wire:click="openPayment">Record payment</x-button>
            @endif
            <x-button variant="secondary" wire:click="exportPdf">Download PDF</x-button>
        </x-slot:actions>

        <x-flash-status type="success" />

        <div class="grid gap-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="billing-master-list hidden max-h-[70vh] overflow-y-auto lg:flex">
                <div class="border-b border-zinc-100 p-3 dark:border-zinc-800">
                    <x-search-input wire:model.live.debounce.300ms="listSearch" placeholder="Search invoices…" />
                </div>
                <div class="flex-1 overflow-y-auto">
                    @forelse ($siblings as $sibling)
                        <a
                            href="{{ route('billing.invoices.show', $sibling) }}"
                            wire:navigate
                            class="billing-master-item {{ $sibling->id === $invoice->id ? 'billing-master-item-active' : '' }}"
                            wire:key="sib-{{ $sibling->id }}"
                        >
                            <div class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $sibling->invoice_number }}</div>
                            <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $sibling->clientAccount?->name }}</div>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="text-xs font-medium tabular-nums text-zinc-700 dark:text-zinc-200">₦{{ number_format($sibling->grand_total, 0) }}</span>
                                <x-badge :status="$sibling->status" />
                            </div>
                        </a>
                    @empty
                        <p class="p-4 text-xs text-zinc-500">No matching invoices.</p>
                    @endforelse
                </div>
            </aside>

            <div class="space-y-4 p-4 sm:p-6">
                <x-billing.invoice-document :invoice="$invoice" />

                @if ($status === 'draft')
                    <x-section-card title="Draft" description="Edit line items before sending">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Draft invoices can be edited on the full form before sending.</p>
                            <x-button size="sm" variant="secondary" :href="route('billing.invoices.edit', $invoice)">Edit invoice</x-button>
                        </div>
                    </x-section-card>
                @endif

                <x-section-card title="Payments" flush>
                    <x-slot:actions>
                        @if (! in_array($status, ['paid', 'void'], true))
                            <button type="button" class="page-link" wire:click="openPayment">Record payment</button>
                        @endif
                    </x-slot:actions>
                    @forelse ($invoice->payments as $payment)
                        <div class="list-row" wire:key="pay-{{ $payment->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">
                                    ₦{{ number_format($payment->amount, 2) }}
                                    <span class="font-normal text-zinc-500 dark:text-zinc-400">· {{ str_replace('_', ' ', $payment->payment_method) }}</span>
                                </div>
                                <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                    {{ $payment->paid_at?->format('M j, Y H:i') }}
                                    @if($payment->notes) · {{ $payment->notes }}@endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-3">
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">No payments recorded yet.</p>
                        </div>
                    @endforelse
                </x-section-card>
            </div>
        </div>
    </x-page-shell>

    @if ($showSend)
        <x-drawer
            title="Send invoice"
            :description="'Email '.$invoice->invoice_number.' with PDF attachment'"
            width="md"
            close-method="closeSend"
        >
            <x-drawer-form wire:submit="sendInvoice" submit-label="Send email" close-method="closeSend" target="sendInvoice">
                <x-form-section title="Recipients" description="Comma-separated emails.">
                    <x-textarea wire:model="sendEmails" label="To *" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model="sendMessage" label="Optional message" rows="3" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($showPayment)
        <x-drawer
            title="Record payment"
            :description="'Remaining ₦'.number_format($balance, 2)"
            width="md"
            close-method="closePayment"
        >
            <x-drawer-form wire:submit="recordPayment" submit-label="Save payment" close-method="closePayment" target="recordPayment">
                <x-form-section title="Payment">
                    <x-input wire:model="paymentForm.amount" label="Amount *" type="number" step="0.01" min="0.01" />
                    <x-select wire:model="paymentForm.payment_method" label="Method *">
                        <option value="cash">Cash</option>
                        <option value="bank_transfer">Bank transfer</option>
                        <option value="mobile_money">Mobile money</option>
                        <option value="card">Card</option>
                        <option value="cheque">Cheque</option>
                    </x-select>
                    <x-textarea wire:model="paymentForm.notes" label="Notes" rows="2" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
