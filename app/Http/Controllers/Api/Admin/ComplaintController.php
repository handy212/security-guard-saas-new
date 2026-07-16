<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\ClientComplaint;
use App\Services\ComplaintService;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class ComplaintController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClientComplaint::class);

        $query = ClientComplaint::with(['clientAccount', 'site'])
            ->when($request->filled('search'), fn ($q) => $q->where('subject', 'like', '%'.$request->string('search').'%'))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, ComplaintService $service): JsonResponse
    {
        $this->authorize('create', ClientComplaint::class);

        $complaint = $service->create($this->validatedComplaint($request));

        return $this->data($complaint->load(['clientAccount', 'site']), 201);
    }

    public function show(ClientComplaint $complaint): JsonResponse
    {
        $this->authorize('update', $complaint);

        return $this->data($complaint->load(['clientAccount', 'site']));
    }

    public function update(Request $request, ClientComplaint $complaint, ComplaintService $service): JsonResponse
    {
        $this->authorize('update', $complaint);

        try {
            $complaint = $service->update($complaint, $this->validatedComplaint($request, partial: true));
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->data($complaint->load(['clientAccount', 'site']));
    }

    public function destroy(ClientComplaint $complaint, ComplaintService $service): JsonResponse
    {
        $this->authorize('delete', $complaint);

        try {
            $service->delete($complaint);
        } catch (RuntimeException $e) {
            return $this->serviceError($e);
        }

        return $this->noContent();
    }

    public function resolve(ClientComplaint $complaint, ComplaintService $service): JsonResponse
    {
        $this->authorize('update', $complaint);

        return $this->data($service->resolve($complaint)->load(['clientAccount', 'site']));
    }

    private function validatedComplaint(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'client_account_id' => [...$required, 'integer', TenantValidation::exists('client_accounts')],
            'site_id' => ['nullable', 'integer', TenantValidation::exists('sites')],
            'subject' => [...$required, 'string', 'max:255'],
            'description' => [...$required, 'string'],
            'priority' => [...$required, 'string', 'max:50'],
            'assigned_to' => ['nullable', 'string', 'max:255'],
        ]);
    }
}
