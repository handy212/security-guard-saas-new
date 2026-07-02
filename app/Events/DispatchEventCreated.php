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

    public function __construct(public DispatchEvent $event) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.'.$this->event->tenant_id.'.dispatch'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'dispatch.created';
    }

    public function broadcastWith(): array
    {
        $this->event->loadMissing(['site', 'assignedGuard', 'clientAccount']);

        return [
            'id' => $this->event->id,
            'dispatch_number' => $this->event->dispatch_number,
            'event_type' => $this->event->event_type,
            'priority' => $this->event->priority->value,
            'status' => $this->event->status->value,
            'site' => $this->event->site?->name,
            'client' => $this->event->clientAccount?->name,
            'guard' => $this->event->assignedGuard?->full_name,
            'caller_name' => $this->event->caller_name,
            'incident_location' => $this->event->incident_location,
            'description' => $this->event->description,
            'latitude' => $this->event->latitude,
            'longitude' => $this->event->longitude,
            'opened_at' => optional($this->event->opened_at)->toIso8601String(),
        ];
    }
}
