<?php

namespace App\Livewire\Billing;

use App\Models\Estimate;
use App\Services\EstimateDeliveryService;
use App\Services\EstimateService;
use App\Services\PdfExportService;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EstimateShow extends Component
{
    public Estimate $estimate;

    public string $listSearch = '';

    public bool $showSend = false;

    public string $sendEmails = '';

    public string $sendMessage = '';

    public function mount(Estimate $estimate): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->estimate = $estimate->load(['clientAccount', 'items', 'convertedInvoice']);
    }

    public function openSend(EstimateDeliveryService $delivery): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        abort_unless(in_array($this->estimate->status, ['draft', 'sent'], true), 422);
        $this->estimate->loadMissing('clientAccount.contacts');
        $this->showSend = true;
        $this->sendEmails = implode(', ', $delivery->defaultRecipients($this->estimate));
        $this->sendMessage = '';
    }

    public function closeSend(): void
    {
        $this->showSend = false;
        $this->sendEmails = '';
        $this->sendMessage = '';
    }

    public function sendEstimate(EstimateDeliveryService $delivery): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        $data = $this->validate([
            'sendEmails' => 'required|string|max:1000',
            'sendMessage' => 'nullable|string|max:2000',
        ]);

        $recipients = preg_split('/[\s,;]+/', $data['sendEmails'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($recipients as $email) {
            abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 422, "Invalid email: {$email}");
        }

        $count = $delivery->send($this->estimate, $recipients, $data['sendMessage'] ?: null);
        $this->estimate->refresh()->load(['clientAccount', 'items', 'convertedInvoice']);
        $this->closeSend();
        session()->flash('status', "Estimate emailed to {$count} recipient".($count === 1 ? '' : 's').'.');
    }

    public function markSent(EstimateService $service): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        try {
            $service->send($this->estimate);
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return;
        }

        $this->estimate->refresh()->load(['clientAccount', 'items', 'convertedInvoice']);
        session()->flash('status', 'Estimate marked as sent.');
    }

    public function accept(EstimateService $service): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $service->accept($this->estimate);
        $this->estimate->refresh()->load(['clientAccount', 'items', 'convertedInvoice']);
        session()->flash('status', 'Estimate accepted.');
    }

    public function convert(EstimateService $service)
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $invoice = $service->convertToInvoice($this->estimate);
        session()->flash('status', "Converted to invoice {$invoice->invoice_number}.");

        return $this->redirect(route('billing.invoices.show', $invoice), navigate: true);
    }

    public function delete(EstimateService $service)
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);

        try {
            $service->delete($this->estimate);
        } catch (\RuntimeException $e) {
            session()->flash('status', $e->getMessage());

            return null;
        }

        session()->flash('status', 'Estimate deleted.');

        return $this->redirect(route('billing.estimates'), navigate: true);
    }

    public function exportPdf(PdfExportService $pdf): StreamedResponse
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $path = $pdf->exportEstimate($this->estimate);

        return Storage::download($path, $this->estimate->estimate_number.'.pdf');
    }

    public function render()
    {
        $siblings = Estimate::with('clientAccount')
            ->where('tenant_id', TenantContext::id())
            ->when($this->listSearch !== '', function ($q) {
                $needle = '%'.$this->listSearch.'%';
                $q->where(function ($q) use ($needle) {
                    $q->where('estimate_number', 'like', $needle)
                        ->orWhereHas('clientAccount', fn ($c) => $c->where('name', 'like', $needle));
                });
            })
            ->latest()
            ->limit(40)
            ->get();

        return view('livewire.billing.estimate-show', [
            'siblings' => $siblings,
        ])->layout('layouts.app');
    }
}
