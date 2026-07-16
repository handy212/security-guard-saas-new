<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\ClientAccount;
use App\Models\ClientDocument;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientDocumentController extends NestedAdminController
{
    public function index(Request $request, ClientAccount $client): JsonResponse
    {
        $this->authorize('view', $client);

        $query = ClientDocument::query()
            ->where('client_account_id', $client->id)
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, ClientAccount $client): JsonResponse
    {
        $this->authorize('update', $client);

        $document = ClientDocument::create($this->validatedDocument($request) + [
            'tenant_id' => TenantContext::id(),
            'client_account_id' => $client->id,
        ]);

        return $this->data($document, 201);
    }

    public function show(ClientAccount $client, ClientDocument $document): JsonResponse
    {
        $this->authorize('view', $client);
        $this->belongsToClient($client, $document);

        return $this->data($document);
    }

    public function update(Request $request, ClientAccount $client, ClientDocument $document): JsonResponse
    {
        $this->authorize('update', $client);
        $this->belongsToClient($client, $document);

        $document->update($this->validatedDocument($request, partial: true));

        return $this->data($document->fresh());
    }

    public function destroy(ClientAccount $client, ClientDocument $document): JsonResponse
    {
        $this->authorize('update', $client);
        $this->belongsToClient($client, $document);
        $document->delete();

        return $this->noContent();
    }

    private function validatedDocument(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'title' => [...$required, 'string', 'max:255'],
            'document_type' => [...$required, 'string', 'max:80'],
            'expires_on' => ['nullable', 'date'],
            'client_visible' => ['boolean'],
            'file_path' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
