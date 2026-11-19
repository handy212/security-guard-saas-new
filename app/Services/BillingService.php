<?php

namespace App\Services;

use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoicePayment;
use App\Models\ShiftAssignment;
use App\Models\Tenant;
use App\Support\EnumHelper;
use App\Support\TenantContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class BillingService
{
    public function generateMonthlyInvoice(ClientAccount $client, string $month): Invoice
    {
        $period = Carbon::createFromFormat('Y-m', $month)->startOfMonth();

        return DB::transaction(function () use ($client, $period) {
            Tenant::query()->lockForUpdate()->findOrFail($client->tenant_id);
            $existing = Invoice::query()
                ->where('client_account_id', $client->id)
                ->whereDate('billing_period', $period->toDateString())
                ->first();

            if ($existing) {
                return $existing->loadMissing('items');
            }

            $invoice = Invoice::create([
                'tenant_id' => $client->tenant_id,
                'client_account_id' => $client->id,
                'billing_period' => $period->toDateString(),
                'invoice_number' => $this->nextInvoiceNumber($client->tenant_id),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
                'status' => 'draft',
                'subtotal' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'amount_paid' => 0,
            ]);

            $assignments = ShiftAssignment::whereHas('shift', fn ($q) => $q
                ->where('client_account_id', $client->id)
                ->whereBetween('starts_at', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()]))
                ->where('status', 'completed')
                ->with('shift')
                ->get();

            $items = [];
            $billedShiftIds = [];
            foreach ($assignments as $assignment) {
                if (isset($billedShiftIds[$assignment->shift_id])) {
                    continue;
                }

                $billedShiftIds[$assignment->shift_id] = true;
                $items[] = [
                    'description' => 'Shift charge - '.$assignment->shift->starts_at->format('M j, Y'),
                    'quantity' => 1,
                    'unit_price' => $assignment->shift->billing_rate ?? $client->default_monthly_rate ?? 0,
                ];
            }

            if ($items === [] && (float) ($client->default_monthly_rate ?? 0) > 0) {
                $items[] = [
                    'description' => 'Monthly security retainer - '.$period->format('F Y'),
                    'quantity' => 1,
                    'unit_price' => (float) $client->default_monthly_rate,
                ];
            }

            if ($items === []) {
                throw new RuntimeException('No completed shifts or monthly rate are available for this invoice period.');
            }

            return $this->syncItems($invoice, $items);
        });
    }

    public function syncItems(Invoice $invoice, array $items): Invoice
    {
        if (EnumHelper::value($invoice->status) !== 'draft') {
            throw new RuntimeException('Only draft invoices can be edited.');
        }

        return DB::transaction(function () use ($invoice, $items) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $invoice->items()->delete();

            $subtotal = 0;
            foreach ($items as $item) {
                $qty = (float) ($item['quantity'] ?? 0);
                $price = (float) ($item['unit_price'] ?? 0);
                if ($qty <= 0) {
                    throw new RuntimeException('Invoice item quantity must be greater than zero.');
                }

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
                'subtotal' => round($subtotal, 2),
                'tax_total' => 0,
                'grand_total' => round($subtotal, 2),
            ]);

            return $invoice->fresh('items');
        });
    }

    public function create(array $data, array $items): Invoice
    {
        return DB::transaction(function () use ($data, $items) {
            $tenantId = TenantContext::id();
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $invoice = Invoice::create([
                'tenant_id' => $tenantId,
                'client_account_id' => $data['client_account_id'],
                'invoice_number' => $this->nextInvoiceNumber($tenantId),
                'invoice_date' => $data['invoice_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? now()->addDays(14)->toDateString(),
                'status' => 'draft',
                'subtotal' => 0,
                'tax_total' => 0,
                'grand_total' => 0,
                'amount_paid' => 0,
            ]);

            return $this->syncItems($invoice, $items);
        });
    }

    public function updateDraft(Invoice $invoice, array $data, array $items): Invoice
    {
        if (EnumHelper::value($invoice->status) !== 'draft') {
            throw new RuntimeException('Only draft invoices can be edited.');
        }

        return DB::transaction(function () use ($invoice, $data, $items) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);
            $invoice->update(collect($data)->only([
                'client_account_id', 'invoice_date', 'due_date',
            ])->filter(fn ($v) => $v !== null && $v !== '')->all());

            return $this->syncItems($invoice, $items);
        });
    }

    public function recordPayment(Invoice $invoice, float $amount, string $method = 'cash', ?string $notes = null): InvoicePayment
    {
        if ($amount <= 0) {
            throw new RuntimeException('Payment amount must be greater than zero.');
        }

        return DB::transaction(function () use ($invoice, $amount, $method, $notes) {
            $invoice = Invoice::query()->lockForUpdate()->findOrFail($invoice->id);

            if (! in_array($invoice->status, ['sent', 'partial', 'overdue'], true)) {
                throw new RuntimeException('Payments can only be recorded on sent or partially paid invoices.');
            }

            $remaining = max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0));
            if ($amount > $remaining + 0.001) {
                throw new RuntimeException('Payment amount exceeds the remaining balance.');
            }

            $payment = InvoicePayment::create([
                'tenant_id' => $invoice->tenant_id,
                'invoice_id' => $invoice->id,
                'amount' => $amount,
                'payment_method' => $method,
                'paid_at' => now(),
                'notes' => $notes,
            ]);
            $totalPaid = InvoicePayment::where('invoice_id', $invoice->id)->sum('amount');
            $paidInFull = (float) $totalPaid >= (float) $invoice->grand_total - 0.001;

            $invoice->update([
                'amount_paid' => round((float) $totalPaid, 2),
                'status' => $paidInFull ? 'paid' : 'partial',
                'paid_at' => $paidInFull ? now() : null,
            ]);

            return $payment;
        });
    }

    private function nextInvoiceNumber(int $tenantId): string
    {
        $year = now()->year;
        $prefix = "INV-{$year}-";
        $last = Invoice::query()
            ->where('tenant_id', $tenantId)
            ->where('invoice_number', 'like', $prefix.'%')
            ->orderByDesc('invoice_number')
            ->value('invoice_number');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
