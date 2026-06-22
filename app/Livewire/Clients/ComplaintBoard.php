<?php

namespace App\Livewire\Clients;

use App\Models\ClientAccount;
use App\Models\ClientComplaint;
use App\Models\Site;
use App\Support\TenantContext;
use Livewire\Component;

class ComplaintBoard extends Component
{
    public array $form = [
        'client_account_id' => '', 'site_id' => '', 'subject' => '', 'description' => '', 'priority' => 'normal',
    ];

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
    }

    public function resolve(ClientComplaint $complaint): void
    {
        abort_unless(auth()->user()->can('clients.manage'), 403);
        $complaint->update(['status' => 'resolved', 'resolved_at' => now()]);
    }

    public function render()
    {
        return view('livewire.clients.complaint-board', [
            'complaints' => ClientComplaint::with(['clientAccount', 'site'])->latest()->limit(80)->get(),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
