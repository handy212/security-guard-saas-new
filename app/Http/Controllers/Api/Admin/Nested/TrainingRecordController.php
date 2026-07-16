<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\Guard;
use App\Models\TrainingRecord;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TrainingRecordController extends NestedAdminController
{
    public function index(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $query = TrainingRecord::query()
            ->where('guard_id', $guard->id)
            ->latest('completed_on');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, Guard $guard): JsonResponse
    {
        $this->authorize('update', $guard);

        $record = TrainingRecord::create($this->validatedRecord($request) + [
            'tenant_id' => TenantContext::id(),
            'guard_id' => $guard->id,
            'status' => $request->string('status', 'completed'),
        ]);

        return $this->data($record, 201);
    }

    public function show(Guard $guard, TrainingRecord $trainingRecord): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $trainingRecord);

        return $this->data($trainingRecord);
    }

    public function update(Request $request, Guard $guard, TrainingRecord $trainingRecord): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $trainingRecord);

        $trainingRecord->update($this->validatedRecord($request, partial: true));

        return $this->data($trainingRecord->fresh());
    }

    public function destroy(Guard $guard, TrainingRecord $trainingRecord): JsonResponse
    {
        $this->authorize('update', $guard);
        $this->belongsToGuard($guard, $trainingRecord);
        $trainingRecord->delete();

        return $this->noContent();
    }

    private function validatedRecord(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'course_name' => [...$required, 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'completed_on' => ['nullable', 'date'],
            'expires_on' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'max:50'],
        ]);
    }
}
