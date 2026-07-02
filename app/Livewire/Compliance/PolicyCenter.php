<?php

namespace App\Livewire\Compliance;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\DataRetentionPolicy;
use App\Models\IncidentEscalationRule;
use App\Models\Site;
use App\Models\SiteSlaRequirement;
use App\Support\TenantContext;
use Livewire\Component;

class PolicyCenter extends Component
{
    use AuthorizesModuleAccess;

    public array $escalationForm = ['incident_type' => '', 'severity' => 'high', 'notify_after_minutes' => 15, 'notify_client' => true];

    public array $retentionForm = ['record_type' => 'incidents', 'retention_days' => 365];

    public array $slaForm = ['site_id' => '', 'metric' => '', 'target_value' => '', 'frequency' => 'daily'];

    public function mount(): void
    {
        $this->authorizePermission('compliance.manage');
    }

    public function saveEscalation(): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        IncidentEscalationRule::create($this->validate([
            'escalationForm.incident_type' => 'nullable',
            'escalationForm.severity' => 'required',
            'escalationForm.notify_after_minutes' => 'integer',
            'escalationForm.notify_client' => 'boolean',
        ])['escalationForm'] + ['tenant_id' => TenantContext::id(), 'is_active' => true]);
    }

    public function saveRetention(): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        DataRetentionPolicy::create($this->validate([
            'retentionForm.record_type' => 'required',
            'retentionForm.retention_days' => 'integer',
        ])['retentionForm'] + ['tenant_id' => TenantContext::id()]);
    }

    public function saveSla(): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        SiteSlaRequirement::create($this->validate([
            'slaForm.site_id' => 'required',
            'slaForm.metric' => 'required',
            'slaForm.target_value' => 'required',
            'slaForm.frequency' => 'required',
        ])['slaForm'] + ['tenant_id' => TenantContext::id(), 'is_active' => true]);
    }

    public function render()
    {
        return view('livewire.compliance.policy-center', [
            'escalations' => IncidentEscalationRule::latest()->get(),
            'retention' => DataRetentionPolicy::latest()->get(),
            'sla' => SiteSlaRequirement::with('site')->latest()->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
