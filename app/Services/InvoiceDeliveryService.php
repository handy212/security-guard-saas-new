<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class InvoiceDeliveryService
{
    public function __construct(private PdfExportService $pdf)
    {
    }

    /**
     * @param  list<string>  $recipients
     */
    public function send(Invoice $invoice, array $recipients, ?string $message = null): int
    {
        $invoice->loadMissing(['clientAccount.tenant', 'items']);

        $recipients = array_values(array_unique(array_filter(array_map(
            fn ($email) => strtolower(trim((string) $email)),
            $recipients
        ))));

        abort_if($recipients === [], 422, 'At least one recipient email is required.');

        $path = $this->pdf->exportInvoice($invoice);
        $absolute = Storage::path($path);
        $company = $invoice->clientAccount?->tenant?->name ?? config('app.name');
        $clientName = $invoice->clientAccount?->name ?? 'Client';
        $subject = "Invoice {$invoice->invoice_number} — {$clientName}";
        $body = implode("\n", array_filter([
            'Hello,',
            '',
            "Please find invoice {$invoice->invoice_number} attached.",
            "Client: {$clientName}",
            'Amount due: ₦'.number_format(max(0, (float) $invoice->grand_total - (float) ($invoice->amount_paid ?? 0)), 2),
            'Due date: '.($invoice->due_date?->format('M j, Y') ?? '—'),
            $message ? '' : null,
            $message ?: null,
            '',
            'You can also view invoices in the client portal.',
            '',
            '— '.$company,
        ]));

        foreach ($recipients as $email) {
            Mail::raw($body, function ($mail) use ($email, $subject, $absolute, $invoice) {
                $mail->to($email)
                    ->subject($subject)
                    ->attach($absolute, [
                        'as' => $invoice->invoice_number.'.pdf',
                        'mime' => 'application/pdf',
                    ]);
            });
        }

        $updates = ['sent_at' => $invoice->sent_at ?? now()];
        if ($invoice->status === 'draft') {
            $updates['status'] = 'sent';
        }
        $invoice->update($updates);

        return count($recipients);
    }

    /**
     * @return list<string>
     */
    public function defaultRecipients(Invoice $invoice): array
    {
        $invoice->loadMissing(['clientAccount.contacts']);

        $emails = collect([
            $invoice->clientAccount?->email,
        ])
            ->merge($invoice->clientAccount?->contacts?->pluck('email') ?? [])
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values()
            ->all();

        return $emails;
    }
}
