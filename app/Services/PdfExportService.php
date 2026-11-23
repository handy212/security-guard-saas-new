<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\Incident;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

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

    private function issuerViewData(array $issuer): array
    {
        return [
            'issuer' => $issuer,
            'issuerLogo' => $this->profiles->logoDataUri($issuer['logo_path'] ?? null),
        ];
    }
}
