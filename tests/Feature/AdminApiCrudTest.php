<?php

namespace Tests\Feature;

use App\Models\ClientAccount;
use App\Models\Expense;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AdminApiCrudTest extends TestCase
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

    public function test_admin_can_issue_and_revoke_token(): void
    {
        $response = $this->postJson('/api/v1/auth/token', [
            'email' => 'admin@demo.test',
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = $response->json('data.token');
        $this->assertNotEmpty($token);

        $this->withToken($token)
            ->deleteJson('/api/v1/auth/token')
            ->assertNoContent();
    }

    public function test_guard_cannot_issue_admin_token(): void
    {
        $this->postJson('/api/v1/auth/token', [
            'email' => 'john.guard@test',
            'password' => 'password',
            'device_name' => 'phpunit',
        ])->assertUnprocessable();
    }

    public function test_client_crud_via_admin_api(): void
    {
        Sanctum::actingAs($this->admin);

        $created = $this->postJson('/api/v1/admin/clients', [
            'name' => 'API Client Co',
            'email' => 'api-client@test',
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('data.name', 'API Client Co');

        $id = $created->json('data.id');

        $this->getJson('/api/v1/admin/clients')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta' => ['current_page', 'total']]);

        $this->putJson("/api/v1/admin/clients/{$id}", [
            'name' => 'API Client Updated',
            'status' => 'active',
        ])->assertOk()
            ->assertJsonPath('data.name', 'API Client Updated');

        $this->deleteJson("/api/v1/admin/clients/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('client_accounts', ['id' => $id]);
    }

    public function test_expense_draft_edit_delete_and_locked_when_paid(): void
    {
        Sanctum::actingAs($this->admin);

        $created = $this->postJson('/api/v1/admin/expenses', [
            'title' => 'API Fuel',
            'expense_date' => today()->toDateString(),
            'amount' => 42.5,
        ])->assertCreated();

        $id = $created->json('data.id');

        $this->putJson("/api/v1/admin/expenses/{$id}", [
            'title' => 'API Fuel Updated',
            'expense_date' => today()->toDateString(),
            'amount' => 55,
        ])->assertOk()
            ->assertJsonPath('data.title', 'API Fuel Updated');

        $paid = Expense::create([
            'tenant_id' => $this->admin->tenant_id,
            'created_by_user_id' => $this->admin->id,
            'expense_number' => 'EXP-PAID-API',
            'title' => 'Already paid',
            'expense_date' => today(),
            'amount' => 10,
            'status' => 'paid',
            'paid_at' => now(),
        ]);

        $this->deleteJson("/api/v1/admin/expenses/{$paid->id}")
            ->assertUnprocessable();

        $this->assertDatabaseHas('expenses', ['id' => $paid->id]);

        $this->deleteJson("/api/v1/admin/expenses/{$id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('expenses', ['id' => $id]);
    }

    public function test_staff_users_list_requires_settings_manage(): void
    {
        Sanctum::actingAs($this->admin);

        $this->getJson('/api/v1/admin/staff-users')
            ->assertOk()
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_cross_tenant_client_is_forbidden(): void
    {
        Sanctum::actingAs($this->admin);

        $tenantB = Tenant::create(['name' => 'Other Co', 'slug' => 'other-co-api', 'status' => 'active']);
        $foreign = ClientAccount::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Foreign Client',
            'status' => 'active',
        ]);

        $this->getJson("/api/v1/admin/clients/{$foreign->id}")
            ->assertNotFound();
    }

    public function test_client_note_nested_crud_and_web_route_isolation(): void
    {
        Sanctum::actingAs($this->admin);

        $client = ClientAccount::firstOrFail();

        $this->assertStringNotContainsString('/api/', route('clients.show', $client));

        $created = $this->postJson("/api/v1/admin/clients/{$client->id}/notes", [
            'body' => 'API nested note',
            'is_internal' => true,
        ])->assertCreated()
            ->assertJsonPath('data.body', 'API nested note');

        $noteId = $created->json('data.id');

        $this->getJson("/api/v1/admin/clients/{$client->id}/notes")
            ->assertOk()
            ->assertJsonStructure(['data', 'meta'])
            ->assertJsonFragment(['id' => $noteId, 'body' => 'API nested note']);

        $this->putJson("/api/v1/admin/clients/{$client->id}/notes/{$noteId}", [
            'body' => 'Updated nested note',
        ])->assertOk()
            ->assertJsonPath('data.body', 'Updated nested note');

        $this->deleteJson("/api/v1/admin/clients/{$client->id}/notes/{$noteId}")
            ->assertNoContent();

        $this->assertDatabaseMissing('client_notes', ['id' => $noteId]);
    }
}
