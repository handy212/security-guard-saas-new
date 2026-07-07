<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Estimate;
use App\Services\EstimateService;
use App\Support\TenantContext;
use Livewire\Component;
use Livewire\WithPagination;

class EstimateIndex extends Component
{
    use WithPagination;

    public bool $showForm = false;

    public array $form = ['client_account_id' => '', 'valid_until' => ''];

    public array $items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'is_taxable' => true]];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->form['valid_until'] = now()->addDays(30)->toDateString();
    }

    public function addLineItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'is_taxable' => true];
    }

    public function removeLineItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }

        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(EstimateService $service): void
    {
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'form.valid_until' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $service->create($data['form'], $data['items']);
        $this->showForm = false;
        $this->items = [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'is_taxable' => true]];
        session()->flash('status', 'Estimate created.');
    }

    public function accept(Estimate $estimate, EstimateService $service): void
    {
        $service->accept($estimate);
        session()->flash('status', 'Estimate accepted.');
    }

    public function convert(Estimate $estimate, EstimateService $service): void
    {
        $invoice = $service->convertToInvoice($estimate);
        session()->flash('status', "Converted to invoice {$invoice->invoice_number}.");
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $all = Estimate::where('tenant_id', $tenantId);

        return view('livewire.billing.estimate-index', [
            'estimates' => Estimate::with('clientAccount')->where('tenant_id', $tenantId)->latest()->paginate(20),
            'clients' => ClientAccount::orderBy('name')->get(),
            'stats' => [
                'total' => $all->count(),
                'open' => (clone $all)->whereIn('status', ['draft', 'sent'])->count(),
                'accepted' => (clone $all)->where('status', 'accepted')->count(),
            ],
        ])->layout('layouts.app');
    }
}
