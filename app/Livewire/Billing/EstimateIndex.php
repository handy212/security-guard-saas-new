<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Estimate;
use App\Services\EstimateDeliveryService;
use App\Services\EstimateService;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstimateIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $filterClientId = '';

    public bool $showFilters = false;

    public ?int $sendingEstimateId = null;

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
        $this->showFilters = $this->hasAdvancedFilters();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo', 'filterClientId'], true)) {
            $this->resetPage();
        }
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

    public function openSend(int $estimateId, EstimateDeliveryService $delivery): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $estimate = Estimate::with(['clientAccount.contacts'])->findOrFail($estimateId);
        abort_unless(in_array($estimate->status, ['draft', 'sent'], true), 422);
        $this->sendingEstimateId = $estimate->id;
        $this->sendEmails = implode(', ', $delivery->defaultRecipients($estimate));
        $this->sendMessage = '';
    }

    public function closeSend(): void
    {
        $this->sendingEstimateId = null;
        $this->sendEmails = '';
        $this->sendMessage = '';
    }

    public function sendEstimate(EstimateDeliveryService $delivery): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $estimate = Estimate::findOrFail($this->sendingEstimateId);

        $data = $this->validate([
            'sendEmails' => 'required|string|max:1000',
            'sendMessage' => 'nullable|string|max:2000',
        ]);

        $recipients = preg_split('/[\s,;]+/', $data['sendEmails'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($recipients as $email) {
            abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 422, "Invalid email: {$email}");
        }

        $count = $delivery->send($estimate, $recipients, $data['sendMessage'] ?: null);
        $this->closeSend();
        session()->flash('status', "Estimate emailed to {$count} recipient".($count === 1 ? '' : 's').'.');
    }

    public function delete(int $id, EstimateService $service): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $estimate = Estimate::findOrFail($id);

        try {
            $service->delete($estimate);
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        session()->flash('status', 'Estimate deleted.');
    }

    public function send(Estimate $estimate, EstimateService $service): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        try {
            $service->send($estimate);
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        session()->flash('status', 'Estimate marked as sent.');
    }

    public function accept(Estimate $estimate, EstimateService $service): void
    {
        $service->accept($estimate);
        session()->flash('status', 'Estimate accepted.');
    }

    public function convert(Estimate $estimate, EstimateService $service)
    {
        $invoice = $service->convertToInvoice($estimate);
        session()->flash('status', "Converted to invoice {$invoice->invoice_number}.");

        return $this->redirect(route('billing.invoices.show', $invoice), navigate: true);
    }

    public function exportPdf(int $estimateId, PdfExportService $pdf): StreamedResponse
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $estimate = Estimate::findOrFail($estimateId);
        $path = $pdf->exportEstimate($estimate);

        return Storage::download($path, $estimate->estimate_number.'.pdf');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $all = Estimate::where('tenant_id', $tenantId);

        $query = Estimate::with('clientAccount')
            ->where('tenant_id', $tenantId)
            ->when(filled($this->search), function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('estimate_number', 'like', $term)
                        ->orWhereHas('clientAccount', fn ($c) => $c->where('name', 'like', $term));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->filterClientId !== '', fn ($q) => $q->where('client_account_id', $this->filterClientId))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('estimate_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('estimate_date', '<=', $this->dateTo))
            ->latest();

        return view('livewire.billing.estimate-index', [
            'estimates' => $query->paginate(20),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sendingEstimate' => $this->sendingEstimateId
                ? Estimate::with('clientAccount')->find($this->sendingEstimateId)
                : null,
            'stats' => [
                'total' => $all->count(),
                'open' => (clone $all)->whereIn('status', ['draft', 'sent'])->count(),
                'pipeline' => (clone $all)->whereIn('status', ['draft', 'sent'])->sum('grand_total'),
                'accepted' => (clone $all)->where('status', 'accepted')->count(),
                'converted' => (clone $all)->whereNotNull('converted_invoice_id')->count(),
            ],
            'hasActiveFilters' => filled($this->search) || $this->statusFilter !== 'all' || $this->hasAdvancedFilters(),
            'hasAdvancedFilters' => $this->hasAdvancedFilters(),
        ])->layout('layouts.app');
    }

    private function hasAdvancedFilters(): bool
    {
        return $this->dateFrom !== '' || $this->dateTo !== '' || $this->filterClientId !== '';
    }
}
