<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Services\PdfExportService;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceIndex extends Component
{
    public string $month;

    public ?int $clientId = null;

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    public function generate(BillingService $service): void
    {
        $this->authorize('create', Invoice::class);
        $client = ClientAccount::findOrFail($this->clientId);
        $service->generateMonthlyInvoice($client, $this->month);
    }

    public function markSent(Invoice $invoice): void
    {
        $this->authorize('update', $invoice);
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function exportPdf(Invoice $invoice, PdfExportService $pdf): StreamedResponse
    {
        $this->authorize('update', $invoice);
        $path = $pdf->exportInvoice($invoice);

        return Storage::download($path);
    }

    public function render()
    {
        return view('livewire.billing.invoice-index', [
            'invoices' => Invoice::with('clientAccount')->latest()->get(),
            'clients' => ClientAccount::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
