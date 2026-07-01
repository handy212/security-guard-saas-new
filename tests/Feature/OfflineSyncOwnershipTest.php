<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\OfflineSyncBatch;
use App\Models\ShiftAssignment;
use App\Models\Tenant;
use App\Models\User;
use App\Services\OfflineSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OfflineSyncOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_offline_clock_in_rejects_another_guards_assignment(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $guardUser = User::where('email', 'john.guard@test')->first();
        $otherGuard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-999',
            'first_name' => 'Other',
            'last_name' => 'Guard',
            'status' => 'active',
        ]);

        $assignment = ShiftAssignment::create([
            'tenant_id' => $tenant->id,
            'shift_id' => \App\Models\Shift::first()->id,
            'guard_id' => $otherGuard->id,
            'status' => 'assigned',
        ]);

        $batch = OfflineSyncBatch::create([
            'tenant_id' => $tenant->id,
            'user_id' => $guardUser->id,
            'payload' => [[
                'type' => 'clock_in',
                'shift_assignment_id' => $assignment->id,
                'latitude' => 6.206,
                'longitude' => -1.665,
            ]],
            'status' => 'pending',
        ]);

        app(OfflineSyncService::class)->process($batch->fresh());

        $batch->refresh();
        $this->assertSame('failed', $batch->status);
        $this->assertNotEmpty($batch->result['errors'] ?? []);
    }
}
