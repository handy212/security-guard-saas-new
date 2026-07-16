<?php

namespace App\Services;

use App\Models\Incident;
use App\Models\Invoice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class PdfExportService
{
    public function exportIncident(Incident $incident): string
    {
        $incident->load(['site']);

        $pdf = Pdf::loadView('pdf.incident', ['incident' => $incident]);
        $path = 'exports/incidents/incident-'.$incident->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }

    public function exportInvoice(Invoice $invoice): string
    {
        $invoice->load(['clientAccount', 'items']);

        $pdf = Pdf::loadView('pdf.invoice', ['invoice' => $invoice]);
        $path = 'exports/invoices/invoice-'.$invoice->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }

    public function exportEstimate(\App\Models\Estimate $estimate): string
    {
        $estimate->load(['clientAccount', 'items']);

        $pdf = Pdf::loadView('pdf.estimate', ['estimate' => $estimate]);
        $path = 'exports/estimates/estimate-'.$estimate->id.'-'.now()->format('YmdHis').'.pdf';
        Storage::put($path, $pdf->output());

        return $path;
    }
}
