<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ClientIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

    public array $form = ['name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'status' => 'active', 'default_hourly_rate' => 0];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    protected function rules(): array
    {
        return [
            'form.name' => 'required',
            'form.industry' => 'nullable',
            'form.email' => 'nullable|email',
            'form.phone' => 'nullable',
            'form.status' => 'required',
            'form.default_hourly_rate' => 'numeric',
        ];
    }

    public function applyStatFilter(string $filter): void
    {
        match ($filter) {
            'total' => $this->statusFilter = 'all',
            'active' => $this->statusFilter = 'active',
            'inactive' => $this->statusFilter = 'inactive',
            default => null,
        };

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function save(): void
    {
        $this->authorize('create', ClientAccount::class);
        $data = $this->validate()['form'];
        if ($this->editingId) {
            $client = ClientAccount::findOrFail($this->editingId);
            $this->authorize('update', $client);
            $client->update($data);
        } else {
            ClientAccount::create($data + ['tenant_id' => TenantContext::id()]);
        }
        $this->closeDrawer();
        $this->reset(['editingId']);
        $this->form = ['name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'status' => 'active', 'default_hourly_rate' => 0];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId']);
        $this->form = ['name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'status' => 'active', 'default_hourly_rate' => 0];
        $this->openForm();
    }

    public function edit(int $id): void
    {
        $client = ClientAccount::findOrFail($id);
        $this->authorize('update', $client);
        $this->editingId = $client->id;
        $this->form = $client->only(array_keys($this->form));
        $this->openForm();
    }

    public function delete(int $id): void
    {
        $client = ClientAccount::findOrFail($id);
        $this->authorize('delete', $client);
        $client->delete();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.clients.client-index', [
            'clients' => $this->clientsQuery()->paginate(10),
            'clientStats' => [
                'total' => ClientAccount::where('tenant_id', $tenantId)->count(),
                'active' => ClientAccount::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'with_email' => ClientAccount::where('tenant_id', $tenantId)->whereNotNull('email')->where('email', '!=', '')->count(),
                'inactive' => ClientAccount::where('tenant_id', $tenantId)->where('status', 'inactive')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all',
        ])->layout('layouts.app');
    }

    private function clientsQuery()
    {
        return ClientAccount::query()
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('email', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('name');
    }
}
