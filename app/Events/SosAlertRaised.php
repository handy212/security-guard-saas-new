<?php

namespace App\Events;

use App\Models\SosAlert;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SosAlertRaised implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public SosAlert $alert)
    {
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tenant.'.$this->alert->tenant_id.'.dispatch'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'sos.raised';
    }

    public function broadcastWith(): array
    {
        $this->alert->loadMissing(['assignedGuard', 'site']);

        return [
            'id' => $this->alert->id,
            'guard_name' => $this->alert->assignedGuard?->full_name,
            'site' => $this->alert->site?->name,
            'latitude' => $this->alert->latitude,
            'longitude' => $this->alert->longitude,
            'message' => $this->alert->message,
            'status' => $this->alert->status,
            'raised_at' => optional($this->alert->raised_at)->toIso8601String(),
        ];
    }
}
