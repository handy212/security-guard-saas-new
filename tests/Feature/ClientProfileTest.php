<?php

namespace Tests\Feature;

use App\Livewire\Clients\ClientProfile;
use App\Models\ClientAccount;
use App\Models\ClientContact;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->admin = User::where('email', 'admin@demo.test')->first();
        app()->instance('currentTenant', $this->admin->tenant);
    }

    public function test_client_profile_page_loads_with_overview(): void
    {
        $client = ClientAccount::first();

        $this->actingAs($this->admin)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee($client->name)
            ->assertSee('Overview')
            ->assertSee('Post Sites')
            ->assertSee('Email Reports');
    }

    public function test_client_profile_tabs_switch(): void
    {
        $client = ClientAccount::first();

        Livewire::actingAs($this->admin)
            ->test(ClientProfile::class, ['clientAccount' => $client])
            ->call('setTab', 'contacts')
            ->assertSet('activeTab', 'contacts')
            ->assertSee('Add contact');
    }

    public function test_client_contact_crud_via_profile(): void
    {
        $client = ClientAccount::first();

        Livewire::actingAs($this->admin)
            ->test(ClientProfile::class, ['clientAccount' => $client])
            ->call('setTab', 'contacts')
            ->set('contactForm.name', 'Jane Ops')
            ->set('contactForm.email', 'jane@client.test')
            ->set('contactForm.role', 'Operations Manager')
            ->call('addContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('client_contacts', [
            'client_account_id' => $client->id,
            'name' => 'Jane Ops',
            'email' => 'jane@client.test',
        ]);

        $contact = ClientContact::where('email', 'jane@client.test')->first();

        Livewire::actingAs($this->admin)
            ->test(ClientProfile::class, ['clientAccount' => $client->fresh()])
            ->call('deleteContact', $contact->id);

        $this->assertDatabaseMissing('client_contacts', ['id' => $contact->id]);
    }

    public function test_client_profile_rejects_other_tenant(): void
    {
        $otherTenant = Tenant::factory()->create();
        $otherTenantClient = ClientAccount::create([
            'tenant_id' => $otherTenant->id,
            'name' => 'Foreign Client',
            'status' => 'active',
        ]);

        $this->actingAs($this->admin)
            ->get(route('clients.show', $otherTenantClient))
            ->assertNotFound();
    }

    public function test_clients_index_links_to_profile(): void
    {
        $client = ClientAccount::first();

        $this->actingAs($this->admin)
            ->get(route('clients.index'))
            ->assertOk()
            ->assertSee(route('clients.show', $client), false);
    }

    public function test_client_contact_edit_via_profile(): void
    {
        $client = ClientAccount::first();

        Livewire::actingAs($this->admin)
            ->test(ClientProfile::class, ['clientAccount' => $client])
            ->call('setTab', 'contacts')
            ->set('contactForm.name', 'Jane Ops')
            ->set('contactForm.email', 'jane@client.test')
            ->call('saveContact')
            ->assertHasNoErrors();

        $contact = ClientContact::where('email', 'jane@client.test')->first();

        Livewire::actingAs($this->admin)
            ->test(ClientProfile::class, ['clientAccount' => $client->fresh()])
            ->call('editContact', $contact->id)
            ->set('contactForm.name', 'Jane Operations')
            ->call('saveContact')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('client_contacts', [
            'id' => $contact->id,
            'name' => 'Jane Operations',
        ]);
    }

    public function test_overview_shows_map_and_stats(): void
    {
        $client = ClientAccount::first();

        $this->actingAs($this->admin)
            ->get(route('clients.show', $client))
            ->assertOk()
            ->assertSee('Last 7 days')
            ->assertSee('Recent shifts')
            ->assertSee('Client locations');
    }
}
