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

    public function save(CustomReportService $service): void
    {
        $data = $this->validate([
            'form.name' => 'required|string|max:255',
            'form.description' => 'nullable|string',
            'form.client_account_id' => 'nullable',
            'fields' => 'required|array|min:1',
            'fields.*.label' => 'required|string',
            'fields.*.field_type' => 'required|in:text,textarea,photo,checkbox,signature,gps',
        ]);

        $service->createTemplate(
            $data['form'] + ['tenant_id' => TenantContext::id(), 'is_active' => true],
            $data['fields'],
        );

        $this->resetForm();
        session()->flash('status', 'Report template created.');
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
