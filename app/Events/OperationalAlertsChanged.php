<?php

namespace App\Events;

use App\Models\AuditLog;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Throwable;

class OperationalAlertsChanged implements ShouldBroadcast, ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(private int $tenantId) {}

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('tenant.'.$this->tenantId.'.dispatch');
    }

    public function broadcastAs(): string
    {
        return 'operational-alerts.changed';
    }

    public function broadcastWith(): array
    {
        return [];
    }

    public function failed(?Throwable $exception): void
    {
        AuditLog::create([
            'tenant_id' => $this->tenantId,
            'action' => 'operational_alert.broadcast_failed',
            'metadata' => ['exception' => $exception ? $exception::class : null],
        ]);
    }
}
