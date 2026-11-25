<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class JwmDeviceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_patrol_reader_features_are_unavailable(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        setPermissionsTeamId($admin->tenant_id);

        $this->actingAs($admin)->get('/patrols')->assertOk()->assertDontSee('Patrol readers');
        $this->get('/patrols/devices')->assertNotFound();
        $this->getJson('/api/v1/admin/jwm-devices')->assertNotFound();
        $this->postJson('/api/v1/jwm/devices/1/events', [])->assertNotFound();
    }

    public function test_legacy_patrol_reader_token_cannot_use_other_apis(): void
    {
        $this->seed();
        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        $token = $admin->createToken('legacy-reader', ['jwm:ingest'])->plainTextToken;

        $this->withToken($token)->getJson('/api/v1/me/assignments')->assertForbidden();
    }
}
