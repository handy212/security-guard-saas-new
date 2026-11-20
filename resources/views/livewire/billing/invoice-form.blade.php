<div>
    <x-page-shell
        :title="$isEditing ? 'Edit invoice' : 'New invoice'"
        description="Create a draft invoice with line items, then send and collect payment."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Invoices', 'href' => route('billing.invoices')],
            ['label' => $isEditing ? ($invoice->invoice_number ?? 'Edit') : 'New'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('billing.invoices')">Cancel</x-button>
        </x-slot:actions>

        <x-flash-status type="success" />

        <form id="invoice-form" wire:submit="save" class="billing-form mx-auto max-w-3xl space-y-4">
            <h2 class="sr-only">Invoice form</h2>
            <x-form-card title="Invoice details" description="Client and billing dates">
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-select wire:model.live="form.client_account_id" label="Client *" class="sm:col-span-2">
                        <option value="">Select client</option>
                        @foreach ($clients as $c)
                            <option value="{{ $c->id }}">{{ $c->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model.live="form.invoice_date" type="date" label="Invoice date *" />
                    <x-input wire:model.live="form.due_date" type="date" label="Due date" />
                </div>
            </x-form-card>

            <x-form-card title="Line items" description="Descriptions, quantities, and unit prices">
                <div class="mb-3 flex justify-end">
                    <x-button type="button" size="sm" variant="secondary" wire:click="addLineItem">Add line</x-button>
                </div>
                <div class="space-y-3">
                    @foreach ($items as $i => $item)
                        <div class="rounded-md border border-zinc-200/90 p-3 dark:border-zinc-700" wire:key="inv-form-line-{{ $i }}">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-[10px] font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400">Line {{ $i + 1 }}</span>
                                @if (count($items) > 1)
                                    <button type="button" wire:click="removeLineItem({{ $i }})" aria-label="Remove invoice line {{ $i + 1 }}" class="table-action text-red-600">Remove</button>
                                @endif
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <x-input wire:model.live="items.{{ $i }}.description" label="Description *" class="sm:col-span-2" />
                                <x-input wire:model.live="items.{{ $i }}.quantity" type="number" step="0.01" label="Qty *" />
                                <x-input wire:model.live="items.{{ $i }}.unit_price" type="number" step="0.01" :label="'Unit price ('.currency_symbol().') *'" />
                            </div>
                        </div>
                    @endforeach
                </div>

                @php
                    $formTotal = collect($items)->sum(fn ($line) => (float) ($line['quantity'] ?? 0) * (float) ($line['unit_price'] ?? 0));
                @endphp
                <div class="billing-form-total">
                    <span>Invoice total</span>
                    <strong>{{ money($formTotal) }}</strong>
                </div>

                <x-slot:footer>
                    <x-button type="button" variant="secondary" :href="route('billing.invoices')">Cancel</x-button>
                    <x-button type="submit" form="invoice-form">{{ $isEditing ? 'Save changes' : 'Create invoice' }}</x-button>
                </x-slot:footer>
            </x-form-card>
        </form>
    </x-page-shell>
</div>
