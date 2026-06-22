<?php

namespace Tests\Feature;

use Tests\TestCase;

class EnterpriseFeatureCoverageTest extends TestCase
{
    public function test_enterprise_completion_doc_exists(): void
    {
        $this->assertFileExists(base_path('docs/FULL-ENTERPRISE-COMPLETION.md'));
    }

    public function test_public_index_exists(): void
    {
        $this->assertFileExists(public_path('index.php'));
    }

    public function test_api_routes_file_is_registered_in_bootstrap(): void
    {
        $bootstrap = file_get_contents(base_path('bootstrap/app.php'));
        $this->assertStringContainsString('routes/api.php', $bootstrap);
    }
}
