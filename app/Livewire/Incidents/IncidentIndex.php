<?php

namespace App\Livewire\Incidents;

use App\Enums\IncidentSeverity;
use App\Models\Incident;
use App\Models\Site;
use App\Services\IncidentService;
use App\Support\TenantContext;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

class IncidentIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public array $form = [
        'site_id' => '', 'title' => '', 'type' => '', 'severity' => 'medium', 'description' => '', 'status' => 'submitted',
    ];

    public function save(IncidentService $service): void
    {
        $this->authorize('create', Incident::class);
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.title' => 'required',
            'form.type' => 'required',
            'form.severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'form.description' => 'required',
        ])['form'];
        $service->submit($data + [
            'tenant_id' => TenantContext::id(),
            'reported_by_user_id' => TenantContext::userId(),
        ]);
        $this->form = ['site_id' => '', 'title' => '', 'type' => '', 'severity' => 'medium', 'description' => '', 'status' => 'submitted'];
    }

    public function approve(Incident $incident, IncidentService $service): void
    {
        $this->authorize('approve', $incident);
        $service->approve($incident, TenantContext::userId());
    }

    public function close(Incident $incident, IncidentService $service): void
    {
        $this->authorize('close', $incident);
        $service->close($incident, 'Closed from operations dashboard');
    }

    public function render()
    {
        return view('livewire.incidents.incident-index', [
            'incidents' => Incident::with('site')->where('title', 'like', '%'.$this->search.'%')->latest()->paginate(10),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
