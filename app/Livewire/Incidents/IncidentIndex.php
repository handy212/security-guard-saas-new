<?php

namespace App\Livewire\Incidents;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Livewire\Concerns\HasPerPage;
use App\Enums\IncidentSeverity;
use App\Models\Incident;
use App\Models\IncidentMedia;
use App\Models\Site;
use App\Services\FileUploadService;
use App\Services\IncidentService;
use App\Services\PdfExportService;
use App\Support\MutableStatus;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class IncidentIndex extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, HasPerPage, WithFileUploads, WithPagination;

    public string $search = '';

    public string $statusFilter = 'all';

    public string $severityFilter = 'all';

    public bool $showMediaForm = false;

    public bool $showDetail = false;

    public ?int $viewingIncidentId = null;

    public ?int $editingId = null;

    public ?int $resolvingIncidentId = null;

    public string $resolutionAction = 'close';

    public string $resolutionNotes = '';

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

    public function mount(): void
    {
        $this->authorizePolicy('viewAny', Incident::class);
    }

    public function applyStatFilter(string $filter): void
    {
        match ($filter) {
            'total' => [$this->statusFilter, $this->severityFilter] = ['all', 'all'],
            'open' => [$this->statusFilter, $this->severityFilter] = ['open', 'all'],
            'critical' => [$this->statusFilter, $this->severityFilter] = ['all', 'high_risk'],
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

    public function viewIncident(int $id): void
    {
        $incident = Incident::findOrFail($id);
        abort_unless(auth()->user()->can('incidents.manage'), 403);
        abort_unless((int) $incident->tenant_id === (int) TenantContext::id(), 404);
        $this->viewingIncidentId = $incident->id;
        $this->uploadIncidentId = $incident->id;
        $this->showDetail = true;
    }

    public function closeDetail(): void
    {
        $this->showDetail = false;
        $this->viewingIncidentId = null;
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->form = ['site_id' => '', 'title' => '', 'type' => '', 'severity' => 'medium', 'description' => '', 'status' => 'submitted'];
        $this->openForm();
    }

    public function edit(int $id): void
    {
        $incident = Incident::findOrFail($id);
        $this->authorize('update', $incident);
        abort_unless(MutableStatus::isMutable($incident), 422, MutableStatus::lockedMessage($incident));

        $this->editingId = $incident->id;
        $this->form = [
            'site_id' => (string) $incident->site_id,
            'title' => $incident->title,
            'type' => $incident->type ?? $incident->incident_type ?? '',
            'severity' => (string) $incident->severity,
            'description' => $incident->description ?? '',
            'status' => $incident->status,
        ];
        $this->openForm();
    }

    public function save(IncidentService $service): void
    {
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.title' => 'required',
            'form.type' => 'required|string|max:100',
            'form.severity' => ['required', Rule::enum(IncidentSeverity::class)],
            'form.description' => 'required',
        ])['form'];

        try {
            if ($this->editingId) {
                $incident = Incident::findOrFail($this->editingId);
                $this->authorize('update', $incident);
                $service->update($incident, $data);
                session()->flash('status', 'Incident updated.');
            } else {
                $this->authorize('create', Incident::class);
                $service->submit($data + [
                    'tenant_id' => TenantContext::id(),
                    'reported_by_user_id' => TenantContext::userId(),
                ]);
                session()->flash('status', 'Incident submitted.');
            }
        } catch (RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        $this->editingId = null;
        $this->form = ['site_id' => '', 'title' => '', 'type' => '', 'severity' => 'medium', 'description' => '', 'status' => 'submitted'];
        $this->closeDrawer();
    }

    public function delete(int $id, IncidentService $service): void
    {
        $incident = Incident::findOrFail($id);
        $this->authorize('delete', $incident);

        try {
            $service->delete($incident);
        } catch (RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        if ($this->viewingIncidentId === $id) {
            $this->closeDetail();
        }
        session()->flash('status', 'Incident deleted.');
    }

    public function closeMediaDrawer(): void
    {
        $this->showMediaForm = false;
    }

    public function openMediaForSelected(): void
    {
        if ($this->viewingIncidentId) {
            $this->uploadIncidentId = $this->viewingIncidentId;
        }
        $this->showMediaForm = true;
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
        session()->flash('status', 'Media attached.');
    }

    public function downloadMedia(int $mediaId): StreamedResponse
    {
        $media = IncidentMedia::findOrFail($mediaId);
        abort_unless((int) $media->tenant_id === (int) TenantContext::id(), 404);
        abort_unless(Storage::exists($media->file_path), 404);

        return Storage::download($media->file_path);
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
        abort_unless($incident->status === 'submitted', 422, 'Only submitted incidents can be approved.');
        $service->approve($incident, TenantContext::userId());
        session()->flash('status', 'Incident approved.');
    }

    public function openClose(int $incidentId): void
    {
        $incident = Incident::findOrFail($incidentId);
        $this->authorize('close', $incident);
        $this->resolvingIncidentId = $incident->id;
        $this->resolutionAction = 'close';
        $this->resolutionNotes = '';
    }

    public function openReject(int $incidentId): void
    {
        $incident = Incident::findOrFail($incidentId);
        $this->authorize('reject', $incident);
        $this->resolvingIncidentId = $incident->id;
        $this->resolutionAction = 'reject';
        $this->resolutionNotes = '';
    }

    public function closeResolution(): void
    {
        $this->resolvingIncidentId = null;
        $this->resolutionNotes = '';
    }

    public function submitResolution(IncidentService $service): void
    {
        $incident = Incident::findOrFail($this->resolvingIncidentId);
        $notes = $this->validate([
            'resolutionNotes' => 'required|string|max:2000',
        ])['resolutionNotes'];

        if ($this->resolutionAction === 'reject') {
            $this->authorize('reject', $incident);
            $service->reject($incident, $notes);
            session()->flash('status', 'Incident rejected.');
        } else {
            $this->authorize('close', $incident);
            $service->close($incident, $notes);
            session()->flash('status', 'Incident closed.');
        }

        $this->closeResolution();
        if ($this->viewingIncidentId === $incident->id) {
            $this->closeDetail();
        }
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
        $viewing = $this->viewingIncidentId
            ? Incident::with(['site', 'media', 'reportedBy', 'approvedBy'])->find($this->viewingIncidentId)
            : null;

        return view('livewire.incidents.incident-index', [
            'incidents' => $this->incidentsQuery()->paginate($this->resolvedPerPage()),
            'sites' => Site::orderBy('name')->get(),
            'resolvingIncident' => $this->resolvingIncidentId ? Incident::find($this->resolvingIncidentId) : null,
            'viewingIncident' => $viewing,
            'incidentTypes' => config('dispatch.incident_types'),
            'allIncidentsForMedia' => Incident::where('tenant_id', $tenantId)->whereNotIn('status', ['closed', 'rejected'])->latest()->limit(40)->get(['id', 'title']),
            'incidentStats' => [
                'total' => Incident::where('tenant_id', $tenantId)->count(),
                'open' => Incident::where('tenant_id', $tenantId)->whereNotIn('status', ['closed', 'rejected'])->count(),
                'critical' => Incident::where('tenant_id', $tenantId)->whereIn('severity', ['critical', 'high'])->whereNotIn('status', ['closed', 'rejected'])->count(),
                'closed' => Incident::where('tenant_id', $tenantId)->where('status', 'closed')->count(),
            ],
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'all' || $this->severityFilter !== 'all',
        ])->layout('layouts.app');
    }

    protected function defaultPerPage(): int
    {
        return 10;
    }

    private function incidentsQuery()
    {
        return Incident::query()
            ->with(['site', 'media'])
            ->withCount('media')
            ->when($this->search !== '', fn ($query) => $query->where('title', 'like', '%'.$this->search.'%'))
            ->when($this->statusFilter === 'open', fn ($query) => $query->whereNotIn('status', ['closed', 'rejected']))
            ->when($this->statusFilter === 'closed', fn ($query) => $query->where('status', 'closed'))
            ->when($this->statusFilter === 'rejected', fn ($query) => $query->where('status', 'rejected'))
            ->when($this->statusFilter === 'submitted', fn ($query) => $query->where('status', 'submitted'))
            ->when($this->statusFilter === 'approved', fn ($query) => $query->where('status', 'approved'))
            ->when($this->severityFilter === 'high_risk', fn ($query) => $query->whereIn('severity', ['critical', 'high']))
            ->when(
                $this->severityFilter !== 'all' && $this->severityFilter !== 'high_risk',
                fn ($query) => $query->where('severity', $this->severityFilter)
            )
            ->latest();
    }
}
