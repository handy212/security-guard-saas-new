<?php

namespace App\Listeners;

use App\Events\NotificationInboxChanged;
use App\Models\User;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Notifications\Events\NotificationSent;

class BroadcastNotificationInboxChange implements ShouldHandleEventsAfterCommit
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel === 'database' && $event->notifiable instanceof User) {
            try {
                NotificationInboxChanged::dispatch($event->notifiable->id);
            } catch (\Throwable $exception) {
                report($exception);
            }
        }
    }
}
