<?php

namespace App\Livewire\Clients;

use App\Models\ClientAccount;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ClientIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $editingId = null;

    public array $form = ['name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'status' => 'active', 'default_hourly_rate' => 0];

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
        $this->reset(['editingId']);
        $this->form = ['name' => '', 'industry' => '', 'email' => '', 'phone' => '', 'status' => 'active', 'default_hourly_rate' => 0];
    }

    public function edit(ClientAccount $client): void
    {
        $this->authorize('update', $client);
        $this->editingId = $client->id;
        $this->form = $client->only(array_keys($this->form));
    }

    public function delete(ClientAccount $client): void
    {
        $this->authorize('delete', $client);
        $client->delete();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.clients.client-index', [
            'clients' => ClientAccount::query()
                ->when($this->search, fn ($query) => $query->where(function ($query) {
                    $query->where('name', 'like', '%'.$this->search.'%')
                        ->orWhere('email', 'like', '%'.$this->search.'%');
                }))
                ->orderBy('name')
                ->paginate(10),
        ])->layout('layouts.app');
    }
}
