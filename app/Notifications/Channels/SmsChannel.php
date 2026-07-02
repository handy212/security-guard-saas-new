<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! config('notifications.sms.enabled')) {
            return;
        }

        $phone = $notifiable->phone ?? null;
        if (! $phone) {
            return;
        }

        $message = $notification->toSms($notifiable);
        if (! $message) {
            return;
        }

        $sid = config('notifications.sms.twilio.sid');
        $token = config('notifications.sms.twilio.token');
        $from = config('notifications.sms.twilio.from');

        if (! $sid || ! $token || ! $from) {
            Log::debug('SMS skipped: Twilio not configured', ['user_id' => $notifiable->id ?? null]);

            return;
        }

        try {
            Http::withBasicAuth($sid, $token)
                ->asForm()
                ->post("https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json", [
                    'From' => $from,
                    'To' => $phone,
                    'Body' => $message,
                ]);
        } catch (\Throwable $e) {
            Log::warning('SMS notification failed', ['error' => $e->getMessage()]);
        }
    }
}
