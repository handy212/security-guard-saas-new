<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $dateFrom = '';

    public string $dateTo = '';

    public string $filterClientId = '';

    public string $methodFilter = 'all';

    public bool $showFilters = false;

    protected $queryString = [
        'search' => ['except' => ''],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'filterClientId' => ['except' => '', 'as' => 'client'],
        'methodFilter' => ['except' => 'all', 'as' => 'method'],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->showFilters = $this->hasAdvancedFilters();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'dateFrom', 'dateTo', 'filterClientId', 'methodFilter'], true)) {
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
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->filterClientId = '';
        $this->methodFilter = 'all';
        $this->resetPage();
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $base = InvoicePayment::where('tenant_id', $tenantId);

        $query = InvoicePayment::with(['invoice.clientAccount'])
            ->where('tenant_id', $tenantId)
            ->when(filled($this->search), function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('notes', 'like', $term)
                        ->orWhereHas('invoice', fn ($inv) => $inv->where('invoice_number', 'like', $term)
                            ->orWhereHas('clientAccount', fn ($c) => $c->where('name', 'like', $term)));
                });
            })
            ->when($this->filterClientId !== '', function ($q) {
                $q->whereHas('invoice', fn ($inv) => $inv->where('client_account_id', $this->filterClientId));
            })
            ->when($this->methodFilter !== 'all', fn ($q) => $q->where('payment_method', $this->methodFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('paid_at', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('paid_at', '<=', $this->dateTo))
            ->latest('paid_at');

        return view('livewire.billing.payment-index', [
            'payments' => $query->paginate(25),
            'clients' => ClientAccount::orderBy('name')->get(),
            'stats' => [
                'total' => (clone $base)->count(),
                'mtd' => (float) (clone $base)->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount'),
                'mtd_count' => (clone $base)->whereBetween('paid_at', [$monthStart, $monthEnd])->count(),
                'open_invoices' => Invoice::where('tenant_id', $tenantId)
                    ->whereIn('status', ['sent', 'partial', 'overdue'])
                    ->count(),
            ],
            'hasActiveFilters' => filled($this->search)
                || $this->methodFilter !== 'all'
                || $this->hasAdvancedFilters(),
        ])->layout('layouts.app');
    }

    private function hasAdvancedFilters(): bool
    {
        return $this->dateFrom !== '' || $this->dateTo !== '' || $this->filterClientId !== '';
    }
}
