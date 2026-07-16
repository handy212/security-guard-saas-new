<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Requests\Api\Admin\StoreClientRequest;
use App\Http\Requests\Api\Admin\UpdateClientRequest;
use App\Models\ClientAccount;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ClientAccount::class);

        $query = ClientAccount::query()
            ->when($request->filled('search'), fn ($q) => $q->where(function ($q) use ($request) {
                $term = '%'.$request->string('search').'%';
                $q->where('name', 'like', $term)->orWhere('email', 'like', $term);
            }))
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->orderBy('name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(StoreClientRequest $request): JsonResponse
    {
        $client = ClientAccount::create($request->validated() + ['tenant_id' => TenantContext::id()]);

        return $this->data($client, 201);
    }

    public function show(ClientAccount $client): JsonResponse
    {
        $this->authorize('view', $client);

        return $this->data($client);
    }

    public function update(UpdateClientRequest $request, ClientAccount $client): JsonResponse
    {
        $client->update($request->validated());

        return $this->data($client->fresh());
    }

    public function destroy(ClientAccount $client): JsonResponse
    {
        $this->authorize('delete', $client);
        $client->delete();

        return $this->noContent();
    }
}
