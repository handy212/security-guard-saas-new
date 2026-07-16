<?php

namespace App\Services;

use App\Models\ClientComplaint;
use App\Support\MutableStatus;
use App\Support\TenantContext;

class ComplaintService
{
    public function create(array $data): ClientComplaint
    {
        return ClientComplaint::create($data + [
            'tenant_id' => TenantContext::id(),
            'status' => $data['status'] ?? 'open',
        ]);
    }

    public function update(ClientComplaint $complaint, array $data): ClientComplaint
    {
        MutableStatus::assertMutable($complaint);

        $complaint->update(collect($data)->only([
            'client_account_id', 'site_id', 'subject', 'description', 'priority', 'assigned_to',
        ])->filter(fn ($v) => $v !== null)->all());

        return $complaint->fresh();
    }

    public function delete(ClientComplaint $complaint): void
    {
        MutableStatus::assertMutable($complaint);
        $complaint->delete();
    }

    public function resolve(ClientComplaint $complaint): ClientComplaint
    {
        $complaint->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return $complaint->fresh();
    }
}
