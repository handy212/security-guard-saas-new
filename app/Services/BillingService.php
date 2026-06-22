<?php

namespace App\Services;

use App\Models\{ClientAccount, Invoice, InvoiceItem, ShiftAssignment};

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

        $assignments = ShiftAssignment::whereHas('shift', fn($q) => $q->where('client_account_id', $client->id)->whereMonth('starts_at', substr($month,5,2))->whereYear('starts_at', substr($month,0,4)))
            ->where('status','completed')->with('shift')->get();

        $subtotal = 0;
        foreach ($assignments as $assignment) {
            $hours = max(1, $assignment->shift->billable_hours ?? 8);
            $rate = $assignment->shift->billing_rate ?? $client->default_hourly_rate ?? 0;
            $amount = $hours * $rate;
            $subtotal += $amount;
            InvoiceItem::create([
                'tenant_id' => $client->tenant_id,
                'invoice_id' => $invoice->id,
                'description' => 'Security services - '.$assignment->shift->starts_at,
                'quantity' => $hours,
                'unit_price' => $rate,
                'line_total' => $amount,
            ]);
        }

        $invoice->update(['subtotal'=>$subtotal, 'tax_total'=>0, 'grand_total'=>$subtotal]);
        return $invoice->fresh('items');
    }
}
