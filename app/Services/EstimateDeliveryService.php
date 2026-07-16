<?php

namespace App\Services;

use App\Models\Estimate;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class EstimateDeliveryService
{
    public function __construct(private PdfExportService $pdf)
    {
    }

    /**
     * @param  list<string>  $recipients
     */
    public function send(Estimate $estimate, array $recipients, ?string $message = null): int
    {
        $estimate->loadMissing(['clientAccount.tenant', 'items']);

        $recipients = array_values(array_unique(array_filter(array_map(
            fn ($email) => strtolower(trim((string) $email)),
            $recipients
        ))));

        abort_if($recipients === [], 422, 'At least one recipient email is required.');
        abort_unless(in_array($estimate->status, ['draft', 'sent'], true), 422, 'Only draft or sent estimates can be emailed.');

        $path = $this->pdf->exportEstimate($estimate);
        $absolute = Storage::path($path);
        $company = $estimate->clientAccount?->tenant?->name ?? config('app.name');
        $clientName = $estimate->clientAccount?->name ?? 'Client';
        $subject = "Estimate {$estimate->estimate_number} — {$clientName}";
        $body = implode("\n", array_filter([
            'Hello,',
            '',
            "Please find estimate {$estimate->estimate_number} attached.",
            "Client: {$clientName}",
            'Total: ₦'.number_format((float) $estimate->grand_total, 2),
            'Valid until: '.($estimate->valid_until?->format('M j, Y') ?? '—'),
            $message ? '' : null,
            $message ?: null,
            '',
            '— '.$company,
        ]));

        foreach ($recipients as $email) {
            Mail::raw($body, function ($mail) use ($email, $subject, $absolute, $estimate) {
                $mail->to($email)
                    ->subject($subject)
                    ->attach($absolute, [
                        'as' => $estimate->estimate_number.'.pdf',
                        'mime' => 'application/pdf',
                    ]);
            });
        }

        $updates = ['sent_at' => $estimate->sent_at ?? now()];
        if ($estimate->status === 'draft') {
            $updates['status'] = 'sent';
        }
        $estimate->update($updates);

        return count($recipients);
    }

    /**
     * @return list<string>
     */
    public function defaultRecipients(Estimate $estimate): array
    {
        $estimate->loadMissing(['clientAccount.contacts']);

        return collect([
            $estimate->clientAccount?->email,
        ])
            ->merge($estimate->clientAccount?->contacts?->pluck('email') ?? [])
            ->filter()
            ->map(fn ($email) => strtolower(trim((string) $email)))
            ->unique()
            ->values()
            ->all();
    }
}
