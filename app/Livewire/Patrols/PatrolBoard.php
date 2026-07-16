<?php

namespace App\Livewire\Patrols;

use App\Enums\VehicleStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\FleetVehicle;
use App\Models\Guard;
use App\Models\PatrolCheckpoint;
use App\Models\PatrolRoute;
use App\Models\PatrolSession;
use App\Models\Site;
use App\Services\FleetService;
use App\Services\PatrolService;
use App\Support\TenantContext;
use Livewire\Component;
use RuntimeException;

class PatrolBoard extends Component
{
    use AuthorizesModuleAccess;

    public string $search = '';

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

    public function editRoute(int $id): void
    {
        $route = PatrolRoute::where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingRouteId = $route->id;
        $this->routeForm = [
            'site_id' => (string) $route->site_id,
            'name' => $route->name,
            'description' => $route->description ?? '',
            'expected_duration_minutes' => $route->expected_duration_minutes ?? 30,
            'status' => $route->status ?? 'active',
        ];
    }

    public function saveRoute(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $data = $this->validate([
            'routeForm.site_id' => 'required',
            'routeForm.name' => 'required',
            'routeForm.description' => 'nullable|string|max:2000',
            'routeForm.expected_duration_minutes' => 'integer',
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

        $this->editingRouteId = null;
        $this->routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];
    }

    public function deleteRoute(int $id): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $route = PatrolRoute::where('tenant_id', TenantContext::id())->findOrFail($id);
        $route->checkpoints()->delete();
        $route->delete();
        if ($this->editingRouteId === $id) {
            $this->editingRouteId = null;
            $this->routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];
        }
        session()->flash('status', 'Route deleted.');
    }

    public function editCheckpoint(int $id): void
    {
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
    }

    public function saveCheckpoint(): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $data = $this->validate([
            'checkpointForm.patrol_route_id' => 'required',
            'checkpointForm.name' => 'required',
            'checkpointForm.code' => 'required',
            'checkpointForm.sequence' => 'integer',
            'checkpointForm.latitude' => 'nullable|numeric',
            'checkpointForm.longitude' => 'nullable|numeric',
            'checkpointForm.instructions' => 'nullable|string|max:1000',
        ])['checkpointForm'];

        $data['latitude'] = $data['latitude'] !== '' ? $data['latitude'] : null;
        $data['longitude'] = $data['longitude'] !== '' ? $data['longitude'] : null;
        $data['instructions'] = $data['instructions'] !== '' ? $data['instructions'] : null;

        if ($this->editingCheckpointId) {
            $checkpoint = PatrolCheckpoint::where('tenant_id', TenantContext::id())->findOrFail($this->editingCheckpointId);
            $checkpoint->update($data);
            session()->flash('status', 'Checkpoint updated.');
        } else {
            PatrolCheckpoint::create($data + ['tenant_id' => TenantContext::id()]);
            session()->flash('status', 'Checkpoint created.');
        }

        $this->editingCheckpointId = null;
        $this->checkpointForm = [
            'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
            'latitude' => '', 'longitude' => '', 'instructions' => '',
        ];
    }

    public function deleteCheckpoint(int $id): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);
        $checkpoint = PatrolCheckpoint::where('tenant_id', TenantContext::id())->findOrFail($id);
        $checkpoint->delete();
        if ($this->editingCheckpointId === $id) {
            $this->editingCheckpointId = null;
        }
        session()->flash('status', 'Checkpoint deleted.');
    }

    public function assignPatrol(PatrolService $patrols, FleetService $fleet): void
    {
        abort_unless(auth()->user()->can('patrols.manage'), 403);

        $data = $this->validate([
            'assignForm.patrol_route_id' => 'required|exists:patrol_routes,id',
            'assignForm.guard_id' => 'required|exists:guards,id',
            'assignForm.vehicle_id' => 'nullable|exists:fleet_vehicles,id',
        ])['assignForm'];

        try {
            \Illuminate\Support\Facades\DB::transaction(function () use ($patrols, $fleet, $data) {
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
            $field = str_contains(strtolower($e->getMessage()), 'vehicle')
                ? 'assignForm.vehicle_id'
                : 'assignForm.guard_id';
            $this->addError($field, $e->getMessage());

            return;
        }

        $this->assignForm = ['patrol_route_id' => '', 'guard_id' => '', 'vehicle_id' => ''];
        session()->flash('status', 'Patrol assigned and started.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $search = trim($this->search);

        $routes = PatrolRoute::with(['site', 'checkpoints'])
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('site', fn ($s) => $s->where('name', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->get();

        $sessions = PatrolSession::with(['route.site', 'assignedGuard', 'scans', 'vehiclePatrol.vehicle'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(20)
            ->get();

        $submissions = \App\Models\TaskSubmission::with(['task', 'scan.assignedGuard', 'scan.checkpoint'])
            ->where('tenant_id', $tenantId)
            ->latest()
            ->limit(30)
            ->get();

        return view('livewire.patrols.patrol-board', [
            'routes' => $routes,
            'sessions' => $sessions,
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
                'active_sessions' => $sessions->where('status', 'in_progress')->count(),
                'completed_today' => $sessions->filter(fn ($s) => $s->completed_at?->isToday())->count(),
                'fleet_available' => FleetVehicle::where('tenant_id', $tenantId)->where('status', VehicleStatus::AVAILABLE)->count(),
            ],
        ])->layout('layouts.app');
    }
}
