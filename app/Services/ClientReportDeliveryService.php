<?php

namespace App\Services;

use App\Models\ClientReportSchedule;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ClientReportDeliveryService
{
    public function __construct(private ClientCommunicationService $communication) {}

    public function deliverDueSchedules(): int
    {
        $delivered = 0;

        ClientReportSchedule::query()
            ->where('is_active', true)
            ->with(['clientAccount.tenant'])
            ->eachById(function (ClientReportSchedule $schedule) use (&$delivered) {
                if (! $this->isDue($schedule)) {
                    return;
                }

                $client = $schedule->clientAccount;
                if ($client?->tenant_id !== $schedule->tenant_id || $client->tenant?->status !== 'active') {
                    return;
                }

                $reportLabel = config('client_profile.report_types')[$schedule->report_type] ?? $schedule->report_type;
                if ($schedule->delivery_channel === 'whatsapp') {
                    if (! ClientCommunicationService::whatsappEnabled($schedule->tenant_id)) {
                        return;
                    }
                    try {
                        $this->communication->sendScheduledReport($schedule, $reportLabel);
                    } catch (ValidationException) {
                        logger()->warning('Scheduled client WhatsApp reminder was not confirmed.', [
                            'tenant_id' => $schedule->tenant_id, 'schedule_id' => $schedule->id,
                        ]);

                        return;
                    }
                    $schedule->update(['last_sent_at' => now()]);
                    $delivered++;

                    return;
                }

                $recipients = array_filter($schedule->recipients ?? []);
                if ($recipients === []) {
                    return;
                }
                $company = $client->tenant->name;

                $subject = "{$reportLabel} — {$client?->name}";
                $body = implode("\n", [
                    'Hello,',
                    '',
                    "Your scheduled {$reportLabel} for {$client?->name} is ready.",
                    'Frequency: '.ucfirst($schedule->frequency),
                    "Prepared by: {$company}",
                    '',
                    'Log in to the client portal for full details and attachments.',
                    '',
                    '— '.$company,
                ]);

                foreach ($recipients as $email) {
                    Mail::raw($body, fn ($message) => $message->to($email)->subject($subject));
                }

                $schedule->update(['last_sent_at' => now()]);
                $delivered++;
            });

        return $delivered;
    }

    private function isDue(ClientReportSchedule $schedule): bool
    {
        if ($schedule->last_sent_at === null) {
            return true;
        }

        return match ($schedule->frequency) {
            'daily' => $schedule->last_sent_at->lt(now()->startOfDay()),
            'weekly' => $schedule->last_sent_at->lt(now()->subWeek()),
            'monthly' => $schedule->last_sent_at->lt(now()->subMonth()),
            default => false,
        };
    }
}
