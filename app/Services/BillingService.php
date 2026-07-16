<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\ShiftAssignment;
use Illuminate\Support\Carbon;

class BillingService
{
    public function generateMonthlyInvoice(ClientAccount $client, string $month): Invoice
    {
        $invoice = Invoice::create([
            'tenant_id' => $client->tenant_id,
            'client_account_id' => $client->id,
            'invoice_number' => 'INV-'.now()->format('YmdHis'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
        ]);

        $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        $assignments = ShiftAssignment::whereHas('shift', fn ($q) => $q
            ->where('client_account_id', $client->id)
            ->whereMonth('starts_at', $period->month)
            ->whereYear('starts_at', $period->year))
            ->where('status', 'completed')
            ->with('shift')
            ->get();

        $subtotal = 0;
        $billedShiftIds = [];

        foreach ($assignments as $assignment) {
            $shiftId = $assignment->shift_id;
            if (isset($billedShiftIds[$shiftId])) {
                continue;
            }
            $billedShiftIds[$shiftId] = true;

            $rate = $assignment->shift->billing_rate ?? $client->default_monthly_rate ?? 0;
            $amount = (float) $rate;
            $subtotal += $amount;

            InvoiceItem::create([
                'tenant_id' => $client->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => 'Shift charge - '.$assignment->shift->starts_at->format('M j, Y'),
                'quantity' => 1,
                'unit_price' => $rate,
                'line_total' => $amount,
            ]);
        }

        if ($subtotal <= 0 && (float) ($client->default_monthly_rate ?? 0) > 0) {
            $rate = (float) $client->default_monthly_rate;
            $subtotal = $rate;
            InvoiceItem::create([
                'tenant_id' => $client->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => 'Monthly security retainer - '.$period->format('F Y'),
                'quantity' => 1,
                'unit_price' => $rate,
                'line_total' => $rate,
            ]);
        }

        $invoice->update(['subtotal' => $subtotal, 'tax_total' => 0, 'grand_total' => $subtotal]);

        return $invoice->fresh('items');
    }

    public function syncItems(Invoice $invoice, array $items): Invoice
    {
        if (\App\Support\EnumHelper::value($invoice->status) !== 'draft') {
            throw new \RuntimeException('Only draft invoices can be edited.');
        }

        $invoice->items()->delete();

        $subtotal = 0;
        foreach ($items as $item) {
            $qty = (float) ($item['quantity'] ?? 0);
            $price = (float) ($item['unit_price'] ?? 0);
            $line = round($qty * $price, 2);
            $subtotal += $line;

            InvoiceItem::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $qty,
                'unit_price' => $price,
                'line_total' => $line,
            ]);
        }

        $invoice->update([
            'subtotal' => $subtotal,
            'tax_total' => 0,
            'grand_total' => $subtotal,
        ]);

        return $invoice->fresh('items');
    }

    public function create(array $data, array $items): Invoice
    {
        $invoice = Invoice::create([
            'tenant_id' => \App\Support\TenantContext::id(),
            'client_account_id' => $data['client_account_id'],
            'invoice_number' => 'INV-'.now()->format('YmdHis'),
            'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
            'due_date' => $data['due_date'] ?? now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'subtotal' => 0,
            'tax_total' => 0,
            'grand_total' => 0,
            'amount_paid' => 0,
        ]);

        return $this->syncItems($invoice, $items);
    }

    public function updateDraft(Invoice $invoice, array $data, array $items): Invoice
    {
        if (\App\Support\EnumHelper::value($invoice->status) !== 'draft') {
            throw new \RuntimeException('Only draft invoices can be edited.');
        }

        $invoice->update(collect($data)->only([
            'client_account_id', 'invoice_date', 'due_date',
        ])->filter(fn ($v) => $v !== null && $v !== '')->all());

        return $this->syncItems($invoice, $items);
    }
}
