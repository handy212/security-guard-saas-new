<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\PatrolCheckpoint;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatrolCheckpointController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('patrols.manage'), 403);

        $query = PatrolCheckpoint::with('route')
            ->when($request->filled('patrol_route_id'), fn ($q) => $q->where('patrol_route_id', $request->integer('patrol_route_id')))
            ->orderBy('sequence');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('patrols.manage'), 403);

        $data = $this->validatedCheckpoint($request);
        $checkpoint = PatrolCheckpoint::create($data + ['tenant_id' => TenantContext::id()]);

        return $this->data($checkpoint->load('route'), 201);
    }

    public function show(PatrolCheckpoint $patrolCheckpoint): JsonResponse
    {
        abort_unless(request()->user()->can('patrols.manage'), 403);
        abort_unless((int) $patrolCheckpoint->tenant_id === TenantContext::id(), 404);

        return $this->data($patrolCheckpoint->load('route'));
    }

    public function update(Request $request, PatrolCheckpoint $patrolCheckpoint): JsonResponse
    {
        abort_unless($request->user()->can('patrols.manage'), 403);
        abort_unless((int) $patrolCheckpoint->tenant_id === TenantContext::id(), 404);

        $patrolCheckpoint->update($this->validatedCheckpoint($request, partial: true));

        return $this->data($patrolCheckpoint->fresh()->load('route'));
    }

    public function destroy(PatrolCheckpoint $patrolCheckpoint): JsonResponse
    {
        abort_unless(request()->user()->can('patrols.manage'), 403);
        abort_unless((int) $patrolCheckpoint->tenant_id === TenantContext::id(), 404);

        $patrolCheckpoint->delete();

        return $this->noContent();
    }

    private function validatedCheckpoint(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'patrol_route_id' => [...$required, 'integer', TenantValidation::exists('patrol_routes')],
            'name' => [...$required, 'string', 'max:255'],
            'code' => [...$required, 'string', 'max:100'],
            'sequence' => ['nullable', 'integer', 'min:1'],
            'latitude' => ['nullable', 'numeric'],
            'longitude' => ['nullable', 'numeric'],
            'instructions' => ['nullable', 'string', 'max:2000'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
