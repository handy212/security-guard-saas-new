<?php

namespace App\Livewire\ClientPortal;

use App\Models\ClientReportApproval;
use App\Services\AuditLogService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\DB;
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
        $this->decide($approval, 'approved', $audit);
    }

    public function reject(ClientReportApproval $approval, AuditLogService $audit): void
    {
        $this->decide($approval, 'rejected', $audit);
    }

    private function decide(ClientReportApproval $approval, string $status, AuditLogService $audit): void
    {
        $user = auth()->user();
        abort_unless($user?->can('client_portal.view') && $user->client_account_id, 403);
        abort_unless((int) $user->client_account_id === (int) $approval->client_account_id
            && (int) $approval->tenant_id === TenantContext::id(), 404);

        DB::transaction(function () use ($approval, $status, $audit, $user): void {
            $updated = ClientReportApproval::query()->whereKey($approval->id)->where('status', 'pending')
                ->update([
                    'status' => $status,
                    'approved_at' => now(),
                    'approved_by' => $user->id,
                    'approver_name' => $user->name,
                ]);

            if ($updated) {
                $audit->record('client_report.'.$status, $approval);
            }
        });
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $clientId = auth()->user()->client_account_id;
        abort_unless(auth()->user()->can('client_portal.view') && $clientId, 403);

        $items = ClientReportApproval::with(['clientAccount', 'approvable'])
            ->where('tenant_id', $tenantId)
            ->where('client_account_id', $clientId)
            ->when($this->search, fn ($q) => $q->where('id', 'like', '%'.$this->search.'%'))
            ->latest()
            ->limit(50)
            ->get();

        return view('livewire.clientportal.approvals', compact('items'))->layout('layouts.portal', [
            'portalTenantName' => TenantContext::current()?->name,
        ]);
    }
}
