<?php

namespace App\Livewire\Guards;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\Branch;
use App\Models\Guard;
use App\Models\Tenant;
use App\Services\PlanLimitService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class GuardIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $verificationFilter = 'all';

    public ?int $editingId = null;

    public array $form = [
        'employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
        'status' => 'active', 'hourly_rate' => 0, 'license_number' => '', 'license_expires_at' => '',
        'rank' => '', 'branch_id' => '',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'verificationFilter' => ['except' => 'all', 'as' => 'kyg'],
    ];

    protected function rules(): array
    {
        return [
            'form.employee_number' => 'nullable',
            'form.first_name' => 'required',
            'form.last_name' => 'required',
            'form.phone' => 'nullable',
            'form.email' => 'nullable|email',
            'form.status' => 'required',
            'form.hourly_rate' => 'numeric',
            'form.license_number' => 'nullable',
            'form.license_expires_at' => 'nullable|date',
            'form.rank' => 'nullable',
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
        $this->resetPage();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId']);
        $this->form = [
            'employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
            'status' => 'active', 'hourly_rate' => 0, 'license_number' => '', 'license_expires_at' => '',
            'rank' => '', 'branch_id' => '',
        ];
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
            Guard::create($data + ['tenant_id' => TenantContext::id()]);
        }

        $this->closeDrawer();
        $this->reset(['editingId']);
        $this->form = [
            'employee_number' => '', 'first_name' => '', 'last_name' => '', 'phone' => '', 'email' => '',
            'status' => 'active', 'hourly_rate' => 0, 'license_number' => '', 'license_expires_at' => '',
            'rank' => '', 'branch_id' => '',
        ];
    }

    public function edit(int $id): void
    {
        $guard = Guard::findOrFail($id);
        $this->authorize('update', $guard);
        $this->editingId = $guard->id;
        $this->form = array_merge($this->form, $guard->only(array_keys($this->form)));
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
        if (in_array($property, ['search', 'statusFilter', 'verificationFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.guards.guard-index', [
            'guards' => $this->guardsQuery()->paginate(15),
            'branches' => Branch::orderBy('name')->get(),
            'guardStats' => [
                'total' => Guard::where('tenant_id', $tenantId)->count(),
                'active' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'pending' => Guard::where('tenant_id', $tenantId)->where('verification_status', '!=', 'verified')->count(),
                'inactive' => Guard::where('tenant_id', $tenantId)->where('status', 'inactive')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all' || $this->verificationFilter !== 'all',
        ])->layout('layouts.app');
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
            ->orderBy('first_name');
    }
}
