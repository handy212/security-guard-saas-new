<?php

namespace App\Notifications;

use App\Models\Incident;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class IncidentSubmittedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Incident $incident) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => 'New incident: '.$this->incident->title,
            'body' => $this->incident->description,
            'action_url' => '/incidents',
            'type' => 'incident',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New incident: '.$this->incident->title)
            ->line('Severity: '.(string) ($this->incident->severity?->value ?? $this->incident->severity))
            ->line($this->incident->description)
            ->action('Review incident', url('/incidents'));
    }
}
