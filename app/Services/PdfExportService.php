<?php

namespace App\Services;

use App\Models\DailyActivityReport;
use App\Models\Estimate;
use App\Models\Incident;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PdfExportService
{
    public function __construct(private CompanyProfileService $profiles) {}

    public function exportIncident(Incident $incident): string
    {
        $incident->load(['site.tenant', 'tenant']);
        $tenant = $incident->tenant ?? $incident->site?->tenant;
        $issuer = $tenant ? $this->profiles->forTenant($tenant) : ['name' => config('app.name')];

        $pdf = Pdf::loadView('pdf.incident', ['incident' => $incident] + $this->issuerViewData($issuer));
        $path = 'exports/incidents/incident-'.$incident->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }

    public function exportInvoice(Invoice $invoice): string
    {
        $invoice->load(['clientAccount', 'items', 'tenant']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice] + $this->issuerViewData($invoice->documentIssuer()));
        $path = 'exports/invoices/invoice-'.$invoice->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }

    public function exportEstimate(Estimate $estimate): string
    {
        $estimate->load(['clientAccount', 'items', 'tenant']);

        $pdf = Pdf::loadView('pdf.estimate', ['estimate' => $estimate] + $this->issuerViewData($estimate->documentIssuer()));
        $path = 'exports/estimates/estimate-'.$estimate->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }

    public function downloadDailyReport(DailyActivityReport $report, bool $includeHandover): StreamedResponse
    {
        $report->loadMissing(['site', 'assignedGuard', 'tenant']);
        $issuer = $this->profiles->forTenant($report->tenant);
        $pdf = Pdf::loadView('pdf.daily-report', ['report' => $report, 'includeHandover' => $includeHandover] + $this->issuerViewData($issuer));

        return response()->streamDownload(
            function () use ($pdf): void {
                echo $pdf->output();
            },
            'daily-activity-report-'.$report->id.'.pdf',
            ['Content-Type' => 'application/pdf'],
        );
    }

    private function issuerViewData(array $issuer): array
    {
        return [
            'issuer' => $issuer,
            'issuerLogo' => $this->profiles->logoDataUri($issuer['logo_path'] ?? null),
        ];
    }
}
