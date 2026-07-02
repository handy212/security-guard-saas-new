<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\ClientAccount;
use App\Models\ClientComplaint;
use App\Models\Site;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ComplaintBoard extends Component
{
    use AuthorizesModuleAccess, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public array $form = [
        'client_account_id' => '', 'site_id' => '', 'subject' => '', 'description' => '', 'priority' => 'normal',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
    ];

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', ClientComplaint::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter'], true)) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->resetPage();
    }

    public function save(): void
    {
        abort_unless(auth()->user()->can('clients.manage'), 403);
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'form.site_id' => 'nullable',
            'form.subject' => 'required',
            'form.description' => 'required',
            'form.priority' => 'required',
        ])['form'];

        ClientComplaint::create($data + ['tenant_id' => TenantContext::id(), 'status' => 'open']);
        $this->form = ['client_account_id' => '', 'site_id' => '', 'subject' => '', 'description' => '', 'priority' => 'normal'];
        session()->flash('status', 'Complaint logged.');
    }

    public function resolve(ClientComplaint $complaint): void
    {
        abort_unless(auth()->user()->can('clients.manage'), 403);
        $complaint->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $base = ClientComplaint::where('tenant_id', $tenantId);

        return view('livewire.clients.complaint-board', [
            'complaints' => (clone $base)->with(['clientAccount', 'site'])
                ->when($this->search !== '', fn ($q) => $q->where('subject', 'like', '%'.$this->search.'%'))
                ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
                ->latest()
                ->paginate(25),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'stats' => [
                'total' => (clone $base)->count(),
                'open' => (clone $base)->where('status', 'open')->count(),
                'high' => (clone $base)->where('priority', 'high')->where('status', 'open')->count(),
                'resolved' => (clone $base)->where('status', 'resolved')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all',
        ])->layout('layouts.app');
    }
}
