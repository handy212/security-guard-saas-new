<?php

namespace Tests\Feature;

use App\Models\Guard;
use App\Models\ShiftAssignment;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileApiAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guard_cannot_clock_in_to_another_guards_assignment(): void
    {
        $this->seed();

        $tenant = Tenant::first();
        $guardUser = User::where('email', 'john.guard@test')->first();
        $otherGuard = Guard::create([
            'tenant_id' => $tenant->id,
            'employee_number' => 'G-002',
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

        Sanctum::actingAs($guardUser);

        $this->postJson('/api/v1/attendance/clock-in', [
            'shift_assignment_id' => $assignment->id,
            'latitude' => 6.206,
            'longitude' => -1.665,
        ])->assertNotFound();
    }
}
