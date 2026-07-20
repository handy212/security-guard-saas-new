<?php

namespace App\Livewire\Reports;

use App\Models\ClientAccount;
use App\Models\ReportTemplate;
use App\Models\Site;
use App\Services\CustomReportService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class ReportTemplateBuilder extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = ['name' => '', 'description' => '', 'client_account_id' => '', 'is_active' => true];

    public array $fields = [['label' => '', 'field_type' => 'text', 'is_required' => false]];

    public ?int $assignTemplateId = null;

    public ?int $assignSiteId = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->can('reports.approve'), 403);
    }

    public function addField(): void
    {
        $this->fields[] = ['label' => '', 'field_type' => 'text', 'is_required' => false];
    }

    public function removeField(int $index): void
    {
        if (count($this->fields) <= 1) {
            return;
        }

        unset($this->fields[$index]);
        $this->fields = array_values($this->fields);
    }

    public function openCreate(): void
    {
        $this->editingId = null;
        $this->form = ['name' => '', 'description' => '', 'client_account_id' => '', 'is_active' => true];
        $this->fields = [['label' => '', 'field_type' => 'text', 'is_required' => false]];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function closeDrawer(): void
    {
        $this->resetForm();
    }

    public function edit(int $id): void
    {
        $template = ReportTemplate::with('fields')->where('tenant_id', TenantContext::id())->findOrFail($id);
        $this->editingId = $template->id;
        $this->form = [
            'name' => $template->name,
            'description' => $template->description ?? '',
            'client_account_id' => (string) ($template->client_account_id ?? ''),
            'is_active' => (bool) $template->is_active,
        ];
        $this->fields = $template->fields->map(fn ($f) => [
            'label' => $f->label,
            'field_type' => $f->field_type,
            'is_required' => (bool) $f->is_required,
        ])->values()->all() ?: [['label' => '', 'field_type' => 'text', 'is_required' => false]];
        $this->resetErrorBag();
        $this->showForm = true;
    }

    public function save(CustomReportService $service): void
    {
        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'nullable|string',
            'form.client_account_id' => 'nullable',
            'form.is_active' => 'boolean',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string',
            'fields.*.field_type' => 'required|in:text,textarea,photo,checkbox,signature,gps',
        ]);

        $payload = $data['form'] + ['tenant_id' => TenantContext::id()];
        $payload['client_account_id'] = $payload['client_account_id'] ?: null;

        if ($this->editingId) {
            $template = ReportTemplate::where('tenant_id', TenantContext::id())->findOrFail($this->editingId);
            $service->updateTemplate($template, $payload, $data['fields']);
            session()->flash('status', 'Report template updated.');
        } else {
            $service->createTemplate($payload, $data['fields']);
            session()->flash('status', 'Report template created.');
        }

        $this->resetForm();
    }

    public function delete(int $id, CustomReportService $service): void
    {
        $template = ReportTemplate::where('tenant_id', TenantContext::id())->findOrFail($id);
        $service->deleteTemplate($template);
        if ($this->editingId === $id) {
            $this->resetForm();
        }
        session()->flash('status', 'Report template deleted.');
    }

    public function assignToSite(CustomReportService $service): void
    {
        $this->validate(['assignTemplateId' => 'required', 'assignSiteId' => 'required']);
        $service->assignToSite($this->assignTemplateId, $this->assignSiteId);
        session()->flash('status', 'Template assigned to site.');
    }

    public function resetForm(): void
    {
        $this->showForm = false;
        $this->editingId = null;
        $this->form = ['name' => '', 'description' => '', 'client_account_id' => '', 'is_active' => true];
        $this->fields = [['label' => '', 'field_type' => 'text', 'is_required' => false]];
    }

    public function render()
    {
        return view('livewire.reports.report-template-builder', [
            'templates' => ReportTemplate::with(['fields', 'assignments.site'])->where('tenant_id', TenantContext::id())->latest()->paginate(15),
            'clients' => ClientAccount::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
