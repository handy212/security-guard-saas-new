<?php

namespace App\Notifications;

use App\Models\SosAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SosAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public SosAlert $alert) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('SOS ALERT — immediate attention required')
            ->line($this->alert->message ?? 'A guard has raised an SOS alert.')
            ->line('Location: '.$this->alert->latitude.', '.$this->alert->longitude)
            ->action('Open control room', url('/dispatch'));
    }
}
