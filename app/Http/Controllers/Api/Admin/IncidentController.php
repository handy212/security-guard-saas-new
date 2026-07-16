<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\StoreIncidentRequest;
use App\Http\Requests\Api\Admin\UpdateIncidentRequest;
use App\Models\Incident;
use App\Services\IncidentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class IncidentController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Incident::class);

        $query = Incident::with('site')
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('severity'), fn ($q) => $q->where('severity', $request->string('severity')))
            ->latest('reported_at');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(StoreIncidentRequest $request, IncidentService $service): JsonResponse
    {
        $incident = $service->submit($request->validated() + ['tenant_id' => $request->user()->tenant_id]);

        return $this->data($incident->load('site'), 201);
    }

    public function show(Incident $incident): JsonResponse
    {
        $this->authorize('update', $incident);

        return $this->data($incident->load(['site', 'media']));
    }

    public function update(UpdateIncidentRequest $request, Incident $incident, IncidentService $service): JsonResponse
    {
        try {
            $incident = $service->update($incident, $request->validated());
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->data($incident->load('site'));
    }

    public function destroy(Incident $incident, IncidentService $service): JsonResponse
    {
        $this->authorize('delete', $incident);

        try {
            $service->delete($incident);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->noContent();
    }

    public function approve(Incident $incident, IncidentService $service, Request $request): JsonResponse
    {
        $this->authorize('approve', $incident);
        $incident = $service->approve($incident, $request->user()->id);

        return $this->data($incident);
    }

    public function close(Request $request, Incident $incident, IncidentService $service): JsonResponse
    {
        $this->authorize('close', $incident);

        $data = $request->validate(['resolution' => ['nullable', 'string', 'max:2000']]);
        $incident = $service->close($incident, $data['resolution'] ?? null);

        return $this->data($incident);
    }

    public function reject(Request $request, Incident $incident, IncidentService $service): JsonResponse
    {
        $this->authorize('reject', $incident);

        $data = $request->validate(['resolution' => ['nullable', 'string', 'max:2000']]);
        $incident = $service->reject($incident, $data['resolution'] ?? null);

        return $this->data($incident);
    }
}
