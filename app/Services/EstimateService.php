<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Support\TenantContext;

class EstimateService
{
    public function create(array $data, array $items): Estimate
    {
        $estimate = Estimate::create($data + [
            'tenant_id' => TenantContext::id(),
            'estimate_number' => 'EST-'.now()->format('YmdHis'),
            'estimate_date' => $data['estimate_date'] ?? now()->toDateString(),
            'status' => 'draft',
        ]);

        $subtotal = 0;
        foreach ($items as $item) {
            $lineTotal = ($item['quantity'] ?? 1) * ($item['unit_price'] ?? 0);
            $subtotal += $lineTotal;
            EstimateItem::create([
                'tenant_id' => TenantContext::id(),
                'estimate_id' => $estimate->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'] ?? 1,
                'unit_price' => $item['unit_price'] ?? 0,
                'line_total' => $lineTotal,
                'is_taxable' => $item['is_taxable'] ?? true,
            ]);
        }

        $estimate->update(['subtotal' => $subtotal, 'grand_total' => $subtotal]);

        return $estimate->fresh('items');
    }

    public function accept(Estimate $estimate): Estimate
    {
        $estimate->update(['status' => 'accepted', 'accepted_at' => now()]);

        return $estimate;
    }

    public function convertToInvoice(Estimate $estimate): Invoice
    {
        $invoice = Invoice::create([
            'tenant_id' => $estimate->tenant_id,
            'client_account_id' => $estimate->client_account_id,
            'invoice_number' => 'INV-'.now()->format('YmdHis'),
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'draft',
            'subtotal' => $estimate->subtotal,
            'tax_total' => $estimate->tax_total,
            'grand_total' => $estimate->grand_total,
        ]);

        foreach ($estimate->items as $item) {
            InvoiceItem::create([
                'tenant_id' => $estimate->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->line_total,
            ]);
        }

        $estimate->update(['converted_invoice_id' => $invoice->id, 'status' => 'converted']);

        return $invoice->fresh('items');
    }

    public function recordPayment(Invoice $invoice, float $amount, string $method = 'cash', ?string $notes = null): InvoicePayment
    {
        $payment = InvoicePayment::create([
            'tenant_id' => $invoice->tenant_id,
            'invoice_id' => $invoice->id,
            'amount' => $amount,
            'payment_method' => $method,
            'paid_at' => now(),
            'notes' => $notes,
        ]);

        $totalPaid = InvoicePayment::where('invoice_id', $invoice->id)->sum('amount');
        $invoice->update([
            'amount_paid' => $totalPaid,
            'status' => $totalPaid >= $invoice->grand_total ? 'paid' : 'partial',
            'paid_at' => $totalPaid >= $invoice->grand_total ? now() : null,
        ]);

        return $payment;
    }
}
