<?php

namespace App\Livewire\ClientPortal;

use App\Models\ClientReportApproval;
use App\Services\AuditLogService;
use App\Support\TenantContext;
use Livewire\Component;

class Approvals extends Component
{
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('client_portal.view'), 403);
    }

    public function approve(ClientReportApproval $approval, AuditLogService $audit): void
    {
        abort_unless(auth()->user()->can('client_portal.view'), 403);
        $approval->update(['status' => 'approved', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        $audit->record('client_report.approved', $approval);
    }

    public function reject(ClientReportApproval $approval, AuditLogService $audit): void
    {
        abort_unless(auth()->user()->can('client_portal.view'), 403);
        $approval->update(['status' => 'rejected', 'approved_at' => now(), 'approved_by' => auth()->id()]);
        $audit->record('client_report.rejected', $approval);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $clientId = auth()->user()->client_account_id;

        $items = ClientReportApproval::with(['clientAccount', 'approvable'])
            ->where('tenant_id', $tenantId)
            ->when($clientId, fn ($q) => $q->where('client_account_id', $clientId))
            ->when($this->search, fn ($q) => $q->where('id', 'like', '%'.$this->search.'%'))
            ->latest()
            ->limit(50)
            ->get();

        return view('livewire.clientportal.approvals', compact('items'))->layout('layouts.portal');
    }
}
