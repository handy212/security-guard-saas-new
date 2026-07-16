<?php

namespace App\Livewire\ClientPortal;

use App\Models\Invoice;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalInvoices extends Component
{
    use WithPagination;

    public string $statusFilter = 'all';

    protected $queryString = [
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('client_portal.view'), 403);
        abort_unless(auth()->user()->client_account_id, 403);
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function downloadPdf(int $invoiceId, PdfExportService $pdf): StreamedResponse
    {
        $invoice = $this->clientInvoiceQuery()->findOrFail($invoiceId);
        $this->authorize('view', $invoice);
        $path = $pdf->exportInvoice($invoice);

        return Storage::download($path, $invoice->invoice_number.'.pdf');
    }

    public function render()
    {
        $query = $this->clientInvoiceQuery()
            ->with('clientAccount')
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        $base = $this->clientInvoiceQuery();

        return view('livewire.client-portal.portal-invoices', [
            'invoices' => $query->paginate(20),
            'stats' => [
                'open' => (clone $base)->whereIn('status', ['sent', 'partial', 'overdue'])->count(),
                'paid' => (clone $base)->where('status', 'paid')->count(),
                'balance' => (clone $base)->whereIn('status', ['sent', 'partial', 'overdue'])
                    ->get()
                    ->sum(fn ($inv) => max(0, (float) $inv->grand_total - (float) ($inv->amount_paid ?? 0))),
            ],
        ])->layout('layouts.portal', [
            'portalTenantName' => TenantContext::current()?->name,
        ]);
    }

    private function clientInvoiceQuery()
    {
        return Invoice::query()
            ->where('tenant_id', TenantContext::id())
            ->where('client_account_id', auth()->user()->client_account_id)
            ->whereIn('status', ['sent', 'partial', 'paid', 'overdue']);
    }
}
