<?php

namespace App\Services;

use App\Models\VisitorLog;
use App\Support\MutableStatus;
use App\Support\TenantContext;

class VisitorService
{
    public function checkIn(array $data): VisitorLog
    {
        return VisitorLog::create($data + [
            'tenant_id' => TenantContext::id(),
            'checked_in_at' => $data['checked_in_at'] ?? now(),
        ]);
    }

    public function update(VisitorLog $visitor, array $data): VisitorLog
    {
        MutableStatus::assertMutable($visitor);

        $visitor->update(collect($data)->only([
            'site_id', 'visitor_name', 'visitor_phone', 'company', 'purpose', 'vehicle_plate',
            'id_type', 'id_number', 'guard_id',
        ])->all());

        return $visitor->fresh();
    }

    public function delete(VisitorLog $visitor): void
    {
        MutableStatus::assertMutable($visitor);
        $visitor->delete();
    }

    public function checkOut(VisitorLog $visitor): VisitorLog
    {
        $visitor->update(['checked_out_at' => now(), 'status' => 'checked_out']);

        return $visitor->fresh();
    }
}
