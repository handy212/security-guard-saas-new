<?php

namespace App\Livewire\Billing;

use App\Livewire\Concerns\HasPerPage;
use App\Models\ClientAccount;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Site;
use App\Services\ExpenseService;
use App\Services\FileUploadService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

class ExpenseIndex extends Component
{
    use HasPerPage;
    use WithFileUploads;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $dateFrom = '';

    public string $dateTo = '';

    public bool $showFilters = false;

    public bool $showForm = false;

    public ?int $editingId = null;

    public $receiptFile = null;

    public array $form = [
        'expense_category_id' => '',
        'client_account_id' => '',
        'site_id' => '',
        'title' => '',
        'description' => '',
        'vendor_name' => '',
        'expense_date' => '',
        'amount' => '',
        'payment_method' => 'cash',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
    ];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->form['expense_date'] = today()->toDateString();
        $this->showFilters = $this->dateFrom !== '' || $this->dateTo !== '';
        app(ExpenseService::class)->ensureDefaultCategories(TenantContext::id());
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'dateFrom', 'dateTo'], true)) {
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
        $this->resetPage();
    }

    public function openForm(): void
    {
        $this->editingId = null;
        $this->showForm = true;
        $this->receiptFile = null;
        $this->form = [
            'expense_category_id' => '',
            'client_account_id' => '',
            'site_id' => '',
            'title' => '',
            'description' => '',
            'vendor_name' => '',
            'expense_date' => today()->toDateString(),
            'amount' => '',
            'payment_method' => 'cash',
        ];
    }

    public function edit(int $id): void
    {
        $expense = Expense::findOrFail($id);
        abort_unless(auth()->user()->can('billing.manage'), 403);
        abort_unless(in_array($expense->status, ['draft', 'submitted'], true), 422);

        $this->editingId = $expense->id;
        $this->form = [
            'expense_category_id' => (string) ($expense->expense_category_id ?? ''),
            'client_account_id' => (string) ($expense->client_account_id ?? ''),
            'site_id' => (string) ($expense->site_id ?? ''),
            'title' => $expense->title,
            'description' => $expense->description ?? '',
            'vendor_name' => $expense->vendor_name ?? '',
            'expense_date' => $expense->expense_date?->toDateString() ?? today()->toDateString(),
            'amount' => (string) $expense->amount,
            'payment_method' => $expense->payment_method ?? 'cash',
        ];
        $this->receiptFile = null;
        $this->showForm = true;
    }

    public function closeForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->receiptFile = null;
    }

    public function save(ExpenseService $service, FileUploadService $uploads): void
    {
        $data = $this->validate([
            'form.expense_category_id' => 'nullable|exists:expense_categories,id',
            'form.client_account_id' => 'nullable|exists:client_accounts,id',
            'form.site_id' => 'nullable|exists:sites,id',
            'form.title' => 'required|string|max:255',
            'form.description' => 'nullable|string|max:2000',
            'form.vendor_name' => 'nullable|string|max:255',
            'form.expense_date' => 'required|date',
            'form.amount' => 'required|numeric|min:0.01',
            'form.payment_method' => 'nullable|string|max:50',
            'receiptFile' => 'nullable|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
        ])['form'];

        try {
            if ($this->editingId) {
                $expense = Expense::findOrFail($this->editingId);
                $receiptPath = $this->receiptFile
                    ? $uploads->storeExpenseReceipt(TenantContext::id(), $expense->id, $this->receiptFile)
                    : null;
                $service->update($expense, $data, $receiptPath);
                session()->flash('status', 'Expense updated.');
            } else {
                $expense = $service->create($data);
                if ($this->receiptFile) {
                    $path = $uploads->storeExpenseReceipt(TenantContext::id(), $expense->id, $this->receiptFile);
                    $expense->update(['receipt_path' => $path]);
                }
                session()->flash('status', 'Expense recorded.');
            }
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        $this->closeForm();
    }

    public function delete(int $id, ExpenseService $service): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $expense = Expense::findOrFail($id);

        try {
            $service->delete($expense);
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        session()->flash('status', 'Expense deleted.');
    }

    public function uploadReceipt(int $expenseId, FileUploadService $uploads): void
    {
        $expense = Expense::findOrFail($expenseId);
        abort_unless(auth()->user()->can('billing.manage'), 403);

        $this->validate([
            'receiptFile' => 'required|file|max:10240|mimes:jpg,jpeg,png,pdf,webp',
        ]);

        $path = $uploads->storeExpenseReceipt(TenantContext::id(), $expense->id, $this->receiptFile);
        $expense->update(['receipt_path' => $path]);
        $this->receiptFile = null;
        session()->flash('status', 'Receipt uploaded.');
    }

    public function approve(int $expenseId, ExpenseService $service): void
    {
        $expense = Expense::findOrFail($expenseId);
        abort_unless(in_array($expense->status, ['draft', 'submitted'], true), 422);
        $service->approve($expense);
        session()->flash('status', 'Expense approved.');
    }

    public function markPaid(int $expenseId, ExpenseService $service): void
    {
        $expense = Expense::findOrFail($expenseId);
        abort_unless($expense->status === 'approved', 422);
        $service->markPaid($expense);
        session()->flash('status', 'Expense marked paid.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $base = Expense::where('tenant_id', $tenantId);

        $query = Expense::with(['category', 'clientAccount', 'site'])
            ->where('tenant_id', $tenantId)
            ->when(filled($this->search), function ($q) {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term) {
                    $inner->where('title', 'like', $term)
                        ->orWhere('vendor_name', 'like', $term)
                        ->orWhere('expense_number', 'like', $term);
                });
            })
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->dateFrom !== '', fn ($q) => $q->whereDate('expense_date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($q) => $q->whereDate('expense_date', '<=', $this->dateTo))
            ->latest('expense_date');

        return view('livewire.billing.expense-index', [
            'expenses' => $query->paginate($this->resolvedPerPage()),
            'categories' => ExpenseCategory::where('tenant_id', $tenantId)->where('is_active', true)->orderBy('name')->get(),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'stats' => [
                'total' => (clone $base)->count(),
                'draft' => (clone $base)->where('status', 'draft')->count(),
                'approved' => (clone $base)->where('status', 'approved')->count(),
                'paid' => (clone $base)->where('status', 'paid')->count(),
                'amount_mtd' => (clone $base)->whereMonth('expense_date', now()->month)->sum('amount'),
            ],
            'hasActiveFilters' => filled($this->search) || $this->statusFilter !== 'all' || $this->dateFrom !== '' || $this->dateTo !== '',
        ])->layout('layouts.app');
    }

    protected function defaultPerPage(): int
    {
        return 25;
    }
}
