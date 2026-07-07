<?php

namespace App\Livewire\Compliance;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\DataRetentionPolicy;
use App\Models\IncidentEscalationRule;
use App\Support\TenantContext;
use Livewire\Component;

class PolicyCenter extends Component
{
    use AuthorizesModuleAccess;

    public array $escalationForm = [
        'incident_type' => '',
        'severity' => 'high',
        'notify_after_minutes' => 15,
        'notify_client' => true,
    ];

    public array $retentionForm = [
        'record_type' => 'incidents',
        'retention_days' => 365,
    ];

    public function mount(): void
    {
        $this->authorizePermission('compliance.manage');
    }

    public function saveEscalation(): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);

        IncidentEscalationRule::create($this->validate([
            'escalationForm.incident_type' => 'nullable|string|max:120',
            'escalationForm.severity' => 'required|string|max:40',
            'escalationForm.notify_after_minutes' => 'required|integer|min:1',
            'escalationForm.notify_client' => 'boolean',
        ])['escalationForm'] + [
            'tenant_id' => TenantContext::id(),
            'is_active' => true,
        ]);

        $this->reset('escalationForm');
        $this->escalationForm['severity'] = 'high';
        $this->escalationForm['notify_after_minutes'] = 15;
        $this->escalationForm['notify_client'] = true;

        session()->flash('status', 'Escalation rule saved.');
    }

    public function saveRetention(): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);

        DataRetentionPolicy::create($this->validate([
            'retentionForm.record_type' => 'required|string|max:80',
            'retentionForm.retention_days' => 'required|integer|min:30',
        ])['retentionForm'] + [
            'tenant_id' => TenantContext::id(),
        ]);

        $this->reset('retentionForm');
        $this->retentionForm['record_type'] = 'incidents';
        $this->retentionForm['retention_days'] = 365;

        session()->flash('status', 'Retention policy saved.');
    }

    public function deleteEscalation(int $id): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        IncidentEscalationRule::where('tenant_id', TenantContext::id())->findOrFail($id)->delete();
    }

    public function deleteRetention(int $id): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        DataRetentionPolicy::where('tenant_id', TenantContext::id())->findOrFail($id)->delete();
    }

    public function toggleEscalation(int $id): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        $rule = IncidentEscalationRule::where('tenant_id', TenantContext::id())->findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function render()
    {
        return view('livewire.compliance.policy-center', [
            'escalations' => IncidentEscalationRule::latest()->get(),
            'retention' => DataRetentionPolicy::latest()->get(),
        ])->layout('layouts.app');
    }
}
