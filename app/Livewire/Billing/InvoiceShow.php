<?php

namespace App\Livewire\Billing;

use App\Models\Invoice;
use App\Services\EstimateService;
use App\Services\InvoiceDeliveryService;
use App\Services\PdfExportService;
use App\Support\EnumHelper;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceShow extends Component
{
    public Invoice $invoice;

    public string $listSearch = '';

    public bool $showSend = false;

    public string $sendEmails = '';

    public string $sendMessage = '';

    public bool $showPayment = false;

    public array $paymentForm = [
        'amount' => '',
        'payment_method' => 'cash',
        'notes' => '',
    ];

    public function mount(Invoice $invoice): void
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        $this->authorize('view', $invoice);
        $this->invoice = $invoice->load(['clientAccount', 'items', 'payments']);
    }

    public function markSent(): void
    {
        $this->authorize('update', $this->invoice);
        abort_unless(EnumHelper::value($this->invoice->status) === 'draft', 422);
        $this->invoice->update(['status' => 'sent', 'sent_at' => now()]);
        $this->invoice->refresh();
        session()->flash('status', 'Invoice marked as sent.');
    }

    public function openSend(InvoiceDeliveryService $delivery): void
    {
        $this->authorize('update', $this->invoice);
        abort_unless(in_array(EnumHelper::value($this->invoice->status), ['draft', 'sent', 'partial', 'overdue'], true), 422);
        $this->invoice->loadMissing('clientAccount.contacts');
        $this->showSend = true;
        $this->sendEmails = implode(', ', $delivery->defaultRecipients($this->invoice));
        $this->sendMessage = '';
    }

    public function closeSend(): void
    {
        $this->showSend = false;
        $this->sendEmails = '';
        $this->sendMessage = '';
    }

    public function sendInvoice(InvoiceDeliveryService $delivery): void
    {
        $this->authorize('update', $this->invoice);

        $data = $this->validate([
            'sendEmails' => 'required|string|max:1000',
            'sendMessage' => 'nullable|string|max:2000',
        ]);

        $recipients = preg_split('/[\s,;]+/', $data['sendEmails'], -1, PREG_SPLIT_NO_EMPTY) ?: [];
        foreach ($recipients as $email) {
            abort_unless(filter_var($email, FILTER_VALIDATE_EMAIL), 422, "Invalid email: {$email}");
        }

        $count = $delivery->send($this->invoice, $recipients, $data['sendMessage'] ?: null);
        $this->invoice->refresh()->load(['clientAccount', 'items', 'payments']);
        $this->closeSend();
        session()->flash('status', "Invoice emailed to {$count} recipient".($count === 1 ? '' : 's').'.');
    }

    public function openPayment(): void
    {
        $this->authorize('update', $this->invoice);
        $status = EnumHelper::value($this->invoice->status);
        abort_if(in_array($status, ['paid', 'void'], true), 422);
        $remaining = max(0, (float) $this->invoice->grand_total - (float) ($this->invoice->amount_paid ?? 0));
        $this->showPayment = true;
        $this->paymentForm = [
            'amount' => number_format($remaining, 2, '.', ''),
            'payment_method' => 'cash',
            'notes' => '',
        ];
    }

    public function closePayment(): void
    {
        $this->showPayment = false;
        $this->paymentForm = ['amount' => '', 'payment_method' => 'cash', 'notes' => ''];
    }

    public function recordPayment(EstimateService $service): void
    {
        $this->authorize('update', $this->invoice);

        $data = $this->validate([
            'paymentForm.amount' => 'required|numeric|min:0.01',
            'paymentForm.payment_method' => 'required|string|max:50',
            'paymentForm.notes' => 'nullable|string|max:500',
        ])['paymentForm'];

        $remaining = max(0, (float) $this->invoice->grand_total - (float) ($this->invoice->amount_paid ?? 0));
        abort_if((float) $data['amount'] > $remaining + 0.001, 422, 'Amount exceeds remaining balance.');

        $service->recordPayment(
            $this->invoice,
            (float) $data['amount'],
            $data['payment_method'],
            $data['notes'] ?: null,
        );

        $this->invoice->refresh()->load(['clientAccount', 'items', 'payments']);
        $this->closePayment();
        session()->flash('status', 'Payment recorded.');
    }

    public function exportPdf(PdfExportService $pdf): StreamedResponse
    {
        $this->authorize('view', $this->invoice);
        $path = $pdf->exportInvoice($this->invoice);

        return Storage::download($path, $this->invoice->invoice_number.'.pdf');
    }

    public function render()
    {
        $tenantId = TenantContext::id();
        $siblings = Invoice::with('clientAccount')
            ->where('tenant_id', $tenantId)
            ->when($this->listSearch !== '', function ($q) {
                $needle = '%'.$this->listSearch.'%';
                $q->where(function ($q) use ($needle) {
                    $q->where('invoice_number', 'like', $needle)
                        ->orWhereHas('clientAccount', fn ($c) => $c->where('name', 'like', $needle));
                });
            })
            ->latest()
            ->limit(40)
            ->get();

        return view('livewire.billing.invoice-show', [
            'siblings' => $siblings,
            'balance' => max(0, (float) $this->invoice->grand_total - (float) ($this->invoice->amount_paid ?? 0)),
            'status' => EnumHelper::value($this->invoice->status),
        ])->layout('layouts.app');
    }
}
