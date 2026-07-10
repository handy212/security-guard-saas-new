<?php

namespace App\Livewire\Billing;

use App\Models\AccountingExport;
use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Services\AccountingExportService;
use App\Services\BillingService;
use App\Services\EstimateService;
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

    public ?int $payingInvoiceId = null;

    public array $paymentForm = [
        'amount' => '',
        'payment_method' => 'cash',
        'notes' => '',
    ];

    public ?int $viewingInvoiceId = null;

    public array $items = [];

    public bool $editingItems = false;

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

    public function openPayment(int $invoiceId): void
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $this->authorize('update', $invoice);
        $remaining = max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0));
        $this->payingInvoiceId = $invoice->id;
        $this->paymentForm = [
            'amount' => number_format($remaining, 2, '.', ''),
            'payment_method' => 'cash',
            'notes' => '',
        ];
    }

    public function closePayment(): void
    {
        $this->payingInvoiceId = null;
        $this->paymentForm = ['amount' => '', 'payment_method' => 'cash', 'notes' => ''];
    }

    public function recordPayment(EstimateService $service): void
    {
        $invoice = Invoice::findOrFail($this->payingInvoiceId);
        $this->authorize('update', $invoice);

        $data = $this->validate([
            'paymentForm.amount' => 'required|numeric|min:0.01',
            'paymentForm.payment_method' => 'required|string|max:50',
            'paymentForm.notes' => 'nullable|string|max:500',
        ])['paymentForm'];

        $remaining = max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0));
        abort_if((float) $data['amount'] > $remaining + 0.001, 422, 'Amount exceeds remaining balance.');

        $service->recordPayment(
            $invoice,
            (float) $data['amount'],
            $data['payment_method'],
            $data['notes'] ?: null,
        );

        $this->closePayment();
        session()->flash('status', 'Payment recorded.');
    }

    public function viewPayments(int $invoiceId): void
    {
        $invoice = Invoice::with('items')->findOrFail($invoiceId);
        $this->authorize('view', $invoice);
        $this->viewingInvoiceId = $invoice->id;
        $this->editingItems = false;
        $this->items = $invoice->items->map(fn ($item) => [
            'description' => $item->description,
            'quantity' => (string) $item->quantity,
            'unit_price' => (string) $item->unit_price,
        ])->values()->all();

        if (\App\Support\EnumHelper::value($invoice->status) === 'draft' && $this->items === []) {
            $this->items = [['description' => '', 'quantity' => '1', 'unit_price' => '0']];
        }
    }

    public function closePayments(): void
    {
        $this->viewingInvoiceId = null;
        $this->editingItems = false;
        $this->items = [];
    }

    public function startEditingItems(): void
    {
        $invoice = Invoice::findOrFail($this->viewingInvoiceId);
        $this->authorize('update', $invoice);
        abort_unless(\App\Support\EnumHelper::value($invoice->status) === 'draft', 403);
        $this->editingItems = true;
    }

    public function addLineItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => '1', 'unit_price' => '0'];
    }

    public function removeLineItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function saveItems(BillingService $billing): void
    {
        $invoice = Invoice::findOrFail($this->viewingInvoiceId);
        $this->authorize('update', $invoice);

        $data = $this->validate([
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            $billing->syncItems($invoice, $data['items']);
        } catch (\RuntimeException $e) {
            $this->addError('items', $e->getMessage());

            return;
        }

        $this->editingItems = false;
        session()->flash('status', 'Invoice line items updated.');
    }

    public function exportInvoicesCsv(AccountingExportService $exports): void
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $exports->exportInvoicesCsv(TenantContext::id());
        session()->flash('status', 'Invoice CSV export generated.');
    }

    public function downloadExport(int $exportId): StreamedResponse
    {
        abort_unless(auth()->user()->can('exports.manage'), 403);
        $export = AccountingExport::where('tenant_id', TenantContext::id())->findOrFail($exportId);

        abort_unless($export->file_path && Storage::exists($export->file_path), 404);

        return Storage::download($export->file_path);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        $tenantId = TenantContext::id();

        $query = Invoice::with(['clientAccount', 'payments'])
            ->where('tenant_id', $tenantId)
            ->when($this->search !== '', fn ($q) => $q->where(function ($q) {
                $needle = '%'.$this->search.'%';
                $q->where('invoice_number', 'like', $needle)
                    ->orWhereHas('clientAccount', fn ($q) => $q->where('name', 'like', $needle));
            }))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        $all = Invoice::where('tenant_id', $tenantId);
        $payingInvoice = $this->payingInvoiceId ? Invoice::with('clientAccount')->find($this->payingInvoiceId) : null;
        $viewingInvoice = $this->viewingInvoiceId
            ? Invoice::with(['payments', 'items', 'clientAccount'])->find($this->viewingInvoiceId)
            : null;

        return view('livewire.billing.invoice-index', [
            'invoices' => $query->paginate(25),
            'clients' => ClientAccount::orderBy('name')->get(),
            'exports' => AccountingExport::where('tenant_id', $tenantId)->latest()->limit(10)->get(),
            'canExport' => auth()->user()->can('exports.manage'),
            'payingInvoice' => $payingInvoice,
            'viewingInvoice' => $viewingInvoice,
            'stats' => [
                'total' => $all->count(),
                'draft' => (clone $all)->where('status', 'draft')->count(),
                'sent' => (clone $all)->where('status', 'sent')->count(),
                'paid' => (clone $all)->where('status', 'paid')->count(),
                'partial' => (clone $all)->where('status', 'partial')->count(),
            ],
        ])->layout('layouts.app');
    }
}
