<?php

namespace App\Livewire\Dispatch;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\Site;
use App\Models\SosAlert;
use App\Services\DispatchService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class DispatcherBoard extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithFileUploads;

    public string $search = '';

    public string $statusFilter = 'active';

    public string $priorityFilter = 'all';

    public ?int $selectedId = null;

    public ?int $assignGuardId = null;

    public $attachmentFile;

    public array $form = [
        'client_account_id' => '',
        'site_id' => '',
        'guard_id' => '',
        'priority' => 'normal',
        'caller_type' => 'client',
        'caller_name' => '',
        'incident_location' => '',
        'event_type' => '',
        'incident_date' => '',
        'incident_time' => '',
        'description' => '',
        'action_taken' => '',
        'internal_notes' => '',
    ];

    public array $detail = [
        'action_taken' => '',
        'internal_notes' => '',
    ];

    public function mount(): void
    {
        $this->authorizePermission('dispatch.manage');
        $this->form['incident_date'] = now()->toDateString();
        $this->form['incident_time'] = now()->format('H:i');
    }

    public function openForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function selectDispatch(int $id): void
    {
        $this->selectedId = $id;
        $dispatch = DispatchEvent::with('activityLogs.user')->findOrFail($id);
        $this->detail = [
            'action_taken' => $dispatch->action_taken ?? '',
            'internal_notes' => $dispatch->internal_notes ?? '',
        ];
        $this->assignGuardId = $dispatch->guard_id;
    }

    public function save(DispatchService $service): void
    {
        $this->authorize('create', DispatchEvent::class);

        $this->normalizeForm();

        $validated = $this->validate([
            'form.client_account_id' => 'required|exists:client_accounts,id',
            'form.site_id' => 'required|exists:sites,id',
            'form.guard_id' => 'nullable|exists:guards,id',
            'form.priority' => ['required', Rule::enum(DispatchPriority::class)],
            'form.caller_type' => 'required|in:'.implode(',', array_keys(config('dispatch.caller_types'))),
            'form.caller_name' => 'required|string|max:255',
            'form.incident_location' => 'required|string|max:255',
            'form.event_type' => 'required|string|max:100',
            'form.incident_date' => 'required|date',
            'form.incident_time' => 'required|string|max:10',
            'form.description' => 'required|string',
            'form.action_taken' => 'nullable|string',
            'form.internal_notes' => 'nullable|string',
            'attachmentFile' => 'nullable|file|max:10240',
        ]);

        $data = $validated['form'];

        $site = Site::findOrFail($data['site_id']);
        abort_unless((int) $site->client_account_id === (int) $data['client_account_id'], 422);

        $event = $service->createDispatch([
            ...$data,
            'tenant_id' => TenantContext::id(),
            'created_by_user_id' => TenantContext::userId(),
            'latitude' => $site->latitude,
            'longitude' => $site->longitude,
        ], $this->attachmentFile);

        $this->selectedId = $event->id;
        $this->showForm = false;
        $this->reset('attachmentFile');
        $this->resetForm();
        session()->flash('status', 'Dispatch '.$event->dispatch_number.' created.');
    }

    public function saveDetail(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        $data = $this->validate([
            'detail.action_taken' => 'nullable|string',
            'detail.internal_notes' => 'nullable|string',
            'attachmentFile' => 'nullable|file|max:10240',
        ]);

        $service->updateDispatch($dispatch, $data['detail'] + ['user_id' => TenantContext::userId()], $this->attachmentFile);
        $this->reset('attachmentFile');
    }

    public function assignGuard(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        $this->validate([
            'assignGuardId' => ['required', TenantValidation::exists('guards')],
        ], [
            'assignGuardId.required' => 'Select a guard before assigning.',
        ]);

        $service->assignGuard($dispatch, (int) $this->assignGuardId, TenantContext::userId());
        session()->flash('status', 'Guard assigned to '.$dispatch->fresh()->dispatch_number.'.');
    }

    public function advanceStatus(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        $service->advanceStatus($dispatch, TenantContext::userId());
    }

    public function cancelDispatch(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        $service->setStatus($dispatch, DispatchStatus::CANCELLED, TenantContext::userId());
        $this->selectedId = null;
    }

    public function acknowledgeSos(SosAlert $alert): void
    {
        $this->authorize('acknowledge', $alert);
        $alert->update([
            'status' => 'acknowledged',
            'acknowledged_by_user_id' => TenantContext::userId(),
            'acknowledged_at' => now(),
        ]);
    }

    public function dispatchFromSos(int $alertId, DispatchService $service): void
    {
        $this->authorize('create', DispatchEvent::class);
        $alert = SosAlert::with(['assignedGuard', 'site'])->findOrFail($alertId);
        $event = $service->createFromSos($alert, TenantContext::userId());
        $this->selectedId = $event->id;
    }

    public function updatedFormClientAccountId(): void
    {
        $this->form['site_id'] = '';
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $dispatches = DispatchEvent::query()
            ->with(['site', 'clientAccount', 'assignedGuard', 'createdBy'])
            ->when($this->statusFilter === 'active', fn ($q) => $q->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED]))
            ->when($this->statusFilter === 'closed', fn ($q) => $q->whereIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED]))
            ->when($this->statusFilter !== 'active' && $this->statusFilter !== 'closed' && $this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('dispatch_number', 'like', '%'.$this->search.'%')
                        ->orWhere('caller_name', 'like', '%'.$this->search.'%')
                        ->orWhere('incident_location', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%');
                });
            })
            ->latest()
            ->limit(50)
            ->get();

        $selected = $this->selectedId
            ? DispatchEvent::with(['site', 'clientAccount', 'assignedGuard', 'activityLogs.user', 'sosAlert'])->find($this->selectedId)
            : null;

        $sosAlerts = SosAlert::with(['assignedGuard', 'site'])
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest()
            ->get();

        $sitesForClient = $this->form['client_account_id']
            ? Site::where('client_account_id', $this->form['client_account_id'])->orderBy('name')->get()
            : collect();

        $stats = [
            'active' => DispatchEvent::where('tenant_id', $tenantId)->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED])->count(),
            'critical' => DispatchEvent::where('tenant_id', $tenantId)->where('priority', DispatchPriority::CRITICAL)->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED])->count(),
            'en_route' => DispatchEvent::where('tenant_id', $tenantId)->where('status', DispatchStatus::EN_ROUTE)->count(),
            'sos' => $sosAlerts->where('status', 'open')->count(),
        ];

        return view('livewire.dispatch.dispatcher-board', [
            'dispatches' => $dispatches,
            'selected' => $selected,
            'sosAlerts' => $sosAlerts,
            'clients' => ClientAccount::orderBy('name')->get(),
            'sitesForClient' => $sitesForClient,
            'guards' => Guard::where('status', 'active')->orderBy('first_name')->get(),
            'stats' => $stats,
            'incidentTypes' => config('dispatch.incident_types'),
            'callerTypes' => config('dispatch.caller_types'),
        ])->layout('layouts.app');
    }

    private function selectedDispatch(): ?DispatchEvent
    {
        return $this->selectedId ? DispatchEvent::find($this->selectedId) : null;
    }

    private function authorizeSelectedDispatch(): DispatchEvent
    {
        $dispatch = $this->selectedDispatch();
        abort_unless($dispatch, 404);
        $this->authorize('update', $dispatch);

        return $dispatch;
    }

    private function normalizeForm(): void
    {
        foreach (['client_account_id', 'site_id', 'guard_id'] as $key) {
            if ($this->form[$key] === '') {
                $this->form[$key] = null;
            }
        }
    }

    private function resetForm(): void
    {
        $this->form = [
            'client_account_id' => '',
            'site_id' => '',
            'guard_id' => '',
            'priority' => 'normal',
            'caller_type' => 'client',
            'caller_name' => '',
            'incident_location' => '',
            'event_type' => '',
            'incident_date' => now()->toDateString(),
            'incident_time' => now()->format('H:i'),
            'description' => '',
            'action_taken' => '',
            'internal_notes' => '',
        ];
    }
}
