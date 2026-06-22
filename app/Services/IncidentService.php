<?php

namespace App\Services;

use App\Models\Incident;

class IncidentService
{
    public function submit(array $data): Incident
    {
        return Incident::create($data + ['status' => 'submitted', 'reported_at' => now()]);
    }

    public function approve(Incident $incident, int $userId): Incident
    {
        $incident->update(['status' => 'approved', 'approved_by_user_id' => $userId, 'approved_at' => now()]);
        return $incident->fresh();
    }

    public function close(Incident $incident, ?string $resolution = null): Incident
    {
        $incident->update(['status' => 'closed', 'resolution' => $resolution, 'closed_at' => now()]);
        return $incident->fresh();
    }
}
