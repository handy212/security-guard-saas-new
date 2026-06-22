<?php

namespace App\Livewire\Clients;

use App\Models\ClientAccount;
use Livewire\Component;
use Livewire\WithPagination;

class ClientIndex extends Component
{
    use WithPagination;
    public string $search = '';
    public array $form = ['name'=>'','industry'=>'','email'=>'','phone'=>'','status'=>'active','default_hourly_rate'=>0];
    public ?int $editingId = null;

    protected function rules(): array { return ['form.name'=>'required|min:2','form.email'=>'nullable|email','form.phone'=>'nullable','form.industry'=>'nullable','form.status'=>'required','form.default_hourly_rate'=>'numeric']; }

    public function save(): void
    {
        $data = $this->validate()['form'];
        ClientAccount::updateOrCreate(['id'=>$this->editingId], $data + ['tenant_id'=>auth()->user()->tenant_id ?? 1]);
        $this->reset(['form','editingId']); $this->form = ['name'=>'','industry'=>'','email'=>'','phone'=>'','status'=>'active','default_hourly_rate'=>0];
    }
    public function edit(ClientAccount $client): void { $this->editingId = $client->id; $this->form = $client->only(['name','industry','email','phone','status','default_hourly_rate']); }
    public function delete(ClientAccount $client): void { $client->delete(); }
    public function render() { return view('livewire.clients.client-index', ['clients'=>ClientAccount::query()->where('name','like','%'.$this->search.'%')->latest()->paginate(10)])->layout('layouts.app'); }
}
