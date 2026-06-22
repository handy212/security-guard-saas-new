<?php

namespace App\Livewire\Incidents;

use App\Enums\IncidentSeverity;
use App\Models\Incident;
use App\Models\Site;
use App\Services\FileUploadService;
use App\Services\IncidentService;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentIndex extends Component
{
    use WithFileUploads, WithPagination;

    public string $search = '';

    public array $form = [
        'site_id' => '', 'title' => '', 'type' => '', 'severity' => 'medium', 'description' => '', 'status' => 'submitted',
    ];

    public $mediaFile;

    public ?int $uploadIncidentId = null;

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

    public function uploadMedia(FileUploadService $uploads): void
    {
        $this->authorize('create', Incident::class);
        $data = $this->validate([
            'uploadIncidentId' => 'required|integer',
            'mediaFile' => 'required|file|max:20480',
        ]);

        $uploads->storeIncidentMedia(
            TenantContext::id(),
            $data['uploadIncidentId'],
            $data['mediaFile']
        );

        $this->reset('mediaFile');
    }

    public function exportPdf(Incident $incident, PdfExportService $pdf): StreamedResponse
    {
        $this->authorize('approve', $incident);
        $path = $pdf->exportIncident($incident);

        return Storage::download($path);
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
