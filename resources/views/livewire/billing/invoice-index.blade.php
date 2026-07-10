<div>
    <x-page-shell title="Invoices" description="Generate client invoices, record payments, and export accounting data.">
        <x-flash-status type="success" />

        <div class="stat-grid">
            <x-stat-card compact label="Total" :value="$stats['total']" icon="billing" />
            <x-stat-card compact label="Draft" :value="$stats['draft']" icon="plan" />
            <x-stat-card compact label="Sent" :value="$stats['sent']" icon="check" tone="info" />
            <x-stat-card compact label="Paid" :value="$stats['paid'] + $stats['partial']" icon="check" tone="success" />
        </div>

        <div class="page-grid-2">
            <x-form-card title="Generate monthly invoice">
                <form wire:submit="generate" class="space-y-3">
                    <x-select wire:model="clientId" label="Client">
                        <option value="">Select client</option>
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="month" label="Month" type="month" />
                    <x-button type="submit" size="sm">Generate invoice</x-button>
                </form>
            </x-form-card>

            @if ($canExport)
                <x-form-card title="Accounting export" description="CSV export for QuickBooks, Xero, or spreadsheets.">
                    <x-button wire:click="exportInvoicesCsv" size="sm">Export invoices CSV</x-button>
                    @if ($exports->isNotEmpty())
                        <div class="mt-4 space-y-2">
                            @foreach ($exports as $export)
                                <div class="flex items-center justify-between gap-2 text-sm" wire:key="export-{{ $export->id }}">
                                    <span class="truncate text-zinc-600">{{ $export->created_at?->format('M j, H:i') }} · {{ strtoupper($export->export_type ?? 'csv') }}</span>
                                    <button type="button" wire:click="downloadExport({{ $export->id }})" class="shrink-0 text-xs font-medium text-accent-600 hover:underline">Download</button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-form-card>
            @endif
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search invoices…">
            <x-slot:tabs>
                <x-segment-control field="statusFilter" :active="$statusFilter" :options="['all' => 'All', 'draft' => 'Draft', 'sent' => 'Sent', 'partial' => 'Partial', 'paid' => 'Paid', 'void' => 'Void', 'overdue' => 'Overdue']" />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Invoice #</x-table.th>
                    <x-table.th>Client</x-table.th>
                    <x-table.th responsive="md">Date</x-table.th>
                    <x-table.th>Total</x-table.th>
                    <x-table.th>Paid</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Actions</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($invoices as $invoice)
                    <tr class="table-row-hover" wire:key="inv-{{ $invoice->id }}">
                        <x-table.td class="font-mono font-medium">{{ $invoice->invoice_number }}</x-table.td>
                        <x-table.td>{{ $invoice->clientAccount?->name }}</x-table.td>
                        <x-table.td responsive="md" muted>{{ $invoice->invoice_date?->format('M j, Y') }}</x-table.td>
                        <x-table.td class="font-semibold">₦{{ number_format($invoice->grand_total, 2) }}</x-table.td>
                        <x-table.td muted>₦{{ number_format($invoice->amount_paid ?? 0, 2) }}</x-table.td>
                        <x-table.td><x-badge :status="$invoice->status" /></x-table.td>
                        <x-table.td align="right">
                            <div class="table-inline-actions">
                                <button type="button" wire:click="viewPayments({{ $invoice->id }})" class="table-action">Details</button>
                                @if($invoice->status !== 'paid' && $invoice->status !== 'void')
                                    <button type="button" wire:click="openPayment({{ $invoice->id }})" class="table-action">Record payment</button>
                                @endif
                                <button type="button" wire:click="exportPdf({{ $invoice->id }})" class="table-action">PDF</button>
                                @if ($invoice->status === 'draft')
                                    <button type="button" wire:click="markSent({{ $invoice->id }})" class="table-action">Mark sent</button>
                                @endif
                            </div>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="7">
                        <x-empty-state compact title="No invoices" description="Generate a monthly invoice for a client to get started." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$invoices" />
    </x-page-shell>

    @if ($payingInvoice)
        <x-modal title="Record payment" :description="'Invoice '.$payingInvoice->invoice_number.' · Remaining ₦'.number_format(max(0, $payingInvoice->grand_total - ($payingInvoice->amount_paid ?? 0)), 2)" closeMethod="closePayment">
            <form wire:submit="recordPayment" class="space-y-3 p-1">
                <x-input wire:model="paymentForm.amount" label="Amount" type="number" step="0.01" min="0.01" />
                <x-select wire:model="paymentForm.payment_method" label="Method">
                    <option value="cash">Cash</option>
                    <option value="bank_transfer">Bank transfer</option>
                    <option value="mobile_money">Mobile money</option>
                    <option value="card">Card</option>
                    <option value="cheque">Cheque</option>
                </x-select>
                <x-textarea wire:model="paymentForm.notes" label="Notes" rows="2" />
                <div class="flex justify-end gap-2">
                    <x-button type="button" variant="secondary" wire:click="closePayment">Cancel</x-button>
                    <x-button type="submit">Save payment</x-button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($viewingInvoice)
        <x-modal title="Invoice details" :description="$viewingInvoice->invoice_number.' · '.$viewingInvoice->clientAccount?->name" closeMethod="closePayments" width="lg">
            <div class="space-y-4 p-1">
                <div class="grid gap-2 text-sm sm:grid-cols-3">
                    <div><span class="text-zinc-500">Total</span><div class="font-semibold">₦{{ number_format($viewingInvoice->grand_total, 2) }}</div></div>
                    <div><span class="text-zinc-500">Paid</span><div class="font-semibold">₦{{ number_format($viewingInvoice->amount_paid ?? 0, 2) }}</div></div>
                    <div><span class="text-zinc-500">Status</span><div><x-badge :status="$viewingInvoice->status" /></div></div>
                </div>

                @if (\App\Support\EnumHelper::value($viewingInvoice->status) === 'draft' && $editingItems)
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Edit line items</h3>
                            <x-button type="button" size="sm" variant="secondary" wire:click="addLineItem">Add line</x-button>
                        </div>
                        <form wire:submit="saveItems" class="space-y-2">
                            @foreach ($items as $index => $item)
                                <div class="grid gap-2 rounded-lg border border-zinc-200 p-2 sm:grid-cols-12" wire:key="inv-item-{{ $index }}">
                                    <div class="sm:col-span-6">
                                        <x-input wire:model="items.{{ $index }}.description" label="Description" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <x-input wire:model="items.{{ $index }}.quantity" label="Qty" type="number" step="0.01" />
                                    </div>
                                    <div class="sm:col-span-3">
                                        <x-input wire:model="items.{{ $index }}.unit_price" label="Unit price" type="number" step="0.01" />
                                    </div>
                                    <div class="flex items-end sm:col-span-1">
                                        <button type="button" class="text-xs text-red-600 hover:underline" wire:click="removeLineItem({{ $index }})">Remove</button>
                                    </div>
                                </div>
                            @endforeach
                            @error('items') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <div class="flex justify-end gap-2">
                                <x-button type="button" variant="secondary" wire:click="$set('editingItems', false)">Cancel</x-button>
                                <x-button type="submit">Save items</x-button>
                            </div>
                        </form>
                    </div>
                @elseif ($viewingInvoice->items->isNotEmpty() || \App\Support\EnumHelper::value($viewingInvoice->status) === 'draft')
                    <div>
                        <div class="mb-2 flex items-center justify-between">
                            <h3 class="text-sm font-semibold">Line items</h3>
                            @if (\App\Support\EnumHelper::value($viewingInvoice->status) === 'draft')
                                <button type="button" class="text-xs font-medium text-accent-600 hover:underline" wire:click="startEditingItems">Edit</button>
                            @endif
                        </div>
                        <div class="space-y-1 text-sm">
                            @forelse ($viewingInvoice->items as $item)
                                <div class="flex justify-between gap-3 border-t border-zinc-100 py-1.5 first:border-0">
                                    <span class="text-zinc-700">{{ $item->description }} <span class="text-zinc-400">× {{ $item->quantity }}</span></span>
                                    <span class="shrink-0 font-medium">₦{{ number_format($item->line_total, 2) }}</span>
                                </div>
                            @empty
                                <p class="text-sm text-zinc-500">No line items yet.</p>
                            @endforelse
                        </div>
                    </div>
                @endif

                <div>
                    <h3 class="mb-2 text-sm font-semibold">Payments</h3>
                    @forelse ($viewingInvoice->payments as $payment)
                        <div class="flex justify-between gap-3 border-t border-zinc-100 py-2 text-sm first:border-0">
                            <div>
                                <div class="font-medium">₦{{ number_format($payment->amount, 2) }} · {{ str_replace('_', ' ', $payment->payment_method) }}</div>
                                <div class="text-xs text-zinc-500">{{ $payment->paid_at?->format('M j, Y H:i') }}@if($payment->notes) · {{ $payment->notes }}@endif</div>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-zinc-500">No payments recorded yet.</p>
                    @endforelse
                </div>
            </div>
        </x-modal>
    @endif
</div>
