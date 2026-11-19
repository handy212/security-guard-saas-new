<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Tenant;
use App\Support\MutableStatus;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class EstimateService
{
    public function __construct(private BillingService $billing) {}

    public function create(array $data, array $items): Estimate
    {
        return DB::transaction(function () use ($data, $items) {
            $tenantId = TenantContext::id();
            Tenant::query()->lockForUpdate()->findOrFail($tenantId);
            $estimate = Estimate::create($data + [
                'tenant_id' => $tenantId,
                'estimate_number' => $this->nextEstimateNumber($tenantId),
                'estimate_date' => $data['estimate_date'] ?? now()->toDateString(),
                'status' => 'draft',
            ]);

            $this->syncItems($estimate, $items);

            return $estimate->fresh('items');
        });
    }

    public function update(Estimate $estimate, array $data, array $items): Estimate
    {
        return DB::transaction(function () use ($estimate, $data, $items) {
            $estimate = Estimate::query()->lockForUpdate()->findOrFail($estimate->id);
            MutableStatus::assertMutable($estimate);

            $estimate->update(collect($data)->only([
                'client_account_id', 'estimate_date', 'valid_until',
            ])->filter(fn ($v) => $v !== null)->all());

            $this->syncItems($estimate, $items);

            return $estimate->fresh('items');
        });
    }

    public function delete(Estimate $estimate): void
    {
        DB::transaction(function () use ($estimate) {
            $estimate = Estimate::query()->lockForUpdate()->findOrFail($estimate->id);
            MutableStatus::assertMutable($estimate);
            $estimate->delete();
        });
    }

    public function send(Estimate $estimate): Estimate
    {
        return DB::transaction(function () use ($estimate) {
            $estimate = Estimate::query()->lockForUpdate()->findOrFail($estimate->id);
            if (! in_array($estimate->status, ['draft', 'sent'], true)) {
                throw new RuntimeException('Only draft or sent estimates can be sent.');
            }

            $estimate->update([
                'status' => 'sent',
                'sent_at' => $estimate->sent_at ?? now(),
            ]);

            return $estimate->fresh('items');
        });
    }

    public function accept(Estimate $estimate): Estimate
    {
        return DB::transaction(function () use ($estimate) {
            $estimate = Estimate::query()->lockForUpdate()->findOrFail($estimate->id);

            if (! in_array($estimate->status, ['draft', 'sent'], true)) {
                throw new RuntimeException('Only draft or sent estimates can be accepted.');
            }

            $estimate->update(['status' => 'accepted', 'accepted_at' => now()]);

            return $estimate->fresh('items');
        });
    }

    private function syncItems(Estimate $estimate, array $items): void
    {
        $estimate->items()->delete();

        $subtotal = 0;
        foreach ($items as $item) {
            $quantity = (float) ($item['quantity'] ?? 1);
            if ($quantity <= 0) {
                throw new RuntimeException('Estimate item quantity must be greater than zero.');
            }
            $lineTotal = round($quantity * (float) ($item['unit_price'] ?? 0), 2);
            $subtotal += $lineTotal;
            EstimateItem::create([
                'tenant_id' => $estimate->tenant_id,
                'estimate_id' => $estimate->id,
                'description' => $item['description'],
                'quantity' => $quantity,
                'unit_price' => $item['unit_price'] ?? 0,
                'line_total' => $lineTotal,
                'is_taxable' => $item['is_taxable'] ?? true,
            ]);
        }

        $estimate->update(['subtotal' => round($subtotal, 2), 'grand_total' => round($subtotal, 2)]);
    }

    public function convertToInvoice(Estimate $estimate): Invoice
    {
        return DB::transaction(function () use ($estimate) {
            $estimate = Estimate::query()->with('items')->lockForUpdate()->findOrFail($estimate->id);

            if ($estimate->converted_invoice_id) {
                return Invoice::findOrFail($estimate->converted_invoice_id)->load('items');
            }

            if ($estimate->status !== 'accepted') {
                throw new RuntimeException('Only accepted estimates can be converted to invoices.');
            }

            $invoice = $this->billing->create([
                'client_account_id' => $estimate->client_account_id,
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(14)->toDateString(),
            ], $estimate->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
            ])->all());

            $invoice->update([
                'subtotal' => $estimate->subtotal,
                'tax_total' => $estimate->tax_total,
                'grand_total' => $estimate->grand_total,
            ]);
            $estimate->update(['converted_invoice_id' => $invoice->id, 'status' => 'converted']);

            return $invoice->fresh('items');
        });
    }

    public function recordPayment(Invoice $invoice, float $amount, string $method = 'cash', ?string $notes = null): InvoicePayment
    {
        return $this->billing->recordPayment($invoice, $amount, $method, $notes);
    }

    private function nextEstimateNumber(int $tenantId): string
    {
        $year = now()->year;
        $prefix = "EST-{$year}-";
        $last = Estimate::query()
            ->where('tenant_id', $tenantId)
            ->where('estimate_number', 'like', $prefix.'%')
            ->orderByDesc('estimate_number')
            ->value('estimate_number');
        $sequence = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }
}
