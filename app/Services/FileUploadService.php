<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\GuardDocument;
use App\Models\Incident;
use App\Models\IncidentMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class FileUploadService
{
    public function __construct(private TenantFileStorageService $storage)
    {
    }

    public function storeIncidentMedia(int $tenantId, int $incidentId, UploadedFile $file, ?string $caption = null): IncidentMedia
    {
        Incident::query()
            ->where('id', $incidentId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $path = $this->storage->store($file, "tenants/{$tenantId}/incidents/{$incidentId}");

        return IncidentMedia::create([
            'tenant_id' => $tenantId,
            'incident_id' => $incidentId,
            'file_path' => $path,
            'media_type' => str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'photo',
            'caption' => $caption,
        ]);
    }

    public function storeGuardDocument(int $tenantId, int $guardId, string $type, UploadedFile $file, ?string $expiresAt = null): GuardDocument
    {
        Guard::query()
            ->where('id', $guardId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $path = $this->storage->store($file, "tenants/{$tenantId}/guards/{$guardId}");

        return GuardDocument::create([
            'tenant_id' => $tenantId,
            'guard_id' => $guardId,
            'type' => $type,
            'file_path' => $path,
            'expires_at' => $expiresAt,
            'status' => 'valid',
        ]);
    }

    public function storeGuardPhoto(int $tenantId, int $guardId, UploadedFile $file): string
    {
        return $this->storage->store($file, "tenants/{$tenantId}/guards/{$guardId}/photos");
    }

    public function storeApplicationPhoto(int $tenantId, UploadedFile $file): string
    {
        return $this->storage->store($file, "tenants/{$tenantId}/applications/photos");
    }

    public function storeClientDocument(int $tenantId, int $clientAccountId, UploadedFile $file): string
    {
        return $this->storage->store($file, "tenants/{$tenantId}/clients/{$clientAccountId}");
    }

    public function storeSiteDocument(int $tenantId, int $siteId, UploadedFile $file): string
    {
        return $this->storage->store($file, "tenants/{$tenantId}/sites/{$siteId}");
    }

    public function storeExpenseReceipt(int $tenantId, int $expenseId, UploadedFile $file): string
    {
        return $this->storage->store($file, "tenants/{$tenantId}/expenses/{$expenseId}");
    }

    public function storeIdCardLogo(int $tenantId, UploadedFile $file): string
    {
        return $file->store("tenants/{$tenantId}/branding", 'public');
    }

    /**
     * Store an authorized signature, trimming empty padding so ink fills the card pad.
     */
    public function storeIdCardSignature(int $tenantId, UploadedFile $file): string
    {
        $path = $file->store("tenants/{$tenantId}/branding", 'public');
        $trimmed = app(GuardIdCardLogoService::class)->signaturePngBinary($path);

        if ($trimmed === null) {
            return $path;
        }

        $trimmedPath = "tenants/{$tenantId}/branding/signature-".uniqid('', true).'.png';
        Storage::disk('public')->put($trimmedPath, $trimmed);
        Storage::disk('public')->delete($path);

        return $trimmedPath;
    }

    public function storeDispatchAttachment(int $tenantId, int $dispatchId, UploadedFile $file): string
    {
        return $file->store("tenants/{$tenantId}/dispatches/{$dispatchId}", 'public');
    }

    public function storeMessageAttachment(int $tenantId, int $threadId, UploadedFile $file): string
    {
        return $file->store("tenants/{$tenantId}/messenger/{$threadId}", 'public');
    }

    public function guardPhotoUrl(Guard $guard): ?string
    {
        if (! $guard->photo_path) {
            return null;
        }

        return route('files.guard-photo', $guard);
    }

    public function guardDocumentUrl(GuardDocument $document): string
    {
        return route('files.guard-document', $document);
    }
}
