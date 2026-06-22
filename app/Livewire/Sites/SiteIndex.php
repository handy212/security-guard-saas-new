<?php

namespace App\Livewire\Sites;

use App\Models\Site;
use App\Support\TenantContext;
use Livewire\Component;

class SiteIndex extends Component
{
    public ?int $editingId = null;

    public array $form = [
        'client_account_id' => '', 'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '',
        'geofence_radius_meters' => 150, 'status' => 'active',
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

    public function save(): void
    {
        $this->authorize('create', Site::class);
        $data = $this->validate()['form'];
        if ($this->editingId) {
            $site = Site::findOrFail($this->editingId);
            $this->authorize('update', $site);
            $site->update($data);
        } else {
            Site::create($data + ['tenant_id' => TenantContext::id()]);
        }
        $this->reset(['editingId']);
        $this->form = ['client_account_id' => '', 'name' => '', 'address' => '', 'latitude' => '', 'longitude' => '', 'geofence_radius_meters' => 150, 'status' => 'active'];
    }

    public function delete(Site $site): void
    {
        $this->authorize('delete', $site);
        $site->delete();
    }

    public function render()
    {
        return view('livewire.sites.site-index', ['sites' => Site::with('clientAccount')->orderBy('name')->get()])->layout('layouts.app');
    }
}
