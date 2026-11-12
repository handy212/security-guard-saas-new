<?php

namespace Tests\Feature;

use App\Livewire\Compliance\ComplianceDashboard;
use App\Models\Guard;
use App\Models\GuardCertification;
use App\Models\GuardDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ComplianceDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
    }

    public function test_expiring_certification_shows_expiry_and_a_clear_review_action(): void
    {
        $guard = Guard::query()->where('tenant_id', $this->admin->tenant_id)->firstOrFail();
        $certification = GuardCertification::create([
            'tenant_id' => $this->admin->tenant_id,
            'guard_id' => $guard->id,
            'name' => 'First aid renewal',
            'issuer' => 'Training provider',
            'expires_at' => now()->addDays(10)->toDateString(),
            'status' => 'valid',
        ]);
        $document = GuardDocument::create([
            'tenant_id' => $this->admin->tenant_id,
            'guard_id' => $guard->id,
            'type' => 'id',
            'file_path' => 'demo/guards/test-id.pdf',
            'expires_at' => now()->addDays(12)->toDateString(),
            'status' => 'active',
        ]);

        Livewire::actingAs($this->admin)
            ->test(ComplianceDashboard::class)
            ->assertSee($guard->full_name)
            ->assertSee($certification->name)
            ->assertSee($certification->expires_at->format('M j, Y'))
            ->assertSee('Review qualification')
            ->assertSee(route('guards.show', $guard).'?tab=qualifications')
            ->assertSee($document->typeLabel())
            ->assertSee($document->expires_at->format('M j, Y'))
            ->assertSee('Review file')
            ->assertSee(route('guards.show', $guard).'?tab=files');
    }
}
