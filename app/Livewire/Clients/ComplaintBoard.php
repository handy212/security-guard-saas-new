<?php

namespace App\Livewire\Clients;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Models\ClientComplaint;
use App\Models\Site;
use App\Services\ComplaintService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;
use RuntimeException;

class ComplaintBoard extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public ?int $editingId = null;

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

    public function openForm(): void
    {
        $this->editingId = null;
        $this->form = ['client_account_id' => '', 'site_id' => '', 'subject' => '', 'description' => '', 'priority' => 'normal'];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $complaint = ClientComplaint::findOrFail($id);
        $this->authorize('update', $complaint);
        abort_unless($complaint->status === 'open', 422);

        $this->editingId = $complaint->id;
        $this->form = [
            'client_account_id' => (string) $complaint->client_account_id,
            'site_id' => (string) ($complaint->site_id ?? ''),
            'subject' => $complaint->subject,
            'description' => $complaint->description ?? '',
            'priority' => $complaint->priority ?? 'normal',
        ];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeDrawer(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->form = ['client_account_id' => '', 'site_id' => '', 'subject' => '', 'description' => '', 'priority' => 'normal'];
        $this->resetErrorBag();
    }

    public function save(ComplaintService $service): void
    {
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'form.site_id' => 'nullable',
            'form.subject' => 'required',
            'form.description' => 'required',
            'form.priority' => 'required',
        ])['form'];
        $data['site_id'] = $data['site_id'] ?: null;

        try {
            if ($this->editingId) {
                $complaint = ClientComplaint::findOrFail($this->editingId);
                $this->authorize('update', $complaint);
                $service->update($complaint, $data);
                session()->flash('status', 'Complaint updated.');
            } else {
                $this->authorize('create', ClientComplaint::class);
                $service->create($data);
                session()->flash('status', 'Complaint logged.');
            }
        } catch (RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        $this->closeDrawer();
    }

    public function resolve(ClientComplaint $complaint, ComplaintService $service): void
    {
        $this->authorize('update', $complaint);
        $service->resolve($complaint);
        session()->flash('status', 'Complaint resolved.');
    }

    public function delete(int $id, ComplaintService $service): void
    {
        $complaint = ClientComplaint::findOrFail($id);
        $this->authorize('delete', $complaint);

        try {
            $service->delete($complaint);
        } catch (RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        session()->flash('status', 'Complaint deleted.');
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
