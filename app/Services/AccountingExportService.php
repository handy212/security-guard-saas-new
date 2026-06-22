<?php

namespace App\Services;

use App\Models\AccountingExport;
use App\Models\Invoice;
use Illuminate\Support\Facades\Storage;

class AccountingExportService
{
    public function exportInvoicesCsv(int $tenantId): AccountingExport
    {
        $rows = ['Invoice,Client,Date,Subtotal,Tax,Total,Status'];
        Invoice::where('tenant_id', $tenantId)->with('clientAccount')->each(function ($invoice) use (&$rows) {
            $rows[] = implode(',', [
                $invoice->invoice_number,
                $invoice->clientAccount?->name,
                optional($invoice->invoice_date)->format('Y-m-d'),
                $invoice->subtotal,
                $invoice->tax_total,
                $invoice->grand_total,
                $invoice->status,
            ]);
        });
        $path = 'exports/accounting/invoices-'.$tenantId.'-'.now()->format('YmdHis').'.csv';
        Storage::put($path, implode("\n", $rows));

        return AccountingExport::create([
            'tenant_id' => $tenantId,
            'provider' => 'csv',
            'export_type' => 'invoice',
            'status' => 'exported',
            'file_path' => $path,
            'exported_at' => now(),
        ]);
    }
}
