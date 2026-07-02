<?php

namespace App\Livewire\Scheduling;

use App\Models\ShiftTemplate;
use App\Models\Site;
use App\Services\WorkforceService;
use App\Support\TenantContext;
use Carbon\Carbon;
use Livewire\Component;

class ShiftTemplateIndex extends Component
{
    public bool $showForm = false;

    public array $form = ['name' => '', 'description' => ''];

    public array $items = [['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => '']];

    public ?int $applyTemplateId = null;

    public string $weekStart = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->can('schedules.manage'), 403);
        $this->weekStart = now()->startOfWeek()->toDateString();
    }

    public function addItem(): void
    {
        $this->items[] = ['day_of_week' => 1, 'start_time' => '08:00', 'end_time' => '16:00', 'site_id' => '', 'required_guards' => 1, 'billing_rate' => ''];
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => 'required',
            'items' => 'required|array|min:1',
            'items.*.site_id' => 'required',
            'items.*.day_of_week' => 'required|integer|min:0|max:6',
        ]);

        $template = ShiftTemplate::create($data['form'] + ['tenant_id' => TenantContext::id(), 'is_active' => true]);
        foreach ($data['items'] as $item) {
            $template->items()->create($item);
        }

        $this->showForm = false;
        session()->flash('status', 'Shift template saved.');
    }

    public function apply(WorkforceService $service): void
    {
        $template = ShiftTemplate::with('items')->findOrFail($this->applyTemplateId);
        $count = $service->applyTemplate($template, Carbon::parse($this->weekStart));
        session()->flash('status', "{$count} shifts created from template.");
    }

    public function render()
    {
        return view('livewire.scheduling.shift-template-index', [
            'templates' => ShiftTemplate::with('items.site')->where('tenant_id', TenantContext::id())->latest()->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
