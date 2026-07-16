<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\WebhookSubscription;
use App\Services\WebhookDeliveryService;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WebhookController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', WebhookSubscription::class);

        $query = WebhookSubscription::query()
            ->when($request->filled('event'), fn ($q) => $q->where('event', $request->string('event')))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', WebhookSubscription::class);

        $data = $request->validate([
            'event' => ['required', 'string', 'max:120'],
            'target_url' => ['required', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $subscription = WebhookSubscription::create([
            'tenant_id' => TenantContext::id(),
            'event' => $data['event'],
            'target_url' => $data['target_url'],
            'secret' => WebhookDeliveryService::generateSecret(),
            'is_active' => $data['is_active'] ?? true,
        ]);

        return $this->data($subscription, 201);
    }

    public function show(WebhookSubscription $webhook): JsonResponse
    {
        $this->authorize('view', $webhook);

        return $this->data($webhook);
    }

    public function update(Request $request, WebhookSubscription $webhook): JsonResponse
    {
        $this->authorize('update', $webhook);

        $data = $request->validate([
            'event' => ['sometimes', 'required', 'string', 'max:120'],
            'target_url' => ['sometimes', 'required', 'url', 'max:255'],
            'is_active' => ['boolean'],
        ]);

        $webhook->update($data);

        return $this->data($webhook->fresh());
    }

    public function destroy(WebhookSubscription $webhook): JsonResponse
    {
        $this->authorize('delete', $webhook);
        $webhook->delete();

        return $this->noContent();
    }
}
