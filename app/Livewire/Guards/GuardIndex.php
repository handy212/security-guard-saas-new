<?php

namespace App\Livewire\Guards;

use App\Enums\GuardDutyType;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\HasPerPage;
use App\Models\Branch;
use App\Models\Guard;
use App\Models\Tenant;
use App\Services\PlanLimitService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class GuardIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, HasPerPage, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $verificationFilter = 'all';

    public string $dutyFilter = 'all';

    public string $branchFilter = 'all';

    public ?int $editingId = null;

    public array $form = [
        'employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
        'status' => 'active', 'monthly_rate' => 0, 'license_number' => '', 'license_expires_at' => '',
        'rank' => '', 'duty_type' => 'guardian', 'branch_id' => '',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'verificationFilter' => ['except' => 'all', 'as' => 'kyg'],
        'dutyFilter' => ['except' => 'all', 'as' => 'duty'],
        'branchFilter' => ['except' => 'all', 'as' => 'branch'],
    ];

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', Guard::class);
    }

    protected function rules(): array
    {
        return [
            'form.employee_number' => 'nullable',
            'form.first_name' => 'required',
            'form.last_name' => 'required',
            'form.phone' => 'nullable',
            'form.email' => 'nullable|email',
            'form.status' => 'required',
            'form.monthly_rate' => 'numeric',
            'form.license_number' => 'nullable',
            'form.license_expires_at' => 'nullable|date',
            'form.rank' => 'nullable',
            'form.duty_type' => 'required|in:guardian,dispatch',
            'form.branch_id' => 'nullable',
        ];
    }

    public function applyStatFilter(string $filter): void
    {
        match ($filter) {
            'total' => [$this->statusFilter, $this->verificationFilter] = ['all', 'all'],
            'active' => [$this->statusFilter, $this->verificationFilter] = ['active', 'all'],
            'pending' => [$this->statusFilter, $this->verificationFilter] = ['all', 'pending'],
            'inactive' => [$this->statusFilter, $this->verificationFilter] = ['inactive', 'all'],
            default => null,
        };

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->verificationFilter = 'all';
        $this->dutyFilter = 'all';
        $this->branchFilter = 'all';
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId']);
        $this->form = $this->blankForm();
        $this->showForm = true;
    }

    public function save(PlanLimitService $limits): void
    {
        $this->authorize('create', Guard::class);
        $data = $this->validate()['form'];
        $data['branch_id'] = $data['branch_id'] ?: null;
        $data['license_expires_at'] = $data['license_expires_at'] ?: null;

        if ($this->editingId) {
            $guard = Guard::findOrFail($this->editingId);
            $this->authorize('update', $guard);
            $guard->update($data);
        } else {
            $tenant = Tenant::findOrFail(TenantContext::id());
            abort_unless($limits->canCreateGuard($tenant), 403, 'Guard limit reached for your plan.');
            $guard = Guard::create($data + ['tenant_id' => TenantContext::id()]);
            $this->closeDrawer();
            $this->reset(['editingId']);
            $this->form = $this->blankForm();

            $this->redirect(route('guards.show', $guard), navigate: true);

            return;
        }

        $this->closeDrawer();
        $this->reset(['editingId']);
        $this->form = $this->blankForm();
    }

    public function edit(int $id): void
    {
        $guard = Guard::findOrFail($id);
        $this->authorize('update', $guard);
        $this->editingId = $guard->id;
        $this->form = array_merge($this->form, $guard->only(array_keys($this->form)));
        $this->form['duty_type'] = $guard->duty_type instanceof GuardDutyType
            ? $guard->duty_type->value
            : ($guard->duty_type ?: 'guardian');
        $this->form['branch_id'] = $guard->branch_id ?? '';
        $this->form['license_expires_at'] = $guard->license_expires_at?->format('Y-m-d') ?? '';
        $this->showForm = true;
    }

    public function delete(int $id): void
    {
        $guard = Guard::findOrFail($id);
        $this->authorize('delete', $guard);
        $guard->delete();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'verificationFilter', 'dutyFilter', 'branchFilter'], true)) {
            $this->resetPage();
        }
    }

    protected function defaultPerPage(): int
    {
        return 15;
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $verifiedIdCardCount = Guard::query()
            ->where('tenant_id', $tenantId)
            ->where('verification_status', 'verified')
            ->whereHas('verificationTokens', function ($query) {
                $query->whereNull('revoked_at')
                    ->where(function ($query) {
                        $query->whereNull('expires_at')->orWhere('expires_at', '>', now());
                    });
            })
            ->count();

        return view('livewire.guards.guard-index', [
            'guards' => $this->guardsQuery()->paginate($this->resolvedPerPage()),
            'branches' => Branch::orderBy('name')->get(),
            'dutyTypes' => GuardDutyType::options(),
            'verifiedIdCardCount' => $verifiedIdCardCount,
            'guardStats' => [
                'total' => Guard::where('tenant_id', $tenantId)->count(),
                'active' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'pending' => Guard::where('tenant_id', $tenantId)->where('verification_status', '!=', 'verified')->count(),
                'inactive' => Guard::where('tenant_id', $tenantId)->where('status', 'inactive')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all' || $this->verificationFilter !== 'all' || $this->dutyFilter !== 'all' || $this->branchFilter !== 'all',
        ])->layout('layouts.app');
    }

    private function blankForm(): array
    {
        return [
            'employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
            'status' => 'active', 'monthly_rate' => 0, 'license_number' => '', 'license_expires_at' => '',
            'rank' => '', 'duty_type' => 'guardian', 'branch_id' => '',
        ];
    }

    private function guardsQuery()
    {
        return Guard::query()
            ->with('branch')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('first_name', 'like', '%'.$this->search.'%')
                    ->orWhere('last_name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%')
                    ->orWhere('employee_number', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->verificationFilter === 'pending', fn ($query) => $query->where('verification_status', '!=', 'verified'))
            ->when($this->verificationFilter === 'verified', fn ($query) => $query->where('verification_status', 'verified'))
            ->when($this->dutyFilter !== 'all', fn ($query) => $query->where('duty_type', $this->dutyFilter))
            ->when($this->branchFilter !== 'all', fn ($query) => $query->where('branch_id', $this->branchFilter))
            ->orderBy('first_name');
    }
}
