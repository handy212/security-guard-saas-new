<div>
    <x-page-shell title="Estimates" description="Create estimates and convert to invoices on acceptance.">
        <x-slot:actions><x-button wire:click="$set('showForm', true)">New estimate</x-button></x-slot:actions>

        <x-flash-status type="success" />

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="billing" />
            <x-stat-card compact label="Open" :value="$stats['open']" icon="pause" tone="info" />
            <x-stat-card compact label="Accepted" :value="$stats['accepted']" icon="check" tone="success" />
            <x-stat-card compact label="Clients" :value="$clients->count()" icon="users" />
        </div>

        <x-section-card title="Estimates">
            @forelse($estimates as $estimate)
                <div class="flex flex-wrap items-center justify-between gap-2 border-t py-3 first:border-0 text-sm" wire:key="estimate-{{ $estimate->id }}">
                    <div>
                        <span class="font-medium text-zinc-900">{{ $estimate->estimate_number }}</span>
                        <span class="text-zinc-500"> — {{ $estimate->clientAccount?->name }}</span>
                        <x-badge :status="$estimate->status" class="ml-1" />
                        <span class="text-zinc-600"> · ₦{{ number_format($estimate->grand_total, 2) }}</span>
                    </div>
                    <div class="flex gap-2">
                        @if($estimate->status === 'draft' || $estimate->status === 'sent')
                            <x-button size="sm" variant="secondary" wire:click="accept({{ $estimate->id }})">Accept</x-button>
                        @endif
                        @if($estimate->status === 'accepted' && ! $estimate->converted_invoice_id)
                            <x-button size="sm" wire:click="convert({{ $estimate->id }})">Convert to invoice</x-button>
                        @endif
                    </div>
                </div>
            @empty
                <x-empty-state title="No estimates" description="Create an estimate to send to clients." />
            @endforelse
            <x-pagination :paginator="$estimates" />
        </x-section-card>
    </x-page-shell>

    @if($showForm)
        <x-drawer title="Create estimate" width="lg" closeMethod="$set('showForm', false)">
            <x-drawer-form wire:submit="save" submit-label="Save estimate" close-method="$set('showForm', false)">
                <x-select wire:model="form.client_account_id" label="Client" class="sm:col-span-2">
                    <option value="">Select client</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </x-select>
                <x-input wire:model="form.valid_until" type="date" label="Valid until" class="sm:col-span-2" />

                @foreach($items as $i => $item)
                    <div class="sm:col-span-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700" wire:key="line-{{ $i }}">
                        <div class="mb-2 flex items-center justify-between">
                            <span class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Line {{ $i + 1 }}</span>
                            @if (count($items) > 1)
                                <button type="button" wire:click="removeLineItem({{ $i }})" class="text-xs text-red-600 hover:underline">Remove</button>
                            @endif
                        </div>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <x-input wire:model="items.{{ $i }}.description" label="Description" class="sm:col-span-2" />
                            <x-input wire:model="items.{{ $i }}.quantity" type="number" step="0.01" label="Qty" />
                            <x-input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" label="Unit price (₦)" />
                        </div>
                    </div>
                @endforeach

                <div class="sm:col-span-2">
                    <x-button type="button" variant="secondary" size="sm" wire:click="addLineItem">Add line item</x-button>
                </div>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
