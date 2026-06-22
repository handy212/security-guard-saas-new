<?php

namespace App\Livewire\Billing;

use App\Models\{ClientAccount, Invoice};
use App\Services\BillingService;
use Livewire\Component;

class InvoiceIndex extends Component
{
    public string $month; public ?int $clientId=null;
    public function mount(): void { $this->month = now()->format('Y-m'); }
    public function generate(BillingService $service): void { $service->generateMonthlyInvoice(ClientAccount::findOrFail($this->clientId), $this->month); }
    public function markSent(Invoice $invoice): void { $invoice->update(['status'=>'sent','sent_at'=>now()]); }
    public function render(){ return view('livewire.billing.invoice-index',['invoices'=>Invoice::with('clientAccount')->latest()->get(),'clients'=>ClientAccount::orderBy('name')->get()])->layout('layouts.app'); }
}
