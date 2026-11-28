<?php

namespace App\Livewire\Dispatch;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Enums\GuardDutyType;
use App\Jobs\EnrichPatriotDispatch;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\HasFormDrawer;
use App\Models\AttendanceLog;
use App\Models\ClientAccount;
use App\Models\DispatchEvent;
use App\Models\DispatchTeam;
use App\Models\Guard;
use App\Models\Site;
use App\Models\SosAlert;
use App\Models\Tenant;
use App\Services\DispatchService;
use App\Services\NotificationDispatcher;
use App\Services\PatriotAccountProvisioner;
use App\Services\PatriotDispatchPayload;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DispatcherBoard extends Component
{
    use AuthorizesModuleAccess, HasFormDrawer, WithFileUploads;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    #[Url(as: 'status', except: 'active')]
    public string $statusFilter = 'active';

    #[Url(as: 'priority', except: 'all')]
    public string $priorityFilter = 'all';

    public ?int $selectedId = null;

    public ?int $assignGuardId = null;

    public ?int $assignTeamId = null;

    public ?int $patriotSiteId = null;

    public bool $confirmingCancel = false;

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
        $requestedId = request()->integer('dispatch');
        if ($requestedId > 0) {
            $this->selectedId = DispatchEvent::forTenant(TenantContext::id())->findOrFail($requestedId)->id;
        }
        $this->form['incident_date'] = now()->toDateString();
        $this->form['incident_time'] = now()->format('H:i');
    }

    public function applyStatFilter(string $filter): void
    {
        match ($filter) {
            'active' => [$this->statusFilter, $this->priorityFilter] = ['active', 'all'],
            'critical' => [$this->statusFilter, $this->priorityFilter] = ['active', 'critical'],
            'assigned' => [$this->statusFilter, $this->priorityFilter] = ['assigned', 'all'],
            'en_route' => [$this->statusFilter, $this->priorityFilter] = ['en_route', 'all'],
            'patriot', 'patriot-attention', 'returning', 'closed', 'unassigned', 'all' => [$this->statusFilter, $this->priorityFilter] = [$filter, 'all'],
            default => null,
        };
    }

    public function clearFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'active';
        $this->priorityFilter = 'all';
    }

    public function openForm(): void
    {
        $this->resetForm();
        $this->showForm = true;
    }

    public function selectDispatch(int $id): void
    {
        $this->selectedId = $id;
        $this->confirmingCancel = false;
        $dispatch = DispatchEvent::forTenant(TenantContext::id())->findOrFail($id);
        $this->detail = [
            'action_taken' => $dispatch->action_taken ?? '',
            'internal_notes' => $dispatch->internal_notes ?? '',
        ];
        $this->assignGuardId = $dispatch->guard_id;
        $this->reset('patriotSiteId', 'assignTeamId');
    }

    public function save(DispatchService $service): void
    {
        $this->authorize('create', DispatchEvent::class);

        $this->normalizeForm();

        $validated = $this->validate([
            'form.client_account_id' => ['required', TenantValidation::exists('client_accounts')],
            'form.site_id' => ['required', TenantValidation::exists('sites')->where('client_account_id', $this->form['client_account_id'])],
            'form.guard_id' => ['nullable', TenantValidation::exists('guards')->where('status', 'active')],
            'form.priority' => ['required', Rule::enum(DispatchPriority::class)],
            'form.caller_type' => 'required|in:'.implode(',', array_keys(config('dispatch.caller_types'))),
            'form.caller_name' => 'required|string|max:255',
            'form.incident_location' => 'required|string|max:255',
            'form.event_type' => 'required|string|max:100',
            'form.incident_date' => 'required|date',
            'form.incident_time' => 'required|date_format:H:i',
            'form.description' => 'required|string|max:5000',
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
        session()->flash('status', 'Dispatch notes saved.');
    }

    public function reviewPatriotSync(string $resolution): void
    {
        abort_unless(in_array($resolution, ['check', 'retry', 'confirm-release'], true), 422);
        $dispatch = $this->authorizeSelectedDispatch();

        DB::transaction(function () use ($dispatch, $resolution): void {
            $event = DispatchEvent::forTenant($dispatch->tenant_id)->lockForUpdate()->findOrFail($dispatch->id);
            $action = str_replace('check:', '', (string) $event->patriot_pending_action);
            abort_unless($event->patriot_job_id && $event->patriot_writeback_error && in_array($action, ['accept', 'decline', 'on-site', 'complete'], true), 422);
            abort_if(($resolution === 'check' && $action === 'decline') || ($resolution === 'confirm-release' && $action !== 'decline'), 422);

            $event->update([
                'patriot_pending_action' => match ($resolution) {
                    'check' => 'check:'.$action,
                    'retry' => $action,
                    'confirm-release' => null,
                },
                'patriot_writeback_started_at' => null,
                'patriot_writeback_error' => null,
            ] + ($resolution === 'confirm-release' ? [
                'patriot_response_state' => 'declined',
                'status' => DispatchStatus::CANCELLED,
                'closed_at' => now(),
                'returned_at' => $event->dispatch_team_id ? now() : null,
            ] : []));
            $event->activityLogs()->create([
                'tenant_id' => $event->tenant_id,
                'user_id' => TenantContext::userId(),
                'action' => 'patriot_review',
                'message' => match ($resolution) {
                    'check' => 'Requested a read-only Patriot status check.',
                    'retry' => 'Control verified that the Patriot update was not applied and requested a retry.',
                    'confirm-release' => 'Control confirmed the release in Patriot.',
                },
            ]);
        }, attempts: 3);
    }

    public function assignDispatchTeam(DispatchService $service): void
    {
        $event = $this->authorizeSelectedDispatch();
        $this->validate(['assignTeamId' => ['required', TenantValidation::exists('dispatch_teams')]]);
        $service->assignTeam($event, $this->assignTeamId, TenantContext::userId());
        $this->reset('assignTeamId');
        session()->flash('status', 'Dispatch assigned to the response team.');
    }

    public function confirmTeamReturn(DispatchService $service): void
    {
        $event = $this->authorizeSelectedDispatch();
        $service->confirmStationReturn($event, TenantContext::userId());
        session()->flash('status', 'Team return confirmed.');
    }

    public function linkPatriotSite(PatriotAccountProvisioner $provisioner): void
    {
        $dispatch = $this->authorizeSelectedDispatch();
        abort_unless(auth()->user()->can('sites.manage'), 403);
        abort_unless($dispatch->patriot_job_id !== null, 404);
        $this->validate(['patriotSiteId' => ['required', TenantValidation::exists('sites')]]);

        DB::transaction(function () use ($dispatch, $provisioner): void {
            Tenant::query()->lockForUpdate()->findOrFail($dispatch->tenant_id);
            $event = DispatchEvent::forTenant($dispatch->tenant_id)->lockForUpdate()->findOrFail($dispatch->id);
            abort_unless($event->status === DispatchStatus::OPEN && ! $event->guard_id && ! $event->incident_id, 422);
            $site = Site::forTenant($dispatch->tenant_id)->lockForUpdate()->findOrFail($this->patriotSiteId);
            $this->authorize('update', $site);
            $provisioner->reconcile($event, $site);
            logger()->info('Patriot account mapping reconciled.', [
                'tenant_id' => $dispatch->tenant_id, 'dispatch_id' => $event->id,
                'previous_site_id' => $event->site_id, 'site_id' => $site->id, 'user_id' => TenantContext::userId(),
            ]);
        }, attempts: 3);

        $this->reset('patriotSiteId');
        session()->flash('status', 'Patriot dispatch linked to the site.');
    }

    public function assignGuard(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        $eligibleGuard = TenantValidation::exists('guards');
        if ($dispatch->patriot_job_id !== null) {
            $eligibleGuard->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value);
        }

        $this->validate([
            'assignGuardId' => ['required', $eligibleGuard],
        ], [
            'assignGuardId.required' => 'Select a guard before assigning.',
            'assignGuardId.exists' => $dispatch->patriot_job_id !== null
                ? 'Select an active dispatch officer for this Patriot job.'
                : 'Select a valid guard.',
        ]);

        if ($dispatch->patriot_job_id !== null && ! $dispatch->hasPatriotResponseLocation()) {
            $this->addError('assignGuardId', 'Verify the response location before assigning a guard.');

            return;
        }

        $service->assignGuard($dispatch, (int) $this->assignGuardId, TenantContext::userId());
        session()->flash('status', 'Guard assigned to '.$dispatch->fresh()->dispatch_number.'.');
    }

    public function advanceStatus(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        try {
            $service->advanceStatus($dispatch, TenantContext::userId());
            session()->flash('status', 'Status updated.');
        } catch (RuntimeException $e) {
            $this->addError('assignGuardId', $e->getMessage());
        }
    }

    public function confirmCancel(): void
    {
        $this->authorizeSelectedDispatch();
        $this->confirmingCancel = true;
    }

    public function closeCancelConfirm(): void
    {
        $this->confirmingCancel = false;
    }

    public function cancelDispatch(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();

        if ($dispatch->patriot_job_id !== null) {
            $this->confirmingCancel = false;
            $this->addError('patriotDecision', 'The assigned dispatch officer must respond to the Patriot request from the guard app.');

            return;
        }
        $service->setStatus($dispatch, DispatchStatus::CANCELLED, TenantContext::userId());
        $this->selectedId = null;
        $this->confirmingCancel = false;
        session()->flash('status', 'Dispatch cancelled.');
    }

    public function promoteToIncident(DispatchService $service): void
    {
        $dispatch = $this->authorizeSelectedDispatch();
        abort_unless(auth()->user()->can('incidents.manage'), 403);

        $incident = $service->promoteToIncident($dispatch, TenantContext::userId());
        $this->selectDispatch($dispatch->id);
        session()->flash('status', 'Incident #'.$incident->id.' created from dispatch.');
    }

    public function refreshPatriotAccount(): void
    {
        $dispatch = $this->authorizeSelectedDispatch();
        abort_unless(auth()->user()->can('sites.manage'), 403);
        abort_unless($dispatch->patriot_instance && $dispatch->patriot_client_ref, 422);

        EnrichPatriotDispatch::dispatch($dispatch->tenant_id, $dispatch->id, refresh: true);
        session()->flash('status', 'Patriot account refresh queued.');
    }

    public function downloadAttachment(): StreamedResponse
    {
        $dispatch = $this->authorizeSelectedDispatch();
        abort_unless($dispatch->attachment_path && Storage::exists($dispatch->attachment_path), 404);

        return Storage::download($dispatch->attachment_path);
    }

    public function acknowledgeSos(SosAlert $alert): void
    {
        $this->authorize('acknowledge', $alert);
        $dispatcher = app(NotificationDispatcher::class);
        $operationalAlert = NotificationDispatcher::canOperateAlarms(auth()->user())
            ? $dispatcher->alarmsFor(auth()->user())->where('event_type', 'sos.raised')
                ->where('source_reference', (string) $alert->id)->first()
            : null;
        if ($operationalAlert) {
            $dispatcher->acknowledgeAlarm(auth()->user(), $operationalAlert->id);

            return;
        }

        SosAlert::forTenant(TenantContext::id())->whereKey($alert->id)->where('status', 'open')->update([
            'status' => 'acknowledged',
            'acknowledged_by_user_id' => TenantContext::userId(),
            'acknowledged_at' => now(),
        ]);
    }

    public function dispatchFromSos(int $alertId, DispatchService $service): void
    {
        $this->authorize('create', DispatchEvent::class);
        $alert = SosAlert::forTenant(TenantContext::id())->findOrFail($alertId);
        $event = $service->createFromSos($alert, TenantContext::userId());
        $this->selectedId = $event->id;
        $this->selectDispatch($event->id);
        session()->flash('status', 'Dispatch '.$event->dispatch_number.' created from SOS.');
    }

    public function updatedFormClientAccountId(): void
    {
        $this->form['site_id'] = '';
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $patriotQueue = DispatchEvent::forTenant($tenantId)->whereNotNull('patriot_job_id');
        $patriotCounts = [
            'awaiting' => (clone $patriotQueue)->whereNull('patriot_response_state')->whereNull('patriot_pending_action')
                ->whereIn('status', [DispatchStatus::OPEN, DispatchStatus::ASSIGNED])->count(),
            'sending' => (clone $patriotQueue)->whereNotNull('patriot_pending_action')->whereNull('patriot_writeback_error')->count(),
            'attention' => (clone $patriotQueue)->where(fn ($query) => $query->whereNotNull('patriot_writeback_error')->orWhere('patriot_sync_status', 'failed'))->count(),
        ];

        $dispatches = DispatchEvent::forTenant($tenantId)
            ->with(['site', 'clientAccount', 'assignedGuard', 'createdBy'])
            ->when(in_array($this->statusFilter, ['active', 'unassigned'], true), fn ($q) => $q->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED]))
            ->when(in_array($this->statusFilter, ['patriot', 'patriot-attention'], true), fn ($q) => $q->whereNotNull('patriot_job_id'))
            ->when($this->statusFilter === 'patriot-attention', fn ($q) => $q->where(fn ($query) => $query->whereNotNull('patriot_writeback_error')->orWhere('patriot_sync_status', 'failed')))
            ->when($this->statusFilter === 'unassigned', fn ($q) => $q->whereNull('guard_id'))
            ->when($this->statusFilter === 'returning', fn ($q) => $q->whereNotNull('dispatch_team_id')->whereNull('returned_at')
                ->whereIn('status', [DispatchStatus::RESOLVED, DispatchStatus::CLOSED, DispatchStatus::CANCELLED]))
            ->when($this->statusFilter === 'closed', fn ($q) => $q->whereIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED]))
            ->when(! in_array($this->statusFilter, ['active', 'unassigned', 'closed', 'returning', 'patriot', 'patriot-attention', 'all'], true), fn ($q) => $q->where('status', $this->statusFilter))
            ->when($this->priorityFilter !== 'all', fn ($q) => $q->where('priority', $this->priorityFilter))
            ->when($this->search !== '', function ($q) {
                $q->where(function ($inner) {
                    $inner->where('dispatch_number', 'like', '%'.$this->search.'%')
                        ->orWhere('caller_name', 'like', '%'.$this->search.'%')
                        ->orWhere('incident_location', 'like', '%'.$this->search.'%')
                        ->orWhere('description', 'like', '%'.$this->search.'%')
                        ->orWhere('patriot_payload->account_number', $this->search)
                        ->orWhere('patriot_payload->activation_id', $this->search);
                });
            })
            // Queue ordering rule: most urgent priority first, then newest. Covered by DispatchSystemTest.
            ->orderByRaw("case priority when 'critical' then 0 when 'high' then 1 when 'normal' then 2 else 3 end")
            ->latest()
            ->limit(50)
            ->get();

        $selected = $this->selectedId
            ? DispatchEvent::forTenant($tenantId)->with(['site', 'clientAccount', 'assignedGuard', 'activityLogs.user', 'sosAlert', 'incident', 'dispatchTeam.standbyPoint'])->find($this->selectedId)
            : null;

        $sosAlerts = SosAlert::with(['assignedGuard', 'site'])
            ->whereIn('status', ['open', 'acknowledged'])
            ->latest()
            ->get();

        $sitesForClient = $this->form['client_account_id']
            ? Site::where('client_account_id', $this->form['client_account_id'])->orderBy('name')->get()
            : collect();

        $timeline = [];
        if ($selected) {
            $timeline = collect([
                ['key' => 'opened', 'label' => 'Opened', 'at' => $selected->opened_at],
                ['key' => 'assigned', 'label' => 'Assigned', 'at' => $selected->assigned_at],
                ['key' => 'en_route', 'label' => 'En route', 'at' => $selected->en_route_at],
                ['key' => 'on_scene', 'label' => 'On scene', 'at' => $selected->on_scene_at],
                ['key' => 'resolved', 'label' => 'Resolved', 'at' => $selected->resolved_at],
                ['key' => 'closed', 'label' => 'Closed', 'at' => $selected->closed_at],
            ])->all();
        }

        $mapMarkers = [];
        $trackingUrl = null;
        $patriotPayload = $selected?->patriot_job_id !== null ? app(PatriotDispatchPayload::class)->forDispatch($selected) : null;
        $latitude = $selected?->latitude ?? $patriotPayload['location']['latitude'] ?? $selected?->site?->latitude;
        $longitude = $selected?->longitude ?? $patriotPayload['location']['longitude'] ?? $selected?->site?->longitude;
        if ($selected && $latitude !== null && $longitude !== null) {
            $mapMarkers[] = [
                'lat' => $latitude,
                'lng' => $longitude,
                'label' => $selected->dispatch_number ?? 'Dispatch',
                'meta' => $selected->incident_location ?? $selected->site?->name,
            ];

            $trackingUrl = route('tracking.live', [
                'lat' => $latitude,
                'lng' => $longitude,
                'dispatch' => $selected->id,
                'guard' => $selected->guard_id,
            ]);
        } elseif ($selected?->guard_id) {
            $trackingUrl = route('tracking.live', ['guard' => $selected->guard_id]);
        }

        $criticalAlert = DispatchEvent::forTenant($tenantId)->where('priority', DispatchPriority::CRITICAL)
            ->where('status', DispatchStatus::OPEN)->whereNull('guard_id')->with('site')
            ->orderBy('opened_at')->orderBy('id')->first();

        $stats = [
            'active' => DispatchEvent::forTenant($tenantId)->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED])->count(),
            'critical' => DispatchEvent::where('tenant_id', $tenantId)->where('priority', DispatchPriority::CRITICAL)->whereNotIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED])->count(),
            'pending' => DispatchEvent::forTenant($tenantId)->where('status', DispatchStatus::OPEN)->whereNull('guard_id')->count(),
            'assigned' => DispatchEvent::forTenant($tenantId)->where('status', DispatchStatus::ASSIGNED)->count(),
            'en_route' => DispatchEvent::where('tenant_id', $tenantId)->where('status', DispatchStatus::EN_ROUTE)->count(),
            'sos' => $sosAlerts->where('status', 'open')->count(),
            'all' => DispatchEvent::where('tenant_id', $tenantId)->count(),
            'closed' => DispatchEvent::where('tenant_id', $tenantId)->whereIn('status', [DispatchStatus::CLOSED, DispatchStatus::CANCELLED, DispatchStatus::RESOLVED])->count(),
        ];

        $guards = Guard::forTenant($tenantId)->where('status', 'active')->orderBy('first_name')->get();
        $assignmentGuards = $selected?->patriot_job_id !== null ? $guards->where('duty_type', GuardDutyType::Dispatch) : $guards;
        $onDuty = collect();
        $busy = collect();
        if ($selected?->mayAssignFromPatriot()) {
            $onDuty = AttendanceLog::forTenant($tenantId)->whereIn('guard_id', $assignmentGuards->modelKeys())
                ->whereNotNull('clock_in_at')->whereNull('clock_out_at')->distinct()->pluck('guard_id')->flip();
            $teamMembers = $assignmentGuards->whereNotNull('dispatch_team_id')->groupBy('dispatch_team_id');
            $busy = DispatchEvent::forTenant($tenantId)->whereKeyNot($selected->id)
                ->where(fn ($query) => $query->whereIn('guard_id', $assignmentGuards->modelKeys())
                    ->orWhereIn('dispatch_team_id', $teamMembers->keys()))
                ->where(function ($query): void {
                    $query->whereIn('status', [DispatchStatus::ASSIGNED, DispatchStatus::EN_ROUTE, DispatchStatus::ON_SCENE])
                        ->orWhere(fn ($returning) => $returning->whereNotNull('dispatch_team_id')->whereNull('returned_at')
                            ->whereIn('status', [DispatchStatus::RESOLVED, DispatchStatus::CLOSED, DispatchStatus::CANCELLED]));
                })
                ->latest('id')->get(['guard_id', 'dispatch_team_id', 'dispatch_number'])
                ->flatMap(fn (DispatchEvent $event) => collect([$event->guard_id])
                    ->merge($teamMembers->get($event->dispatch_team_id, collect())->pluck('id'))
                    ->filter()->unique()->map(fn ($guardId) => ['guard_id' => $guardId, 'number' => $event->dispatch_number]))
                ->groupBy('guard_id');
            $assignmentGuards = $assignmentGuards->sortBy(fn (Guard $guard): int => $busy->has($guard->id) ? 2 : ($onDuty->has($guard->id) ? 0 : 1));
        }

        return view('livewire.dispatch.dispatcher-board', [
            'dispatches' => $dispatches,
            'patriotCounts' => $patriotCounts,
            'hasPatriotJobs' => $patriotQueue->exists(),
            'selected' => $selected,
            'patriotPayload' => $patriotPayload,
            'patriotResponseDetails' => $patriotPayload && auth()->user()->can('sites.manage')
                ? (app(PatriotAccountProvisioner::class)->diagnostics($selected)['response_details'] ?? [])
                : [],
            'sosAlerts' => $sosAlerts,
            'clients' => ClientAccount::orderBy('name')->get(),
            'sitesForClient' => $sitesForClient,
            'patriotSites' => $selected?->patriot_job_id !== null && $selected->site_id === null && $selected->status === DispatchStatus::OPEN && auth()->user()->can('sites.manage')
                ? Site::forTenant($tenantId)->orderBy('name')->get(['id', 'name'])
                : collect(),
            'guards' => $guards,
            'dispatchTeams' => DispatchTeam::forTenant($tenantId)->with(['activeDeployment', 'outstandingDispatches'])->withCount([
                'members as active_officers_count' => fn ($query) => $query->where('status', 'active')->where('duty_type', GuardDutyType::Dispatch->value),
            ])->orderBy('name')->get(),
            'assignmentGuards' => $assignmentGuards,
            'onDutyResponders' => $onDuty,
            'busyResponders' => $busy,
            'stats' => $stats,
            'criticalAlert' => $criticalAlert,
            'timeline' => $timeline,
            'mapMarkers' => $mapMarkers,
            'trackingUrl' => $trackingUrl,
            'incidentTypes' => config('dispatch.incident_types'),
            'callerTypes' => config('dispatch.caller_types'),
            'hasActiveFilters' => $this->search !== '' || $this->statusFilter !== 'active' || $this->priorityFilter !== 'all',
        ])->layout('layouts.app');
    }

    private function selectedDispatch(): ?DispatchEvent
    {
        return $this->selectedId ? DispatchEvent::forTenant(TenantContext::id())->find($this->selectedId) : null;
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
