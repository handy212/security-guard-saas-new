<?php

namespace App\Http\Controllers\Api\Admin;

use App\Models\ReportTemplate;
use App\Services\CustomReportService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportTemplateController extends AdminController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', ReportTemplate::class);

        $query = ReportTemplate::with(['fields', 'assignments.site'])
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search').'%'))
            ->latest();

        return $this->paginated($query->paginate($this->perPage($request)));
    }

    public function store(Request $request, CustomReportService $service): JsonResponse
    {
        $this->authorize('create', ReportTemplate::class);

        [$form, $fields] = $this->validatedTemplate($request);
        $template = $service->createTemplate($form + ['tenant_id' => TenantContext::id()], $fields);

        return $this->data($template, 201);
    }

    public function show(ReportTemplate $reportTemplate): JsonResponse
    {
        $this->authorize('view', $reportTemplate);

        return $this->data($reportTemplate->load(['fields', 'assignments.site']));
    }

    public function update(Request $request, ReportTemplate $reportTemplate, CustomReportService $service): JsonResponse
    {
        $this->authorize('update', $reportTemplate);

        [$form, $fields] = $this->validatedTemplate($request, partial: true);
        if (! $request->has('fields')) {
            $fields = $reportTemplate->fields->map(fn ($field) => [
                'label' => $field->label,
                'field_type' => $field->field_type,
                'is_required' => (bool) $field->is_required,
            ])->values()->all();
        }
        $template = $service->updateTemplate($reportTemplate, $form, $fields);

        return $this->data($template);
    }

    public function destroy(ReportTemplate $reportTemplate, CustomReportService $service): JsonResponse
    {
        $this->authorize('delete', $reportTemplate);
        $service->deleteTemplate($reportTemplate);

        return $this->noContent();
    }

    private function validatedTemplate(Request $request, bool $partial = false): array
    {
        $required = $partial ? ['sometimes', 'required'] : ['required'];

        $form = $request->validate([
            'name' => [...$required, 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'client_account_id' => ['nullable', 'integer', TenantValidation::exists('client_accounts')],
            'is_active' => ['boolean'],
        ]);

        $fields = $request->validate([
            'fields' => [...$required, 'array', 'min:1'],
            'fields.*.label' => ['required_with:fields', 'string'],
            'fields.*.field_type' => ['required_with:fields', 'in:text,textarea,photo,checkbox,signature,gps'],
            'fields.*.is_required' => ['nullable', 'boolean'],
        ])['fields'] ?? [];

        return [$form, $fields];
    }
}
