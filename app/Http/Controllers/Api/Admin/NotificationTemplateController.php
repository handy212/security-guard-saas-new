<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\NotificationTemplate;
use App\Support\TenantContext;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationTemplateController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', NotificationTemplate::class);

        $query = NotificationTemplate::query()
            ->when($request->filled('search'), fn ($q) => $q->where('code', 'like', '%'.$request->string('search').'%'))
            ->orderBy('code');

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorize('create', NotificationTemplate::class);

        $data = $this->validatedTemplate($request);
        $template = NotificationTemplate::create($data + ['tenant_id' => TenantContext::id()]);

        return $this->data($template, 201);
    }

    public function show(NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('view', $notificationTemplate);

        return $this->data($notificationTemplate);
    }

    public function update(Request $request, NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('update', $notificationTemplate);

        $notificationTemplate->update($this->validatedTemplate($request, partial: true));

        return $this->data($notificationTemplate->fresh());
    }

    public function destroy(NotificationTemplate $notificationTemplate): JsonResponse
    {
        $this->authorize('delete', $notificationTemplate);
        $notificationTemplate->delete();

        return $this->noContent();
    }

    private function validatedTemplate(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        return $request->validate([
            'code' => [...$required, 'string', 'max:120'],
            'channel' => [...$required, 'in:mail,sms,database'],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => [...$required, 'string', 'max:5000'],
            'is_active' => ['boolean'],
        ]);
    }
}
