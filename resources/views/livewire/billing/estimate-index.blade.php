<div>
    <x-page-shell title="Estimates" description="Create estimates and convert to invoices on acceptance.">
        <x-slot:actions><x-button wire:click="$set('showForm', true)">New estimate</x-button></x-slot:actions>

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$estimates->total()" icon="billing" />
            <x-stat-card compact label="Draft / sent" :value="$estimates->filter(fn ($e) => in_array($e->status, ['draft', 'sent']))->count()" icon="pause" tone="info" />
            <x-stat-card compact label="Accepted" :value="$estimates->where('status', 'accepted')->count()" icon="check" tone="success" />
            <x-stat-card compact label="Clients" :value="$clients->count()" icon="users" />
        </div>

        <x-section-card title="Estimates">
            @forelse($estimates as $estimate)
                <div class="flex flex-wrap items-center justify-between gap-2 border-t py-3 first:border-0 text-sm" wire:key="estimate-{{ $estimate->id }}">
                    <div>
                        <span class="font-medium text-zinc-900">{{ $estimate->estimate_number }}</span>
                        <span class="text-zinc-500"> — {{ $estimate->clientAccount?->name }}</span>
                        <x-badge :status="$estimate->status" class="ml-1" />
                        <span class="text-zinc-600"> · ${{ number_format($estimate->grand_total, 2) }}</span>
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
                <x-select wire:model="form.client_account_id" label="Client">
                    <option value="">Select</option>
                    @foreach($clients as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
                </x-select>
                <x-input wire:model="form.valid_until" type="date" label="Valid until" />
                @foreach($items as $i => $item)
                    <x-input wire:model="items.{{ $i }}.description" label="Line {{ $i + 1 }} description" class="sm:col-span-2" />
                    <x-input wire:model="items.{{ $i }}.quantity" type="number" label="Qty" />
                    <x-input wire:model="items.{{ $i }}.unit_price" type="number" step="0.01" label="Price" />
                @endforeach
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
