<?php

namespace App\Http\Controllers\Api\Admin\Nested;

use App\Models\ClientAccount;
use App\Models\ClientNote;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientNoteController extends NestedAdminController
{
    public function index(Request $request, ClientAccount $client): JsonResponse
    {
        $this->authorize('view', $client);

        $query = ClientNote::query()
            ->where('client_account_id', $client->id)
            ->with('author')
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, ClientAccount $client): JsonResponse
    {
        $this->authorize('update', $client);

        $note = ClientNote::create($this->validatedNote($request) + [
            'tenant_id' => TenantContext::id(),
            'client_account_id' => $client->id,
            'user_id' => $request->user()->id,
        ]);

        return $this->data($note->load('author'), 201);
    }

    public function show(ClientAccount $client, ClientNote $note): JsonResponse
    {
        $this->authorize('view', $client);
        $this->belongsToClient($client, $note);

        return $this->data($note->load('author'));
    }

    public function update(Request $request, ClientAccount $client, ClientNote $note): JsonResponse
    {
        $this->authorize('update', $client);
        $this->belongsToClient($client, $note);

        $note->update($this->validatedNote($request, partial: true));

        return $this->data($note->fresh()->load('author'));
    }

    public function destroy(ClientAccount $client, ClientNote $note): JsonResponse
    {
        $this->authorize('update', $client);
        $this->belongsToClient($client, $note);
        $note->delete();

        return $this->noContent();
    }

    private function validatedNote(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'body' => [...$required, 'string', 'max:5000'],
            'is_internal' => ['boolean'],
        ]);
    }
}
