<?php

namespace Tests\Feature;

use App\Livewire\Guards\GuardApplicationQueue;
use App\Livewire\Public\GuardApplicationForm;
use App\Models\Guard;
use App\Models\GuardApplication;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GuardApplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_application_form_creates_pending_application(): void
    {
        $this->seed();

        $tenant = Tenant::where('slug', 'demo-security')->firstOrFail();

        Livewire::test(GuardApplicationForm::class, ['tenant' => $tenant->slug])
            ->set('first_name', 'Ama')
            ->set('last_name', 'Mensah')
            ->set('phone', '0244000000')
            ->set('email', 'ama@example.com')
            ->set('duty_type', 'dispatch')
            ->call('submit')
            ->assertSet('submitted', true);

        $this->assertDatabaseHas('guard_applications', [
            'tenant_id' => $tenant->id,
            'first_name' => 'Ama',
            'last_name' => 'Mensah',
            'duty_type' => 'dispatch',
            'status' => 'pending',
        ]);
    }

    public function test_approving_application_creates_inactive_unverified_guard(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $admin->tenant);
        setPermissionsTeamId($admin->tenant_id);

        $application = GuardApplication::create([
            'tenant_id' => $admin->tenant_id,
            'first_name' => 'Kojo',
            'last_name' => 'Asante',
            'phone' => '0200000000',
            'email' => 'kojo@example.com',
            'duty_type' => 'guardian',
            'status' => 'pending',
        ]);

        Livewire::actingAs($admin)
            ->test(GuardApplicationQueue::class)
            ->call('approve', $application->id)
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('approved', $application->status);
        $this->assertNotNull($application->guard_id);

        $guard = Guard::find($application->guard_id);
        $this->assertSame('inactive', $guard->status);
        $this->assertSame('unverified', $guard->verification_status);
        $this->assertSame('guardian', $guard->duty_type->value);
    }
}
