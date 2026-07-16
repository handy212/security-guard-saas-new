<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\VisitorLog;
use App\Services\VisitorService;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class VisitorController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', VisitorLog::class);

        $query = VisitorLog::with(['site', 'assignedGuard'])
            ->when($request->filled('search'), fn ($q) => $q->where('visitor_name', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, VisitorService $service): JsonResponse
    {
        $this->authorize('create', VisitorLog::class);

        $visitor = $service->checkIn($this->validatedVisitor($request) + ['status' => 'checked_in']);

        return $this->data($visitor->load(['site', 'assignedGuard']), 201);
    }

    public function show(VisitorLog $visitor): JsonResponse
    {
        $this->authorize('update', $visitor);

        return $this->data($visitor->load(['site', 'assignedGuard']));
    }

    public function update(Request $request, VisitorLog $visitor, VisitorService $service): JsonResponse
    {
        $this->authorize('update', $visitor);

        try {
            $visitor = $service->update($visitor, $this->validatedVisitor($request, partial: true));
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->data($visitor->load(['site', 'assignedGuard']));
    }

    public function destroy(VisitorLog $visitor, VisitorService $service): JsonResponse
    {
        $this->authorize('delete', $visitor);

        try {
            $service->delete($visitor);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->noContent();
    }

    public function checkOut(VisitorLog $visitor, VisitorService $service): JsonResponse
    {
        $this->authorize('update', $visitor);

        return $this->data($service->checkOut($visitor)->load(['site', 'assignedGuard']));
    }

    private function validatedVisitor(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'site_id' => [...$required, 'integer', TenantValidation::exists('sites')],
            'visitor_name' => [...$required, 'string', 'max:255'],
            'visitor_phone' => ['nullable', 'string', 'max:50'],
            'company' => ['nullable', 'string', 'max:255'],
            'purpose' => ['nullable', 'string', 'max:255'],
            'vehicle_plate' => ['nullable', 'string', 'max:50'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:120'],
            'guard_id' => ['nullable', 'integer', TenantValidation::exists('guards')],
        ]);
    }
}
