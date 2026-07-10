<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ClientIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

    public array $form = [
        'name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'address' => '',
        'latitude' => '', 'longitude' => '', 'status' => 'active', 'default_monthly_rate' => 0,
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', ClientAccount::class);
    }

    protected function rules(): array
    {
        return [
            'form.name' => 'required',
            'form.industry' => 'nullable',
            'form.email' => 'nullable|email',
            'form.phone' => 'nullable',
            'form.address' => 'nullable|string|max:500',
            'form.latitude' => 'nullable|numeric',
            'form.longitude' => 'nullable|numeric',
            'form.status' => 'required',
            'form.default_monthly_rate' => 'numeric',
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
        $data['latitude'] = $data['latitude'] !== '' && $data['latitude'] !== null ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' && $data['longitude'] !== null ? $data['longitude'] : null;
        $data['address'] = $data['address'] !== '' ? $data['address'] : null;

        if ($this->editingId) {
            $client = ClientAccount::findOrFail($this->editingId);
            $this->authorize('update', $client);
            $client->update($data);
        } else {
            $client = ClientAccount::create($data + ['tenant_id' => TenantContext::id()]);
            $this->closeDrawer();
            $this->reset(['editingId']);
            $this->resetForm();

            $this->redirect(route('clients.show', $client), navigate: true);

            return;
        }
        $this->closeDrawer();
        $this->reset(['editingId']);
        $this->resetForm();
    }

    public function openCreate(): void
    {
        $this->reset(['editingId']);
        $this->resetForm();
        $this->openForm();
    }

    public function edit(int $id): void
    {
        $client = ClientAccount::findOrFail($id);
        $this->authorize('update', $client);
        $this->editingId = $client->id;
        $this->form = [
            'name' => $client->name ?? '',
            'industry' => $client->industry ?? '',
            'email' => $client->email ?? '',
            'phone' => $client->phone ?? '',
            'address' => $client->address ?? '',
            'latitude' => $client->latitude !== null ? (string) $client->latitude : '',
            'longitude' => $client->longitude !== null ? (string) $client->longitude : '',
            'status' => $client->status ?? 'active',
            'default_monthly_rate' => $client->default_monthly_rate ?? 0,
        ];
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

    private function resetForm(): void
    {
        $this->form = [
            'name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'address' => '',
            'latitude' => '', 'longitude' => '', 'status' => 'active', 'default_monthly_rate' => 0,
        ];
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
