<?php

namespace App\Jobs;

use App\Events\OperationalAlertsChanged;
use App\Models\AuditLog;
use App\Models\OperationalBroadcastDelivery;
use Illuminate\Contracts\Broadcasting\Factory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeliverOperationalBroadcast implements ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public array $backoff = [5, 15, 60];

    public function __construct(public int $tenantId, public int $deliveryId) {}

    public function handle(Factory $broadcasting): void
    {
        $pending = OperationalBroadcastDelivery::forTenant($this->tenantId)->whereKey($this->deliveryId)->whereNull('sent_at');
        $delivery = $pending->first();
        if (! $delivery || ! $pending->increment('attempts', 1, ['last_attempt_at' => now(), 'failed_at' => null])) {
            return;
        }

        $event = new OperationalAlertsChanged($this->tenantId);
        try {
            $broadcasting->connection($delivery->connection)->broadcast(
                [(string) $event->broadcastOn()], $event->broadcastAs(), $event->broadcastWith(),
            );
        } catch (Throwable $exception) {
            $this->recordOutcome('broadcast_attempt_failed', $exception);

            throw $exception;
        }

        $pending->update(['sent_at' => now(), 'failed_at' => null]);
    }

    public function failed(?Throwable $exception): void
    {
        DB::transaction(function () use ($exception): void {
            $changed = OperationalBroadcastDelivery::forTenant($this->tenantId)->whereKey($this->deliveryId)
                ->whereNull('sent_at')->whereNull('failed_at')->update(['failed_at' => now()]);

            if ($changed) {
                $this->recordOutcome('broadcast_failed', $exception);
            }
        });
    }

    private function recordOutcome(string $action, ?Throwable $exception): void
    {
        AuditLog::create([
            'tenant_id' => $this->tenantId,
            'action' => 'operational_alert.'.$action,
            'auditable_type' => OperationalBroadcastDelivery::class,
            'auditable_id' => $this->deliveryId,
            'metadata' => ['exception' => $exception ? $exception::class : null],
        ]);
    }
}
