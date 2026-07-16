<?php

namespace App\Livewire\Billing;

use App\Models\ClientAccount;
use App\Models\Invoice;
use App\Services\BillingService;
use App\Support\EnumHelper;
use Livewire\Component;

class InvoiceForm extends Component
{
    public ?Invoice $invoice = null;

    public array $form = [
        'client_account_id' => '',
        'invoice_date' => '',
        'due_date' => '',
    ];

    public array $items = [
        ['description' => '', 'quantity' => 1, 'unit_price' => 0],
    ];

    public function mount(?Invoice $invoice = null): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        if ($invoice?->exists) {
            abort_unless(EnumHelper::value($invoice->status) === 'draft', 422);
            $this->authorize('update', $invoice);
            $this->invoice = $invoice->load('items');
            $this->form = [
                'client_account_id' => (string) $invoice->client_account_id,
                'invoice_date' => $invoice->invoice_date?->toDateString() ?? today()->toDateString(),
                'due_date' => $invoice->due_date?->toDateString() ?? today()->addDays(14)->toDateString(),
            ];
            $this->items = $invoice->items->map(fn ($item) => [
                'description' => $item->description,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
            ])->values()->all() ?: [['description' => '', 'quantity' => 1, 'unit_price' => 0]];
        } else {
            $this->authorize('create', Invoice::class);
            $this->form['invoice_date'] = today()->toDateString();
            $this->form['due_date'] = today()->addDays(14)->toDateString();
        }
    }

    public function addLineItem(): void
    {
        $this->items[] = ['description' => '', 'quantity' => 1, 'unit_price' => 0];
    }

    public function removeLineItem(int $index): void
    {
        if (count($this->items) <= 1) {
            return;
        }
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(BillingService $billing)
    {
        $data = $this->validate([
            'form.client_account_id' => 'required',
            'form.invoice_date' => 'required|date',
            'form.due_date' => 'nullable|date|after_or_equal:form.invoice_date',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0',
            'items.*.unit_price' => 'required|numeric|min:0',
        ]);

        try {
            if ($this->invoice) {
                $invoice = $billing->updateDraft($this->invoice, $data['form'], $data['items']);
                session()->flash('status', 'Invoice updated.');
            } else {
                $invoice = $billing->create($data['form'], $data['items']);
                session()->flash('status', 'Invoice created.');
            }
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return null;
        }

        return $this->redirect(route('billing.invoices.show', $invoice), navigate: true);
    }

    public function render()
    {
        return view('livewire.billing.invoice-form', [
            'clients' => ClientAccount::orderBy('name')->get(),
            'isEditing' => (bool) $this->invoice,
        ])->layout('layouts.app');
    }
}
