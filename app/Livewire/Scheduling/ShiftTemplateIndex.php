<?php

namespace App\Livewire\Scheduling;

use App\Livewire\Concerns\HasFormDrawer;
use App\Models\ShiftTemplate;
use App\Models\Site;
use App\Services\WorkforceService;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Carbon\Carbon;
use Livewire\Component;

class ShiftTemplateIndex extends Component
{
    use HasFormDrawer;

    public ?int $editingTemplateId = null;

    public array $form = ['name' => '', 'description' => '', 'is_active' => true];

    public array $items = [['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => '']];

    public ?int $applyTemplateId = null;

    public string $weekStart = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->weekStart = now()->startOfWeek(Carbon::SUNDAY)->toDateString();
    }

    public function openForm(): void
    {
        $this->editingTemplateId = null;
        $this->form = ['name' => '', 'description' => '', 'is_active' => true];
        $this->items = [['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => '']];
        $this->showForm = true;
    }

    public function editTemplate(int $templateId): void
    {
        $template = ShiftTemplate::with('items')->where('tenant_id', TenantContext::id())->findOrFail($templateId);
        $this->editingTemplateId = $template->id;
        $this->form = [
            'name' => $template->name,
            'description' => $template->description ?? '',
            'is_active' => (bool) $template->is_active,
        ];
        $this->items = $template->items->map(fn ($item) => [
            'day_of_week' => $item->day_of_week,
            'start_time' => substr((string) $item->start_time, 0, 5),
            'end_time' => substr((string) $item->end_time, 0, 5),
            'site_id' => (string) $item->site_id,
            'required_guards' => $item->required_guards,
            'billing_rate' => $item->billing_rate ?? '',
        ])->all() ?: [['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => '']];
        $this->showForm = true;
    }

    public function addItem(): void
    {
        $this->items[] = ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => ''];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => 'required',
            'form.description' => 'nullable',
            'form.is_active' => 'boolean',
            'items' => 'required|array|min:1',
            'items.*.site_id' => ['required', TenantValidation::exists('sites')],
            'items.*.day_of_week' => 'required|integer|min:0|max:6',
            'items.*.start_time' => 'required|date_format:H:i',
            'items.*.end_time' => 'required|date_format:H:i',
            'items.*.required_guards' => 'required|integer|min:1',
            'items.*.billing_rate' => 'nullable|numeric|min:0',
        ]);

        if ($this->editingTemplateId) {
            $template = ShiftTemplate::where('tenant_id', TenantContext::id())->findOrFail($this->editingTemplateId);
            $template->update($data['form']);
            $template->items()->delete();
        } else {
            $template = ShiftTemplate::create($data['form'] + ['tenant_id' => TenantContext::id()]);
        }

        foreach ($data['items'] as $item) {
            $template->items()->create([
                'day_of_week' => $item['day_of_week'],
                'start_time' => $item['start_time'],
                'end_time' => $item['end_time'],
                'site_id' => $item['site_id'],
                'required_guards' => $item['required_guards'],
                'billing_rate' => filled($item['billing_rate'] ?? null) ? $item['billing_rate'] : null,
            ]);
        }

        $this->showForm = false;
        $this->editingTemplateId = null;
        $this->form = ['name' => '', 'description' => '', 'is_active' => true];
        $this->items = [['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => '']];
        session()->flash('status', 'Shift template saved.');
    }

    public function toggleActive(int $templateId): void
    {
        $template = ShiftTemplate::where('tenant_id', TenantContext::id())->findOrFail($templateId);
        $template->update(['is_active' => ! $template->is_active]);
        session()->flash('status', $template->is_active ? 'Template activated.' : 'Template deactivated.');
    }

    public function deleteTemplate(int $templateId): void
    {
        ShiftTemplate::where('tenant_id', TenantContext::id())->whereKey($templateId)->delete();
        session()->flash('status', 'Template deleted.');
    }

    public function apply(WorkforceService $service): void
    {
        $data = $this->validate([
            'applyTemplateId' => ['required', TenantValidation::exists('shift_templates')],
            'weekStart' => 'required|date',
        ]);

        $template = ShiftTemplate::with('items')
            ->where('tenant_id', TenantContext::id())
            ->findOrFail($data['applyTemplateId']);

        abort_if($template->items->isEmpty(), 422, 'Template has no shift patterns.');
        abort_unless($template->is_active, 422, 'This template is inactive. Activate it before applying.');

        $weekStart = Carbon::parse($data['weekStart'])->startOfWeek(Carbon::SUNDAY);
        $count = $service->applyTemplate($template, $weekStart);
        session()->flash('status', "{$count} shifts created from template.");

        $this->redirect(route('schedules.index', ['date' => $weekStart->toDateString()]), navigate: true);
    }

    public function render()
    {
        return view('livewire.scheduling.shift-template-index', [
            'templates' => ShiftTemplate::with('items.site')->where('tenant_id', TenantContext::id())->latest()->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
