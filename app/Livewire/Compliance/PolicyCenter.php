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

    public ?string $activeDrawer = null;

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

    public function openEscalationForm(): void
    {
        $this->escalationForm = [
            'incident_type' => '',
            'severity' => 'high',
            'notify_after_minutes' => 15,
            'notify_client' => true,
        ];
        $this->resetErrorBag();
        $this->activeDrawer = 'escalation';
    }

    public function openRetentionForm(): void
    {
        $this->retentionForm = [
            'record_type' => 'incidents',
            'retention_days' => 365,
        ];
        $this->resetErrorBag();
        $this->activeDrawer = 'retention';
    }

    public function closeDrawer(): void
    {
        $this->activeDrawer = null;
        $this->resetErrorBag();
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

        $this->escalationForm = [
            'incident_type' => '',
            'severity' => 'high',
            'notify_after_minutes' => 15,
            'notify_client' => true,
        ];
        $this->activeDrawer = null;

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

        $this->retentionForm = [
            'record_type' => 'incidents',
            'retention_days' => 365,
        ];
        $this->activeDrawer = null;

        session()->flash('status', 'Retention policy saved.');
    }

    public function deleteEscalation(int $id): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        IncidentEscalationRule::where('tenant_id', TenantContext::id())->findOrFail($id)->delete();
        session()->flash('status', 'Escalation rule deleted.');
    }

    public function deleteRetention(int $id): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        DataRetentionPolicy::where('tenant_id', TenantContext::id())->findOrFail($id)->delete();
        session()->flash('status', 'Retention policy deleted.');
    }

    public function toggleEscalation(int $id): void
    {
        abort_unless(auth()->user()->can('compliance.manage'), 403);
        $rule = IncidentEscalationRule::where('tenant_id', TenantContext::id())->findOrFail($id);
        $rule->update(['is_active' => ! $rule->is_active]);
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.compliance.policy-center', [
            'escalations' => IncidentEscalationRule::where('tenant_id', $tenantId)->latest()->get(),
            'retention' => DataRetentionPolicy::where('tenant_id', $tenantId)->latest()->get(),
        ])->layout('layouts.app');
    }
}
