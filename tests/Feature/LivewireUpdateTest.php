<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LivewireUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedules_page_livewire_update_endpoint_accepts_valid_payload(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->firstOrFail();

        $page = $this->actingAs($admin)->get('/schedules');
        $page->assertOk();

        $html = $page->getContent();

        preg_match('/data-csrf="([^"]+)"/', $html, $csrfMatch);
        preg_match_all('/wire:snapshot="([^"]+)"/', $html, $snapshotMatches);

        $this->assertNotEmpty($csrfMatch[1] ?? null, 'Expected Livewire CSRF token on schedules page.');
        $this->assertNotEmpty($snapshotMatches[1] ?? [], 'Expected Livewire snapshot on schedules page.');

        $snapshot = null;

        foreach ($snapshotMatches[1] as $encodedSnapshot) {
            $decoded = html_entity_decode($encodedSnapshot, ENT_QUOTES);
            $payload = json_decode($decoded, true);

            if (($payload['memo']['name'] ?? null) === 'shifts.schedule-board') {
                $snapshot = $decoded;
                break;
            }
        }

        $this->assertNotNull($snapshot, 'Expected schedule board Livewire snapshot on schedules page.');

        $response = $this->actingAs($admin)
            ->withHeader('X-Livewire', '')
            ->postJson('/livewire/update', [
                '_token' => $csrfMatch[1],
                'components' => [
                    [
                        'snapshot' => $snapshot,
                        'updates' => [],
                        'calls' => [
                            ['path' => '', 'method' => 'openForm', 'params' => []],
                        ],
                    ],
                ],
            ]);

        $response->assertOk();
        $response->assertJsonStructure(['components']);
    }

    public function test_livewire_update_returns_404_for_empty_components(): void
    {
        $this->seed();

        $admin = User::where('email', 'admin@demo.test')->firstOrFail();

        $this->actingAs($admin)
            ->withHeader('X-Livewire', '')
            ->postJson('/livewire/update', [
                '_token' => csrf_token(),
                'components' => [],
            ])
            ->assertNotFound();
    }
}
