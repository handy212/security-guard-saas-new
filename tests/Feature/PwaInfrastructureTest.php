<?php

namespace Tests\Feature;

use Tests\TestCase;

class PwaInfrastructureTest extends TestCase
{
    public function test_guard_pwa_manifest_is_valid_json(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.json')), true);

        $this->assertIsArray($manifest);
        $this->assertSame('/guard', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_service_worker_exists(): void
    {
        $this->assertFileExists(public_path('sw.js'));
        $this->assertStringContainsString('install', file_get_contents(public_path('sw.js')));
    }

    public function test_guard_mobile_route_requires_authentication(): void
    {
        $this->get('/guard')->assertRedirect('/login');
    }
}
