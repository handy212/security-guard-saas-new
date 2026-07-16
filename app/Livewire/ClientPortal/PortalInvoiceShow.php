<?php

namespace App\Livewire\ClientPortal;

use App\Models\Invoice;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalInvoiceShow extends Component
{
    public Invoice $invoice;

    public function mount(Invoice $invoice): void
    {
        abort_unless(auth()->user()->can('client_portal.view'), 403);
        abort_unless(auth()->user()->client_account_id, 403);
        abort_unless(
            (int) $invoice->client_account_id === (int) auth()->user()->client_account_id
            && in_array($invoice->status, ['sent', 'partial', 'paid', 'overdue'], true),
            404
        );
        $this->authorize('view', $invoice);
        $this->invoice = $invoice->load(['clientAccount', 'items', 'payments']);
    }

    public function downloadPdf(PdfExportService $pdf): StreamedResponse
    {
        $this->authorize('view', $this->invoice);
        $path = $pdf->exportInvoice($this->invoice);

        return Storage::download($path, $this->invoice->invoice_number.'.pdf');
    }

    public function render()
    {
        $balance = max(0, (float) $this->invoice->grand_total - (float) ($this->invoice->amount_paid ?? 0));
        $issued = $this->invoice->invoice_date?->format('M j, Y');
        $due = $this->invoice->due_date?->format('M j, Y');
        $description = $issued ? "Issued {$issued}" : 'Invoice details';
        if ($due) {
            $description .= " · Due {$due}";
        }

        return view('livewire.client-portal.portal-invoice-show', [
            'balance' => $balance,
            'pageDescription' => $description,
        ])->layout('layouts.portal', [
            'portalTenantName' => TenantContext::current()?->name,
        ]);
    }
}
