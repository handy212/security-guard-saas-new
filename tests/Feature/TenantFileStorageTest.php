<?php

namespace Tests\Feature;

use App\Services\TenantFileStorageService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TenantFileStorageTest extends TestCase
{
    public function test_sensitive_uploads_use_private_tenant_disk(): void
    {
        Storage::fake('tenant_private');

        $service = app(TenantFileStorageService::class);
        $path = $service->store(UploadedFile::fake()->create('license.pdf', 100), 'tenants/1/guards/1');

        Storage::disk('tenant_private')->assertExists($path);
        $this->assertSame('tenant_private', $service->diskName());
    }
}
