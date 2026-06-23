<div>
    <x-page-header title="Billing & Invoices" description="Generate client invoices and export PDFs." />

    <div class="space-y-5 p-6">
        <x-form-card title="Generate monthly invoice">
            <form wire:submit="generate" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                <x-select wire:model="clientId" label="Client" class="flex-1">
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="month" label="Month" type="month" class="sm:w-48" />
                <x-button type="submit">Generate invoice</x-button>
            </form>
        </x-form-card>

        <x-data-table title="Invoices">
            <thead class="bg-slate-50 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                <tr>
                    <th class="px-4 py-3">Invoice #</th>
                    <th class="px-4 py-3">Client</th>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Total</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="table-row-hover">
                        <td class="px-4 py-3 font-mono font-medium">{{ $invoice->invoice_number }}</td>
                        <td class="px-4 py-3">{{ $invoice->clientAccount?->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $invoice->invoice_date }}</td>
                        <td class="px-4 py-3 font-semibold">{{ number_format($invoice->grand_total, 2) }}</td>
                        <td class="px-4 py-3"><x-badge :status="$invoice->status" /></td>
                        <td class="px-4 py-3 text-right">
                            <button wire:click="markSent({{ $invoice->id }})" class="btn-link">Mark sent</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-10"><x-empty-state title="No invoices" description="Generate a monthly invoice for a client to get started." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>
    </div>
</div>
