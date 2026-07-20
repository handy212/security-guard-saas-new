<div>
    <x-page-shell
        :title="$estimate->estimate_number"
        :description="($estimate->clientAccount?->name ?? 'Estimate').' · Document view'"
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Estimates', 'href' => route('billing.estimates')],
            ['label' => $estimate->estimate_number],
        ]"
    >
        <x-slot:actions>
            @if (in_array($estimate->status, ['draft', 'sent'], true))
                <x-button variant="secondary" :href="route('billing.estimates.edit', $estimate)">Edit</x-button>
                <x-button wire:click="openSend">Send estimate</x-button>
            @endif
            @if ($estimate->status === 'draft')
                <x-button variant="secondary" wire:click="markSent">Mark as sent</x-button>
            @endif
            @if (in_array($estimate->status, ['draft', 'sent'], true))
                <x-button variant="secondary" wire:click="accept">Accept</x-button>
            @endif
            @if ($estimate->status === 'accepted' && ! $estimate->converted_invoice_id)
                <x-button wire:click="convert">Convert to invoice</x-button>
            @endif
            @if ($estimate->converted_invoice_id)
                <x-button :href="route('billing.invoices.show', $estimate->converted_invoice_id)">Open invoice</x-button>
            @endif
            <x-button variant="secondary" wire:click="exportPdf">Download PDF</x-button>
            @if (in_array($estimate->status, ['draft', 'sent'], true))
                <x-button variant="secondary" wire:click="delete" wire:confirm="Delete this estimate?">Delete</x-button>
            @endif
        </x-slot:actions>

        <x-flash-status type="success" />

        <div class="grid gap-0 overflow-hidden rounded-xl border border-zinc-200 bg-zinc-50 dark:border-zinc-800 dark:bg-zinc-950 lg:grid-cols-[18rem_minmax(0,1fr)]">
            <aside class="billing-master-list hidden max-h-[70vh] overflow-y-auto lg:flex">
                <div class="border-b border-zinc-100 p-3 dark:border-zinc-800">
                    <x-search-input wire:model.live.debounce.300ms="listSearch" placeholder="Search estimates…" />
                </div>
                <div class="flex-1 overflow-y-auto">
                    @forelse ($siblings as $sibling)
                        <a
                            href="{{ route('billing.estimates.show', $sibling) }}"
                            wire:navigate
                            class="billing-master-item {{ $sibling->id === $estimate->id ? 'billing-master-item-active' : '' }}"
                            wire:key="est-sib-{{ $sibling->id }}"
                        >
                            <div class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100">{{ $sibling->estimate_number }}</div>
                            <div class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $sibling->clientAccount?->name }}</div>
                            <div class="mt-1 flex items-center justify-between gap-2">
                                <span class="text-xs font-medium tabular-nums text-zinc-700 dark:text-zinc-200">₦{{ number_format($sibling->grand_total, 0) }}</span>
                                <x-badge :status="$sibling->status" />
                            </div>
                        </a>
                    @empty
                        <p class="p-4 text-xs text-zinc-500">No matching estimates.</p>
                    @endforelse
                </div>
            </aside>

            <div class="p-4 sm:p-6">
                <x-billing.estimate-document :estimate="$estimate" />
            </div>
        </div>
    </x-page-shell>

    @if ($showSend)
        <x-drawer
            title="Send estimate"
            :description="'Email '.$estimate->estimate_number.' with PDF attachment'"
            width="md"
            close-method="closeSend"
        >
            <x-drawer-form wire:submit="sendEstimate" submit-label="Send email" close-method="closeSend" target="sendEstimate">
                <x-form-section title="Recipients" description="Comma-separated emails.">
                    <x-textarea wire:model="sendEmails" label="To *" rows="3" class="sm:col-span-2" />
                    <x-textarea wire:model="sendMessage" label="Optional message" rows="3" class="sm:col-span-2" />
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
