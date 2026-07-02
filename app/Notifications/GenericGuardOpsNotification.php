<?php

namespace App\Notifications;

use App\Notifications\Channels\PushChannel;
use App\Notifications\Channels\SmsChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class GenericGuardOpsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $subject,
        public string $body,
        public ?string $actionUrl = '/dashboard',
        public ?string $templateCode = null,
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['mail', 'database'];

        if (config('notifications.push.enabled')) {
            $channels[] = PushChannel::class;
        }

        if ($this->shouldSendSms()) {
            $channels[] = SmsChannel::class;
        }

        return $channels;
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->subject,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
            'type' => 'system',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->subject)
            ->line($this->body);

        if ($this->actionUrl) {
            $mail->action('View in GuardOps', url($this->actionUrl));
        }

        return $mail;
    }

    public function toPush(object $notifiable): array
    {
        return [
            'title' => $this->subject,
            'body' => $this->body,
            'action_url' => $this->actionUrl,
        ];
    }

    public function toSms(object $notifiable): ?string
    {
        return $this->subject.': '.$this->body;
    }

    private function shouldSendSms(): bool
    {
        if (! config('notifications.sms.enabled') || ! $this->templateCode) {
            return false;
        }

        return in_array($this->templateCode, config('notifications.sms.templates_requiring_sms', []), true);
    }
}
