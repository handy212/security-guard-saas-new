<?php

namespace App\Http\Controllers\Api\Admin;

use App\Enums\ShiftStatus;
use App\Models\Shift;
use App\Services\ScheduleService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ShiftController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Shift::class);

        $query = Shift::with(['site', 'clientAccount', 'assignments.guard'])
            ->when($request->filled('date'), fn ($q) => $q->whereDate('starts_at', $request->string('date')))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('site_id'), fn ($q) => $q->where('site_id', $request->integer('site_id')))
            ->orderBy('starts_at');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, ScheduleService $service): JsonResponse
    {
        $this->authorize('create', Shift::class);

        $data = $this->validatedShift($request);
        $shift = $service->createShift($data + [
            'tenant_id' => TenantContext::id(),
            'status' => ShiftStatus::OPEN->value,
        ]);

        return $this->data($shift->load(['site', 'clientAccount']), 201);
    }

    public function show(Shift $shift): JsonResponse
    {
        $this->authorize('update', $shift);

        return $this->data($shift->load(['site', 'clientAccount', 'assignments.guard']));
    }

    public function update(Request $request, Shift $shift, ScheduleService $service): JsonResponse
    {
        $this->authorize('update', $shift);

        $data = $this->validatedShift($request, partial: true);
        $payload = array_merge($shift->only([
            'client_account_id', 'site_id', 'site_post_id', 'title', 'starts_at', 'ends_at',
            'required_guards', 'billing_rate', 'billable_hours', 'notes',
        ]), $data);
        $payload['status'] = $shift->status instanceof ShiftStatus ? $shift->status->value : $shift->status;

        $shift = $service->updateShift($shift, $payload);

        return $this->data($shift->load(['site', 'clientAccount', 'assignments.guard']));
    }

    public function destroy(Shift $shift, ScheduleService $service): JsonResponse
    {
        $this->authorize('delete', $shift);
        $service->cancelShift($shift);

        return $this->noContent();
    }

    private function validatedShift(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'client_account_id' => [...$required, 'integer', TenantValidation::exists('client_accounts')],
            'site_id' => [...$required, 'integer', TenantValidation::exists('sites')],
            'site_post_id' => ['nullable', 'integer', TenantValidation::exists('site_posts')],
            'title' => [...$required, 'string', 'max:255'],
            'starts_at' => [...$required, 'date'],
            'ends_at' => [...$required, 'date', 'after:starts_at'],
            'required_guards' => ['nullable', 'integer', 'min:1'],
            'billing_rate' => ['nullable', 'numeric', 'min:0'],
            'billable_hours' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);
    }
}
