<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\VehicleStatus;
use App\Enums\VehicleType;
use App\Models\FleetVehicle;
use App\Services\FleetService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class FleetVehicleController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', FleetVehicle::class);

        $query = FleetVehicle::with('site')
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('plate_number', 'like', $term)
                    ->orWhere('name', 'like', $term)
                    ->orWhere('make', 'like', $term)
                    ->orWhere('model', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('type'), fn ($q) => $q->where('type', $request->string('type')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, FleetService $fleet): JsonResponse
    {
        $this->authorize('create', FleetVehicle::class);

        $data = $this->validatedVehicle($request);
        $vehicle = $fleet->create($data + ['tenant_id' => TenantContext::id()]);

        return $this->data($vehicle->load('site'), 201);
    }

    public function show(FleetVehicle $fleetVehicle): JsonResponse
    {
        $this->authorize('view', $fleetVehicle);

        return $this->data($fleetVehicle->load('site'));
    }

    public function update(Request $request, FleetVehicle $fleetVehicle, FleetService $fleet): JsonResponse
    {
        $this->authorize('update', $fleetVehicle);

        $data = $this->validatedVehicle($request, $fleetVehicle, partial: true);
        $vehicle = $fleet->update($fleetVehicle, $data);

        return $this->data($vehicle->load('site'));
    }

    public function destroy(FleetVehicle $fleetVehicle, FleetService $fleet): JsonResponse
    {
        $this->authorize('delete', $fleetVehicle);

        try {
            $fleet->delete($fleetVehicle);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->noContent();
    }

    private function validatedVehicle(Request $request, ?FleetVehicle $vehicle = null, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        $data = $request->validate([
            'type' => [...$required, Rule::enum(VehicleType::class)],
            'plate_number' => [
                ...$required, 'string', 'max:40',
                Rule::unique('fleet_vehicles', 'plate_number')
                    ->where(fn ($q) => $q->where('tenant_id', TenantContext::id()))
                    ->ignore($vehicle?->id),
            ],
            'name' => ['nullable', 'string', 'max:120'],
            'make' => ['nullable', 'string', 'max:80'],
            'model' => ['nullable', 'string', 'max:80'],
            'site_id' => ['nullable', 'integer', TenantValidation::exists('sites')],
            'status' => [...$required, Rule::enum(VehicleStatus::class)],
            'current_odometer' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        if (isset($data['plate_number'])) {
            $data['plate_number'] = strtoupper(trim($data['plate_number']));
        }

        return $data;
    }
}
