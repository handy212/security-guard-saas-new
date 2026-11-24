<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class NotificationInboxChanged implements ShouldBroadcast
{
    use Dispatchable;

    public function __construct(private int $userId) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('user.'.$this->userId);
    }

    public function broadcastAs(): string
    {
        return 'notification-inbox.changed';
    }

    public function broadcastWith(): array
    {
        return [];
    }
}
