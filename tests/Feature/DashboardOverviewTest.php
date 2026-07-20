<?php

namespace Tests\Feature;

use App\Enums\DispatchPriority;
use App\Enums\DispatchStatus;
use App\Enums\ShiftStatus;
use App\Livewire\Dashboard\AttentionIndicator;
use App\Livewire\Dashboard\Overview;
use App\Models\DispatchEvent;
use App\Models\Guard;
use App\Models\Shift;
use App\Models\Site;
use App\Models\SosAlert;
use App\Models\User;
use App\Services\DashboardMetricsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardOverviewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->firstOrFail();
        app()->instance('currentTenant', $this->admin->tenant);
        setPermissionsTeamId($this->admin->tenant_id);
    }

    public function test_dashboard_renders_for_tenant_admin(): void
    {
        Livewire::actingAs($this->admin)
            ->test(Overview::class)
            ->assertSuccessful()
            ->assertSee('Operations overview')
            ->assertSee('Quick actions')
            ->assertSee('Open dispatches')
            ->assertSeeHtml("Today's schedule");
    }

    public function test_attention_items_include_sos_and_understaffed_shifts(): void
    {
        $tenantId = $this->admin->tenant_id;
        $site = Site::where('tenant_id', $tenantId)->firstOrFail();

        $guard = Guard::where('tenant_id', $tenantId)->firstOrFail();

        SosAlert::create([
            'tenant_id' => $tenantId,
            'guard_id' => $guard->id,
            'site_id' => $site->id,
            'status' => 'open',
            'latitude' => 5.6,
            'longitude' => -0.2,
            'message' => 'Need backup',
            'raised_at' => now(),
        ]);

        Shift::create([
            'tenant_id' => $tenantId,
            'client_account_id' => $site->client_account_id,
            'site_id' => $site->id,
            'title' => 'Night gate',
            'starts_at' => now()->setTime(18, 0),
            'ends_at' => now()->setTime(6, 0)->addDay(),
            'required_guards' => 2,
            'status' => ShiftStatus::SCHEDULED,
        ]);

        DispatchEvent::create([
            'tenant_id' => $tenantId,
            'site_id' => $site->id,
            'client_account_id' => $site->client_account_id,
            'dispatch_number' => 'DSP-TEST-1',
            'event_type' => 'alarm',
            'priority' => DispatchPriority::HIGH,
            'status' => DispatchStatus::OPEN,
            'description' => 'Gate alarm',
            'opened_at' => now(),
            'created_by_user_id' => $this->admin->id,
        ]);

        $metrics = app(DashboardMetricsService::class);
        $items = collect($metrics->attentionItems($tenantId))->pluck('key');

        $this->assertTrue($items->contains('sos'));
        $this->assertTrue($items->contains('staffing'));
        $this->assertTrue($items->contains('dispatch'));

        Livewire::actingAs($this->admin)
            ->test(AttentionIndicator::class)
            ->assertSee('Needs attention')
            ->assertSee('open SOS')
            ->assertSee('understaffed')
            ->assertSeeHtml('needing attention');
    }
}
