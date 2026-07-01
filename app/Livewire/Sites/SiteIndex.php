<?php

namespace App\Livewire\Sites;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Models\Site;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class SiteIndex extends Component
{
    use HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

    public array $form = [
        'client_account_id' => '', 'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '',
        'geofence_radius_meters' => 150, 'status' => 'active',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    protected function rules(): array
    {
        return [
            'form.client_account_id' => 'required',
            'form.name' => 'required',
            'form.address' => 'nullable',
            'form.latitude' => 'nullable|numeric',
            'form.longitude' => 'nullable|numeric',
            'form.geofence_radius_meters' => 'integer',
            'form.status' => 'required',
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
        $this->authorize('create', Site::class);
        $data = $this->validate()['form'];
        $data['latitude'] = $data['latitude'] !== '' ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' ? $data['longitude'] : null;
        if ($this->editingId) {
            $site = Site::findOrFail($this->editingId);
            $this->authorize('update', $site);
            $site->update($data);
        } else {
            Site::create($data + ['tenant_id' => TenantContext::id()]);
        }
        $this->closeDrawer();
        $this->reset(['editingId']);
        $this->form = ['client_account_id' => '', 'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'geofence_radius_meters' => 150, 'status' => 'active'];
    }

    public function openCreate(): void
    {
        $this->reset(['editingId']);
        $this->form = ['client_account_id' => '', 'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'geofence_radius_meters' => 150, 'status' => 'active'];
        $this->openForm();
    }

    public function edit(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->authorize('update', $site);
        $this->editingId = $site->id;
        $this->form = $site->only(array_keys($this->form));
        $this->openForm();
    }

    public function delete(int $id): void
    {
        $site = Site::findOrFail($id);
        $this->authorize('delete', $site);
        $site->delete();
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

        return view('livewire.sites.site-index', [
            'sites' => $this->sitesQuery()->paginate(10),
            'clients' => ClientAccount::orderBy('name')->get(),
            'siteStats' => [
                'total' => Site::where('tenant_id', $tenantId)->count(),
                'active' => Site::where('tenant_id', $tenantId)->where('status', 'active')->count(),
                'geofenced' => Site::where('tenant_id', $tenantId)->whereNotNull('latitude')->whereNotNull('longitude')->count(),
                'inactive' => Site::where('tenant_id', $tenantId)->where('status', 'inactive')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all',
        ])->layout('layouts.app');
    }

    private function sitesQuery()
    {
        return Site::with('clientAccount')
            ->when($this->search, fn ($query) => $query->where(function ($query) {
                $query->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('address', 'like', '%'.$this->search.'%');
            }))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->orderBy('name');
    }
}
