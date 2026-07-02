<?php

namespace App\Services;

use DateTimeInterface;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantFileStorageService
{
    public function diskName(): string
    {
        return (string) config('filesystems.tenant_disk', 'tenant_private');
    }

    public function disk(): Filesystem
    {
        return Storage::disk($this->diskName());
    }

    public function store(UploadedFile $file, string $directory): string
    {
        app(\App\Contracts\FileScanner::class)->scan($file);

        return $file->store($directory, $this->diskName());
    }

    public function exists(string $path): bool
    {
        if ($this->disk()->exists($path)) {
            return true;
        }

        return Storage::disk('public')->exists($path);
    }

    public function response(string $path): StreamedResponse
    {
        if ($this->disk()->exists($path)) {
            return $this->disk()->response($path);
        }

        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }

    public function temporaryUrl(string $path, DateTimeInterface $expiresAt): ?string
    {
        if (! $this->disk()->exists($path)) {
            return null;
        }

        if (config('filesystems.disks.'.$this->diskName().'.driver') !== 's3') {
            return null;
        }

        return $this->disk()->temporaryUrl($path, $expiresAt);
    }
}
