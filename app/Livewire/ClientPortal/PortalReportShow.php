<?php

namespace App\Livewire\ClientPortal;

use App\Models\DailyActivityReport;
use App\Models\PatrolSession;
use App\Models\ShiftAssignment;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PortalReportShow extends Component
{
    public int $reportId;

    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;
        $this->approvedReport();
    }

    public function download(PdfExportService $pdf): StreamedResponse
    {
        return $pdf->downloadDailyReport($this->approvedReport(), includeHandover: false);
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $clientId = (int) auth()->user()->client_account_id;
        $report = $this->approvedReport();
        $assignment = null;
        $patrols = collect();

        if ($report->shift_assignment_id) {
            $assignment = ShiftAssignment::query()
                ->with('shift')
                ->where('tenant_id', $tenantId)
                ->whereKey($report->shift_assignment_id)
                ->whereHas('shift', fn (Builder $query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('client_account_id', $clientId))
                ->first();

            $patrols = PatrolSession::query()
                ->with([
                    'route',
                    'scans' => fn ($query) => $query
                        ->where('tenant_id', $tenantId)
                        ->with(['checkpoint' => fn ($checkpointQuery) => $checkpointQuery->where('tenant_id', $tenantId)]),
                ])
                ->where('tenant_id', $tenantId)
                ->where('shift_assignment_id', $report->shift_assignment_id)
                ->whereHas('route.site', fn (Builder $query) => $query
                    ->where('tenant_id', $tenantId)
                    ->where('client_account_id', $clientId))
                ->orderBy('started_at')
                ->get();
        }

        return view('livewire.client-portal.portal-report-show', [
            'report' => $report,
            'assignment' => $assignment,
            'patrols' => $patrols,
        ])->layout('layouts.portal', [
            'portalTenantName' => TenantContext::current()?->name,
        ]);
    }

    private function approvedReport(): DailyActivityReport
    {
        $user = auth()->user();
        abort_unless($user?->can('client_portal.view'), 403);
        abort_unless($user?->client_account_id, 403);

        $tenantId = TenantContext::id();
        $clientId = (int) $user->client_account_id;

        return DailyActivityReport::query()
            ->with('site')
            ->where('tenant_id', $tenantId)
            ->where('status', 'approved')
            ->whereHas('site', fn (Builder $query) => $query
                ->where('tenant_id', $tenantId)
                ->where('client_account_id', $clientId))
            ->findOrFail($this->reportId);
    }
}
