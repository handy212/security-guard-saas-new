<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\PassdownLog;
use App\Services\PassdownService;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PassdownController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', PassdownLog::class);

        $query = PassdownLog::with(['site', 'sitePost', 'assignedGuard'])
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, PassdownService $service): JsonResponse
    {
        $this->authorize('create', PassdownLog::class);

        $data = $this->validatedPassdown($request);
        $passdown = $service->create($data);

        return $this->data($passdown->load(['site', 'sitePost', 'assignedGuard']), 201);
    }

    public function show(PassdownLog $passdown): JsonResponse
    {
        $this->authorize('view', $passdown);

        return $this->data($passdown->load(['site', 'sitePost', 'assignedGuard']));
    }

    public function update(Request $request, PassdownLog $passdown, PassdownService $service): JsonResponse
    {
        $this->authorize('update', $passdown);

        $passdown = $service->update($passdown, $this->validatedPassdown($request, partial: true));

        return $this->data($passdown->load(['site', 'sitePost', 'assignedGuard']));
    }

    public function destroy(PassdownLog $passdown, PassdownService $service): JsonResponse
    {
        $this->authorize('delete', $passdown);
        $service->delete($passdown);

        return $this->noContent();
    }

    private function validatedPassdown(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'site_id' => [...$required, 'integer', TenantValidation::exists('sites')],
            'site_post_id' => ['nullable', 'integer', TenantValidation::exists('site_posts')],
            'guard_id' => ['nullable', 'integer', TenantValidation::exists('guards')],
            'shift_assignment_id' => ['nullable', 'integer', TenantValidation::exists('shift_assignments')],
            'content' => [...$required, 'string', 'min:10'],
        ]);
    }
}
