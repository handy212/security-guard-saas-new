<?php

namespace App\Livewire\Clients;

use App\Models\ClientAccount;
use App\Support\TenantContext;
use Livewire\Component;

class ClientIndex extends Component
{
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

    public function delete(ClientAccount $client): void
    {
        $this->authorize('delete', $client);
        $client->delete();
    }

    public function render()
    {
        return view('livewire.clients.client-index', ['clients' => ClientAccount::orderBy('name')->get()])->layout('layouts.app');
    }
}
