<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\PatrolRoute;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PatrolRouteController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('patrols.manage'), 403);

        $query = PatrolRoute::with(['site', 'checkpoints'])
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('patrols.manage'), 403);

        $data = $this->validatedRoute($request);
        $route = PatrolRoute::create($data + ['tenant_id' => TenantContext::id()]);

        return $this->data($route->load(['site', 'checkpoints']), 201);
    }

    public function show(PatrolRoute $patrolRoute): JsonResponse
    {
        abort_unless(request()->user()->can('patrols.manage'), 403);
        abort_unless((int) $patrolRoute->tenant_id === TenantContext::id(), 404);

        return $this->data($patrolRoute->load(['site', 'checkpoints']));
    }

    public function update(Request $request, PatrolRoute $patrolRoute): JsonResponse
    {
        abort_unless($request->user()->can('patrols.manage'), 403);
        abort_unless((int) $patrolRoute->tenant_id === TenantContext::id(), 404);

        $patrolRoute->update($this->validatedRoute($request, partial: true));

        return $this->data($patrolRoute->fresh()->load(['site', 'checkpoints']));
    }

    public function destroy(PatrolRoute $patrolRoute): JsonResponse
    {
        abort_unless(request()->user()->can('patrols.manage'), 403);
        abort_unless((int) $patrolRoute->tenant_id === TenantContext::id(), 404);

        $patrolRoute->checkpoints()->delete();
        $patrolRoute->delete();

        return $this->noContent();
    }

    private function validatedRoute(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'site_id' => [...$required, 'integer', TenantValidation::exists('sites')],
            'name' => [...$required, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'expected_duration_minutes' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
