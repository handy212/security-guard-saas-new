<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\ClientAccount;
use App\Models\ClientReportSchedule;
use App\Models\Estimate;
use App\Models\GuardSiteAssignment;
use App\Models\Invoice;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ClientCommunicationService
{
    public function __construct(private PdfExportService $pdf, private AuditLogService $audit) {}

    public static function whatsappEnabled(int $tenantId): bool
    {
        return $tenantId === (int) config('services.evolution.tenant_id')
            && filled(config('services.evolution.url'))
            && filled(config('services.evolution.instance'))
            && filled(config('services.evolution.key'));
    }

    public function sendPosting(GuardSiteAssignment $assignment, string $channel): void
    {
        $assignment->loadMissing(['site.clientAccount', 'assignedGuard']);
        $site = $assignment->site;
        $guard = $assignment->assignedGuard;
        abort_unless($site?->tenant_id === $assignment->tenant_id && $guard?->tenant_id === $assignment->tenant_id, 404);
        $client = $this->client($site->clientAccount, $assignment->tenant_id);
        $role = filled($guard->rank) ? " ({$guard->rank})" : '';
        $message = "Hello {$client->name}, {$guard->full_name}{$role} has been assigned to {$site->name}.";

        if ($channel === 'email') {
            $email = filter_var($client->email, FILTER_VALIDATE_EMAIL);
            abort_unless($email, 422, 'Add a valid email to the client account.');
            Mail::raw($message, fn ($mail) => $mail->to($email)->subject('Guard posting update'));
            $reference = null;
        } else {
            abort_unless($channel === 'whatsapp', 422);
            $reference = $this->sendWhatsApp($client, 'sendText', ['text' => $message]);
        }

        $this->audit->record('client.posting_submitted', $assignment, ['channel' => $channel, 'provider_reference' => $reference]);
    }

    public function sendDocument(Invoice|Estimate $document): void
    {
        $invoice = $document instanceof Invoice;
        abort_unless(in_array($document->status, $invoice ? ['draft', 'sent', 'partial', 'overdue'] : ['draft', 'sent'], true), 422);
        $client = $this->client($document->clientAccount, $document->tenant_id);
        $this->whatsappNumber($client);
        $reference = $invoice ? $document->invoice_number : $document->estimate_number;
        $label = $invoice ? 'Invoice' : 'Estimate';
        $draft = $document->status === 'draft';
        if ($draft) {
            $document->issuer_profile = $document->documentIssuer();
        }

        $path = $invoice ? $this->pdf->exportInvoice($document) : $this->pdf->exportEstimate($document);
        $pdf = Storage::get($path);
        abort_unless(is_string($pdf) && $pdf !== '', 422, 'The billing PDF could not be loaded.');

        $providerReference = $this->sendWhatsApp($client, 'sendMedia', [
            'mediatype' => 'document',
            'mimetype' => 'application/pdf',
            'fileName' => $reference.'.pdf',
            'caption' => "Hello {$client->name}, please find {$label} {$reference} attached.",
            'media' => base64_encode($pdf),
        ]);

        DB::transaction(function () use ($document, $draft): void {
            $current = $document->newQuery()->where('tenant_id', $document->tenant_id)->lockForUpdate()->findOrFail($document->id);
            if ($draft && $current->status === 'draft') {
                $current->status = 'sent';
                $current->issuer_profile = $document->issuer_profile;
            }
            $current->sent_at ??= now();
            $current->save();
        }, attempts: 3);
        $this->audit->record('client.billing_whatsapp_submitted', $document, ['provider_reference' => $providerReference]);
    }

    public function sendScheduledReport(ClientReportSchedule $schedule, string $reportLabel): void
    {
        $client = $this->client($schedule->clientAccount, $schedule->tenant_id);
        if ($schedule->whatsapp_phone !== $this->whatsappNumber($client)) {
            throw ValidationException::withMessages(['clientCommunication' => 'The client phone number changed. Create a new WhatsApp schedule.']);
        }

        $reference = $this->sendWhatsApp($client, 'sendText', [
            'text' => "Hello {$client->name}, this is your scheduled {$reportLabel} reminder. Sign in to the client portal for report updates.",
        ]);
        AuditLog::create([
            'tenant_id' => $schedule->tenant_id,
            'action' => 'client.report_whatsapp_submitted',
            'auditable_type' => ClientReportSchedule::class,
            'auditable_id' => $schedule->id,
            'metadata' => ['provider_reference' => $reference],
        ]);
    }

    private function client(?ClientAccount $client, int $tenantId): ClientAccount
    {
        abort_unless($client?->tenant_id === $tenantId, 404);

        return $client;
    }

    public function whatsappNumber(ClientAccount $client): string
    {
        abort_unless(self::whatsappEnabled($client->tenant_id), 404);
        $phone = preg_replace('/[\s().-]/', '', (string) $client->phone);
        if (! preg_match('/^\+[1-9][0-9]{7,14}$/D', $phone)) {
            throw ValidationException::withMessages(['clientCommunication' => 'Use the client account phone number in international format, starting with + and the country code.']);
        }

        return substr($phone, 1);
    }

    private function sendWhatsApp(ClientAccount $client, string $operation, array $payload): string
    {
        $number = $this->whatsappNumber($client);
        $url = rtrim(config('services.evolution.url'), '/').'/message/'.$operation.'/'.rawurlencode(config('services.evolution.instance'));

        try {
            $response = Http::acceptJson()->withHeaders(['apikey' => config('services.evolution.key')])
                ->withoutRedirecting()->connectTimeout(5)->timeout(20)->post($url, ['number' => $number] + $payload);
        } catch (ConnectionException) {
            throw ValidationException::withMessages(['clientCommunication' => 'WhatsApp did not confirm the send. Check the conversation before trying again.']);
        }

        $id = $response->json('key.id');
        if (! $response->successful() || ! is_string($id) || $id === '') {
            throw ValidationException::withMessages(['clientCommunication' => 'Evolution API did not confirm the WhatsApp send. Check the instance and client number.']);
        }

        return $id;
    }
}
