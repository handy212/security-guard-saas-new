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
    public function storeIncidentMedia(int $tenantId, int $incidentId, UploadedFile $file, ?string $caption = null): IncidentMedia
    {
        Incident::query()
            ->where('id', $incidentId)
            ->where('tenant_id', $tenantId)
            ->firstOrFail();

        $path = $file->store("tenants/{$tenantId}/incidents/{$incidentId}", 'public');

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

        $path = $file->store("tenants/{$tenantId}/guards/{$guardId}", 'public');

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
        return $file->store("tenants/{$tenantId}/guards/{$guardId}/photos", 'public');
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
