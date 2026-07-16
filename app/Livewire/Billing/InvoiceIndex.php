<?php

namespace App\Livewire\Billing;

use App\Models\AccountingExport;
use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Services\AccountingExportService;
use App\Services\BillingService;
use App\Services\EstimateService;
use App\Services\InvoiceDeliveryService;
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

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $filterClientId = '';

    public bool $showFilters = false;

    public ?int $payingInvoiceId = null;

    public array $paymentForm = [
        'amount' => '',
        'payment_method' => 'cash',
        'notes' => '',
    ];

    public bool $showGenerateForm = false;

    public ?int $sendingInvoiceId = null;

    public string $sendEmails = '';

    public string $sendMessage = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'filterClientId' => ['except' => '', 'as' => 'client'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->month = now()->format('Y-m');
        $this->showFilters = $this->hasAdvancedFilters();
    }

    public function openGenerate(): void
    {
        $this->showGenerateForm = true;
    }

    public function closeGenerate(): void
    {
        $this->showGenerateForm = false;
    }

    public function toggleFilters(): void
    {
        $this->showFilters = ! $this->showFilters;
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->filterClientId = '';
        $this->resetPage();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo', 'filterClientId'], true)) {
            $this->resetPage();
        }
    }

    public function generate(BillingService $service): void
    {
        $this->authorize('create', Invoice::class);
        $client = ClientAccount::findOrFail($this->clientId);
        $service->generateMonthlyInvoice($client, $this->month);
        $this->showGenerateForm = false;
        session()->flash('status', 'Invoice generated.');
    }

    public function markSent(Invoice $invoice): void
    {
        $this->authorize('update', $invoice);
        abort_unless($invoice->status === 'draft', 422);
        $invoice->update(['status' => 'sent', 'sent_at' => now()]);
        session()->flash('status', 'Invoice marked as sent.');
    }

    public function openSend(int $invoiceId, InvoiceDeliveryService $delivery): void
    {
        $invoice = Invoice::with(['clientAccount.contacts'])->findOrFail($invoiceId);
        $this->authorize('update', $invoice);
        abort_unless(in_array($invoice->status, ['draft', 'sent', 'partial', 'overdue'], true), 422);
        $this->sendingInvoiceId = $invoice->id;
        $this->sendEmails = implode(', ', $delivery->defaultRecipients($invoice));
        $this->sendMessage = '';
    }

    public function closeSend(): void
    {
        $this->sendingInvoiceId = null;
        $this->sendEmails = '';
        $this->sendMessage = '';
    }

    public function sendInvoice(InvoiceDeliveryService $delivery): void
    {
        $invoice = Invoice::findOrFail($this->sendingInvoiceId);
        $this->authorize('update', $invoice);

        $data = $this->validate([
            'sendEmails' => 'required|string|max:1000',
            'sendMessage' => 'nullable|string|max:2000',
        ]);

        $recipients = preg_split('/[\s,;]+/', $data['sendEmails'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($recipients as $email) {
            abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 422, "Invalid email: {$email}");
        }

        $count = $delivery->send($invoice, $recipients, $data['sendMessage'] ?: null);
        $this->closeSend();
        session()->flash('status', "Invoice emailed to {$count} recipient".($count === 1 ? '' : 's').'.');
    }

    public function exportPdf(int $invoiceId, PdfExportService $pdf): StreamedResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $this->authorize('view', $invoice);
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
            ->when($this->filterClientId !== '', fn ($q) => $q->where('client_account_id', $this->filterClientId))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('invoice_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('invoice_date', '<=', $this->dateTo))
            ->latest();

        $all = Invoice::where('tenant_id', $tenantId);
        $open = (clone $all)->whereIn('status', ['draft', 'sent', 'partial', 'overdue'])->get();
        $payingInvoice = $this->payingInvoiceId ? Invoice::with('clientAccount')->find($this->payingInvoiceId) : null;
        $sendingInvoice = $this->sendingInvoiceId
            ? Invoice::with('clientAccount')->find($this->sendingInvoiceId)
            : null;

        return view('livewire.billing.invoice-index', [
            'invoices' => $query->paginate(25),
            'clients' => ClientAccount::orderBy('name')->get(),
            'exports' => AccountingExport::where('tenant_id', $tenantId)->latest()->limit(10)->get(),
            'canExport' => auth()->user()->can('exports.manage'),
            'payingInvoice' => $payingInvoice,
            'sendingInvoice' => $sendingInvoice,
            'hasAdvancedFilters' => $this->hasAdvancedFilters(),
            'stats' => [
                'total' => $all->count(),
                'draft' => (clone $all)->where('status', 'draft')->count(),
                'sent' => (clone $all)->where('status', 'sent')->count(),
                'paid' => (clone $all)->where('status', 'paid')->count(),
                'partial' => (clone $all)->where('status', 'partial')->count(),
                'amount_due' => $open->sum(fn ($inv) => max(0, (float) $inv->grand_total - (float) ($inv->amount_paid ?? 0))),
            ],
        ])->layout('layouts.app');
    }

    private function hasAdvancedFilters(): bool
    {
        return $this->dateFrom !== '' || $this->dateTo !== '' || $this->filterClientId !== '';
    }
}
