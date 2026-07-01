<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceIndex extends Component
{
    use WithPagination;

    public string $month;

    public ?int $clientId = null;

    public string $search = '';

    public string $statusFilter = 'all';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->month = now()->format('Y-m');
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function generate(BillingService $service): void
    {
        $this->authorize('create', Invoice::class);
        $client = ClientAccount::findOrFail($this->clientId);
        $service->generateMonthlyInvoice($client, $this->month);
        session()->flash('status', 'Invoice generated.');
    }

    public function markSent(Invoice $invoice): void
    {
        $this->authorize('update', $invoice);
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function exportPdf(int $invoiceId, PdfExportService $pdf): StreamedResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $this->authorize('update', $invoice);
        $path = $pdf->exportInvoice($invoice);

        return Storage::download($path);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        $tenantId = TenantContext::id();

        $query = Invoice::with('clientAccount')
            ->where('tenant_id', $tenantId)
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $needle = '%'.$this->search.'%';
                $q->where('invoice_number', 'like', $needle)
                    ->orWhereHas('clientAccount', fn ($q) => $q->where('name', 'like', $needle));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        $all = Invoice::where('tenant_id', $tenantId);

        return view('livewire.billing.invoice-index', [
            'invoices' => $query->paginate(25),
            'clients' => ClientAccount::orderBy('name')->get(),
            'stats' => [
                'total' => $all->count(),
                'draft' => (clone $all)->where('status', 'draft')->count(),
                'sent' => (clone $all)->where('status', 'sent')->count(),
                'paid' => (clone $all)->where('status', 'paid')->count(),
            ],
        ])->layout('layouts.app');
    }
}
