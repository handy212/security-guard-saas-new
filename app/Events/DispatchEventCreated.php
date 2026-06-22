<?php

namespace App\Events;

use App\Models\DispatchEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DispatchEventCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public DispatchEvent $event)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.'.$this->event->tenant_id.'.dispatch'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dispatch.event';
    }

    public function broadcastWith(): array
    {
        $this->event->loadMissing('site');

        return [
            'id' => $this->event->id,
            'event_type' => $this->event->event_type,
            'priority' => $this->event->priority,
            'status' => $this->event->status,
            'site' => $this->event->site?->name,
            'description' => $this->event->description,
            'opened_at' => optional($this->event->opened_at)->toIso8601String(),
        ];
    }
}
