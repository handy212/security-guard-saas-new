<?php

namespace App\Livewire\Incidents;

use App\Livewire\Concerns\HasFormDrawer;
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
    use HasFormDrawer, WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $severityFilter = 'all';

    public bool $showMediaForm = false;

    public array $form = [
        'site_id' => '', 'title' => '', 'type' => '', 'severity' => 'medium', 'description' => '', 'status' => 'submitted',
    ];

    public $mediaFile;

    public ?int $uploadIncidentId = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'statusFilter' => ['except' => 'all', 'as' => 'status'],
        'severityFilter' => ['except' => 'all', 'as' => 'severity'],
    ];

    public function applyStatFilter(string $filter): void
    {
        match ($filter) {
            'total' => [$this->statusFilter, $this->severityFilter] = ['all', 'all'],
            'open' => [$this->statusFilter, $this->severityFilter] = ['open', 'all'],
            'critical' => [$this->statusFilter, $this->severityFilter] = ['all', 'critical'],
            'closed' => [$this->statusFilter, $this->severityFilter] = ['closed', 'all'],
            default => null,
        };

        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->severityFilter = 'all';
        $this->resetPage();
    }

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
        $this->closeDrawer();
    }

    public function closeMediaDrawer(): void
    {
        $this->showMediaForm = false;
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
        $this->showMediaForm = false;
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

    public function updated($property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'severityFilter'], true)) {
            $this->resetPage();
        }
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.incidents.incident-index', [
            'incidents' => $this->incidentsQuery()->paginate(10),
            'sites' => Site::orderBy('name')->get(),
            'incidentStats' => [
                'total' => Incident::where('tenant_id', $tenantId)->count(),
                'open' => Incident::where('tenant_id', $tenantId)->whereNotIn('status', ['closed', 'rejected'])->count(),
                'critical' => Incident::where('tenant_id', $tenantId)->whereIn('severity', ['critical', 'high'])->whereNotIn('status', ['closed', 'rejected'])->count(),
                'closed' => Incident::where('tenant_id', $tenantId)->where('status', 'closed')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all' || $this->severityFilter !== 'all',
        ])->layout('layouts.app');
    }

    private function incidentsQuery()
    {
        return Incident::query()
            ->with('site')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter === 'open', fn ($query) => $query->whereNotIn('status', ['closed', 'rejected']))
            ->when($this->statusFilter === 'closed', fn ($query) => $query->where('status', 'closed'))
            ->when($this->severityFilter !== 'all', fn ($query) => $query->where('severity', $this->severityFilter))
            ->latest();
    }
}
