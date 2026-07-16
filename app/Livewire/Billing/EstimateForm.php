<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Estimate;
use App\Services\EstimateService;
use Livewire\Component;

class EstimateForm extends Component
{
    public ?Estimate $estimate = null;

    public array $form = [
        'client_account_id' => '',
        'valid_until' => '',
    ];

    public array $items = [
        ['description' => '', 'quantity' => 1, 'unit_price' => 0, 'is_taxable' => true],
    ];

    public function mount(?Estimate $estimate = null): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        if ($estimate?->exists) {
            abort_unless(in_array($estimate->status, ['draft', 'sent'], true), 422);
            $this->estimate = $estimate->load('items');
            $this->form = [
                'client_account_id' => (string) $estimate->client_account_id,
                'valid_until' => $estimate->valid_until?->toDateString() ?? '',
            ];
            $this->items = $estimate->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'is_taxable' => (bool) $item->is_taxable,
            ])->values()->all() ?: [['description' => '', 'quantity' => 1, 'unit_price' => 0, 'is_taxable' => true]];
        } else {
            $this->form['valid_until'] = now()->addDays(30)->toDateString();
        }
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

    public function save(EstimateService $service)
    {
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'form.valid_until' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            if ($this->estimate) {
                $estimate = $service->update($this->estimate, $data['form'], $data['items']);
                session()->flash('status', 'Estimate updated.');
            } else {
                $estimate = $service->create($data['form'], $data['items']);
                session()->flash('status', 'Estimate created.');
            }
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return null;
        }

        return $this->redirect(route('billing.estimates.show', $estimate), navigate: true);
    }

    public function render()
    {
        return view('livewire.billing.estimate-form', [
            'clients' => ClientAccount::orderBy('name')->get(),
            'isEditing' => (bool) $this->estimate,
        ])->layout('layouts.app');
    }
}
