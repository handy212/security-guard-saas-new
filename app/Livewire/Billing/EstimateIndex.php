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

    public function save(EstimateService $service): void
    {
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        $service->create($data['form'], $data['items']);
        $this->showForm = false;
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
        return view('livewire.billing.estimate-index', [
            'estimates' => Estimate::with('clientAccount')->where('tenant_id', TenantContext::id())->latest()->paginate(20),
            'clients' => ClientAccount::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
