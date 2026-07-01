<div>
    <x-page-shell title="Billing & Invoices" description="Generate client invoices and export PDFs.">
        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="billing" />
            <x-stat-card compact label="Draft" :value="$stats['draft']" icon="plan" />
            <x-stat-card compact label="Sent" :value="$stats['sent']" icon="check" tone="info" />
            <x-stat-card compact label="Paid" :value="$stats['paid']" icon="check" tone="success" />
        </div>

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

        <x-page-toolbar search="search" searchPlaceholder="Search invoices…">
            <x-slot:tabs>
                <x-segment-control model="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'paid' => 'Paid']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">Invoice #</th>
                    <th class="px-3 py-2">Client</th>
                    <th class="hidden px-3 py-2 md:table-cell">Date</th>
                    <th class="px-3 py-2">Total</th>
                    <th class="px-3 py-2">Status</th>
                    <th class="px-3 py-2 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="table-row-hover" wire:key="inv-{{ $invoice->id }}">
                        <td class="px-3 py-2 font-mono font-medium">{{ $invoice->invoice_number }}</td>
                        <td class="px-3 py-2">{{ $invoice->clientAccount?->name }}</td>
                        <td class="hidden px-3 py-2 text-zinc-600 md:table-cell">{{ $invoice->invoice_date }}</td>
                        <td class="px-3 py-2 font-semibold">{{ number_format($invoice->grand_total, 2) }}</td>
                        <td class="px-3 py-2"><x-badge :status="$invoice->status" /></td>
                        <td class="px-3 py-2 text-right">
                            <div class="flex justify-end gap-2">
                                <button type="button" wire:click="exportPdf({{ $invoice->id }})" class="btn-link text-xs">PDF</button>
                                @if ($invoice->status === 'draft')
                                    <button type="button" wire:click="markSent({{ $invoice->id }})" class="btn-link text-xs">Mark sent</button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-3 py-8"><x-empty-state title="No invoices" description="Generate a monthly invoice for a client to get started." /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$invoices" />
    </x-page-shell>
</div>
