<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\ClientAccount;
use App\Models\ClientContact;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientContactController extends NestedAdminController
{
    public function index(Request $request, ClientAccount $client): JsonResponse
    {
        $this->authorize('view', $client);

        $query = ClientContact::query()
            ->where('client_account_id', $client->id)
            ->orderBy('name');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, ClientAccount $client): JsonResponse
    {
        $this->authorize('update', $client);

        $contact = ClientContact::create($this->validatedContact($request) + [
            'tenant_id' => TenantContext::id(),
            'client_account_id' => $client->id,
        ]);

        return $this->data($contact, 201);
    }

    public function show(ClientAccount $client, ClientContact $contact): JsonResponse
    {
        $this->authorize('view', $client);
        $this->belongsToClient($client, $contact);

        return $this->data($contact);
    }

    public function update(Request $request, ClientAccount $client, ClientContact $contact): JsonResponse
    {
        $this->authorize('update', $client);
        $this->belongsToClient($client, $contact);

        $contact->update($this->validatedContact($request, partial: true));

        return $this->data($contact->fresh());
    }

    public function destroy(ClientAccount $client, ClientContact $contact): JsonResponse
    {
        $this->authorize('update', $client);
        $this->belongsToClient($client, $contact);
        $contact->delete();

        return $this->noContent();
    }

    private function validatedContact(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'name' => [...$required, 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:40'],
            'role' => ['nullable', 'string', 'max:120'],
        ]);
    }
}
