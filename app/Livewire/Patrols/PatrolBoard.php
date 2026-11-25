<?php

namespace App\Livewire\Patrols;

use App\Enums\VehicleStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\CheckpointScan;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\Site;
use App\Models\TaskSubmission;
use App\Services\FleetService;
use App\Services\PatrolService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use RuntimeException;

class PatrolBoard extends Component
{
    use AuthorizesModuleAccess;

    public string $search = '';

    public ?string $activeDrawer = null;

    public function mount(): void
    {
        $this->authorizePermission('patrols.manage');
    }

    public array $routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];

    public ?int $editingRouteId = null;

    public ?int $editingCheckpointId = null;

    public array $checkpointForm = [
        'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
        'latitude' => '', 'longitude' => '', 'instructions' => '',
    ];

    public array $assignForm = [
        'patrol_route_id' => '',
        'guard_id' => '',
        'vehicle_id' => '',
    ];

    public function openRouteForm(): void
    {
        $this->editingRouteId = null;
        $this->routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];
        $this->resetErrorBag();
        $this->activeDrawer = 'route';
    }

    public function openCheckpointForm(?int $routeId = null): void
    {
        $this->authorizePermission('patrols.manage');
        $this->editingCheckpointId = null;
        $this->checkpointForm = [
            'patrol_route_id' => $routeId ? (string) $routeId : '',
            'name' => '',
            'code' => '',
            'sequence' => $routeId ? $this->nextCheckpointSequence($routeId) : 1,
            'latitude' => '',
            'longitude' => '',
            'instructions' => '',
        ];
        $this->resetErrorBag();
        $this->activeDrawer = 'checkpoint';
    }

    public function updatedCheckpointFormPatrolRouteId(mixed $routeId): void
    {
        if (! $this->editingCheckpointId && is_numeric($routeId)) {
            $this->checkpointForm['sequence'] = $this->nextCheckpointSequence((int) $routeId);
        }
    }

    private function nextCheckpointSequence(int $routeId): int
    {
        return (int) PatrolCheckpoint::forTenant(TenantContext::id())
            ->where('patrol_route_id', $routeId)
            ->max('sequence') + 1;
    }

    public function openAssignForm(): void
    {
        $this->assignForm = ['patrol_route_id' => '', 'guard_id' => '', 'vehicle_id' => ''];
        $this->resetErrorBag();
        $this->activeDrawer = 'assign';
    }

    public function closeDrawer(): void
    {
        $this->activeDrawer = null;
        $this->editingRouteId = null;
        $this->editingCheckpointId = null;
        $this->routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];
        $this->checkpointForm = [
            'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
            'latitude' => '', 'longitude' => '', 'instructions' => '',
        ];
        $this->assignForm = ['patrol_route_id' => '', 'guard_id' => '', 'vehicle_id' => ''];
        $this->resetErrorBag();
    }

    public function editRoute(int $id): void
    {
        $this->authorizePermission('patrols.manage');
        $route = PatrolRoute::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingRouteId = $route->id;
        $this->routeForm = [
            'site_id' => (string) $route->site_id,
            'name' => $route->name,
            'description' => $route->description ?? '',
            'expected_duration_minutes' => $route->expected_duration_minutes ?? 30,
            'status' => $route->status ?? 'active',
        ];
        $this->resetErrorBag();
        $this->activeDrawer = 'route';
    }

    public function saveRoute(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $data = $this->validate([
            'routeForm.site_id' => ['required', TenantValidation::exists('sites')],
            'routeForm.name' => 'required|string|max:255',
            'routeForm.description' => 'nullable|string|max:2000',
            'routeForm.expected_duration_minutes' => 'required|integer|min:1|max:65535',
            'routeForm.status' => 'required|in:active,inactive',
        ])['routeForm'];
        $data['description'] = $data['description'] !== '' ? $data['description'] : null;

        if ($this->editingRouteId) {
            $route = PatrolRoute::where('tenant_id', TenantContext::id())->findOrFail($this->editingRouteId);
            $route->update($data);
            session()->flash('status', 'Route updated.');
        } else {
            PatrolRoute::create($data + ['tenant_id' => TenantContext::id()]);
            session()->flash('status', 'Route created.');
        }

        $this->closeDrawer();
    }

    public function deleteRoute(int $id): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $route = PatrolRoute::where('tenant_id', TenantContext::id())->findOrFail($id);
        if (PatrolSession::forTenant(TenantContext::id())->where('patrol_route_id', $id)->exists()) {
            $this->addError('patrolAction', 'This route has patrol history. Set it to inactive to stop new patrols.');

            return;
        }
        $route->delete();
        if ($this->editingRouteId === $id) {
            $this->closeDrawer();
        }
        session()->flash('status', 'Route deleted.');
    }

    public function editCheckpoint(int $id): void
    {
        $this->authorizePermission('patrols.manage');
        $checkpoint = PatrolCheckpoint::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingCheckpointId = $checkpoint->id;
        $this->checkpointForm = [
            'patrol_route_id' => (string) $checkpoint->patrol_route_id,
            'name' => $checkpoint->name,
            'code' => $checkpoint->code,
            'sequence' => $checkpoint->sequence ?? 1,
            'latitude' => $checkpoint->latitude !== null ? (string) $checkpoint->latitude : '',
            'longitude' => $checkpoint->longitude !== null ? (string) $checkpoint->longitude : '',
            'instructions' => $checkpoint->instructions ?? '',
        ];
        $this->resetErrorBag();
        $this->activeDrawer = 'checkpoint';
    }

    public function saveCheckpoint(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $checkpoint = $this->editingCheckpointId
            ? PatrolCheckpoint::forTenant(TenantContext::id())->findOrFail($this->editingCheckpointId)
            : null;

        $data = $this->validate([
            'checkpointForm.patrol_route_id' => ['required', TenantValidation::exists('patrol_routes')],
            'checkpointForm.name' => 'required|string|max:255',
            'checkpointForm.code' => ['required', 'string', 'max:255', Rule::unique('patrol_checkpoints', 'code')->ignore($checkpoint)],
            'checkpointForm.sequence' => 'required|integer|min:1|max:65535',
            'checkpointForm.latitude' => 'nullable|required_with:checkpointForm.longitude|numeric|between:-90,90',
            'checkpointForm.longitude' => 'nullable|required_with:checkpointForm.latitude|numeric|between:-180,180',
            'checkpointForm.instructions' => 'nullable|string|max:1000',
        ])['checkpointForm'];

        $data['latitude'] = $data['latitude'] !== '' ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' ? $data['longitude'] : null;
        $data['instructions'] = $data['instructions'] !== '' ? $data['instructions'] : null;

        if ($checkpoint) {
            $checkpoint->update($data);
            session()->flash('status', 'Checkpoint updated.');
        } else {
            PatrolCheckpoint::create($data + ['tenant_id' => TenantContext::id()]);
            session()->flash('status', 'Checkpoint created.');
        }

        $this->closeDrawer();
    }

    public function deleteCheckpoint(int $id): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $checkpoint = PatrolCheckpoint::where('tenant_id', TenantContext::id())->findOrFail($id);
        if (CheckpointScan::forTenant(TenantContext::id())->where('patrol_checkpoint_id', $id)->exists()
            || PatrolSession::forTenant(TenantContext::id())->where('patrol_route_id', $checkpoint->patrol_route_id)->where('status', 'in_progress')->exists()) {
            $this->addError('patrolAction', 'This checkpoint has scan history or is part of an active patrol.');

            return;
        }
        $checkpoint->delete();
        if ($this->editingCheckpointId === $id) {
            $this->closeDrawer();
        }
        session()->flash('status', 'Checkpoint deleted.');
    }

    public function assignPatrol(PatrolService $patrols, FleetService $fleet): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);

        $data = $this->validate([
            'assignForm.patrol_route_id' => ['required', TenantValidation::exists('patrol_routes')->where('status', 'active')],
            'assignForm.guard_id' => ['required', TenantValidation::exists('guards')->where('status', 'active')->where('verification_status', 'verified')],
            'assignForm.vehicle_id' => ['nullable', TenantValidation::exists('fleet_vehicles')->where('status', VehicleStatus::AVAILABLE->value)],
        ])['assignForm'];

        try {
            DB::transaction(function () use ($patrols, $fleet, $data) {
                $session = $patrols->assignAndStart(
                    TenantContext::id(),
                    (int) $data['patrol_route_id'],
                    (int) $data['guard_id'],
                );

                if (! empty($data['vehicle_id'])) {
                    $fleet->startTrip([
                        'vehicle_id' => (int) $data['vehicle_id'],
                        'guard_id' => (int) $data['guard_id'],
                        'patrol_session_id' => $session->id,
                    ]);
                }
            });
        } catch (RuntimeException $e) {
            $field = match (true) {
                str_contains(strtolower($e->getMessage()), 'vehicle') => 'assignForm.vehicle_id',
                str_contains(strtolower($e->getMessage()), 'active route') => 'assignForm.patrol_route_id',
                default => 'assignForm.guard_id',
            };
            $this->addError($field, $e->getMessage());

            return;
        }

        $this->closeDrawer();
        session()->flash('status', 'Patrol assigned and started.');
    }

    public function render()
    {
        $this->authorizePermission('patrols.manage');
        $tenantId = TenantContext::id();
        $search = trim($this->search);

        $visibleRoute = fn (Builder $query) => $query->where('status', '!=', 'archived');
        $visibleSessions = PatrolSession::forTenant($tenantId)->whereHas('route', $visibleRoute);
        $routes = PatrolRoute::with(['site', 'checkpoints'])
            ->where($visibleRoute)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('site', fn ($s) => $s->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->get();

        $sessions = (clone $visibleSessions)
            ->with(['route' => fn ($query) => $query->with('site')->withCount('checkpoints'), 'assignedGuard', 'vehiclePatrol.vehicle'])
            ->withCount(['scans', 'scans as verified_checkpoints_count' => fn (Builder $query) => $query
                ->where('status', 'valid')->select(DB::raw('COUNT(DISTINCT patrol_checkpoint_id)'))])
            ->latest()
            ->limit(20)
            ->get();

        $submissions = TaskSubmission::with(['task', 'scan.assignedGuard', 'scan.checkpoint'])
            ->where('tenant_id', $tenantId)
            ->whereHas('scan.session.route', $visibleRoute)
            ->latest()
            ->limit(30)
            ->get();

        return view('livewire.patrols.patrol-board', [
            'routes' => $routes,
            'sessions' => $sessions,
            'assignableRoutes' => $this->activeDrawer === 'assign'
                ? PatrolRoute::with('site')->where('status', 'active')->has('checkpoints')->orderBy('name')->get()
                : collect(),
            'submissions' => $submissions,
            'sites' => Site::orderBy('name')->get(),
            'guards' => Guard::where('tenant_id', $tenantId)
                ->where('status', 'active')
                ->where('verification_status', 'verified')
                ->orderBy('first_name')
                ->get(),
            'availableFleet' => FleetVehicle::where('tenant_id', $tenantId)
                ->where('status', VehicleStatus::AVAILABLE)
                ->orderBy('plate_number')
                ->get(),
            'stats' => [
                'routes' => $routes->count(),
                'checkpoints' => $routes->sum(fn ($r) => $r->checkpoints->count()),
                'active_sessions' => (clone $visibleSessions)->where('status', 'in_progress')->count(),
                'completed_today' => (clone $visibleSessions)->where('status', 'completed')->whereBetween('completed_at', [today(), today()->endOfDay()])->count(),
            ],
        ])->layout('layouts.app');
    }
}
