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

    public array $checkpointForm = [
        'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
        'latitude' => '', 'longitude' => '', 'instructions' => '',
    ];

    public array $assignForm = [
        'patrol_route_id' => '',
        'guard_id' => '',
        'vehicle_id' => '',
    ];

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
        PatrolRoute::create($data + ['tenant_id' => TenantContext::id()]);
        $this->routeForm = ['site_id' => '', 'name' => '', 'description' => '', 'expected_duration_minutes' => 30, 'status' => 'active'];
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

        PatrolCheckpoint::create($data + ['tenant_id' => TenantContext::id()]);
        $this->checkpointForm = [
            'patrol_route_id' => '', 'name' => '', 'code' => '', 'sequence' => 1,
            'latitude' => '', 'longitude' => '', 'instructions' => '',
        ];
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
            $session = $patrols->assignAndStart(
                TenantContext::id(),
                (int) $data['patrol_route_id'],
                (int) $data['guard_id'],
            );
        } catch (RuntimeException $e) {
            $this->addError('assignForm.guard_id', $e->getMessage());

            return;
        }

        if (! empty($data['vehicle_id'])) {
            try {
                $fleet->startTrip([
                    'vehicle_id' => (int) $data['vehicle_id'],
                    'guard_id' => (int) $data['guard_id'],
                    'patrol_session_id' => $session->id,
                ]);
            } catch (RuntimeException $e) {
                $this->addError('assignForm.vehicle_id', $e->getMessage());
                session()->flash('status', 'Patrol started, but vehicle could not be assigned: '.$e->getMessage());

                return;
            }
        }

        $this->assignForm = ['patrol_route_id' => '', 'guard_id' => '', 'vehicle_id' => ''];
        session()->flash('status', 'Patrol assigned and started.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $routes = PatrolRoute::with(['site', 'checkpoints'])->latest()->get();
        $sessions = PatrolSession::with(['route', 'assignedGuard', 'scans'])->latest()->limit(20)->get();
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
            'guards' => Guard::where('tenant_id', $tenantId)->where('status', 'active')->orderBy('first_name')->get(),
            'availableFleet' => FleetVehicle::where('tenant_id', $tenantId)
                ->where('status', VehicleStatus::AVAILABLE)
                ->orderBy('plate_number')
                ->get(),
            'stats' => [
                'routes' => $routes->count(),
                'checkpoints' => $routes->sum(fn ($r) => $r->checkpoints->count()),
                'active_sessions' => $sessions->where('status', 'in_progress')->count(),
                'completed_today' => $sessions->filter(fn ($s) => $s->completed_at?->isToday())->count(),
            ],
        ])->layout('layouts.app');
    }
}
