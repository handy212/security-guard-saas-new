<?php

namespace App\Services;

use App\Contracts\FileScanner;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformBrandingService
{
    private ?array $branding = null;

    public function __construct(private TenantFileStorageService $files, private AuditLogService $audit) {}

    /** @return array{command_dashboard: bool, name: ?string, sign_in_message: ?string, support_email: ?string, light_accent: ?string, dark_accent: ?string, logo: ?string, favicon: ?string, background: ?string} */
    public function current(): array
    {
        return $this->branding ??= $this->decode($this->settings()->value('value'));
    }

    public function name(): string
    {
        return $this->current()['name'] ?? config('app.name', 'GuardCore Pro');
    }

    public function logoUrl(): ?string
    {
        return $this->assetUrl('logo');
    }

    public function faviconUrl(): string
    {
        return $this->assetUrl('favicon') ?? asset('icons/icon-192.png');
    }

    public function backgroundUrl(): string
    {
        return $this->assetUrl('background') ?? asset('icons/sign-in-background.webp');
    }

    public function assetResponse(string $kind, string $version): StreamedResponse
    {
        $path = $this->current()[$kind] ?? null;
        $disk = $this->files->disk();
        abort_unless($path && hash_equals(sha1($path), $version) && $disk->exists($path), 404);

        return $disk->response($path, headers: [
            'Cache-Control' => 'public, max-age=31536000, immutable',
            'Content-Type' => match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
                'jpg', 'jpeg' => 'image/jpeg',
                'webp' => 'image/webp',
                'ico' => 'image/x-icon',
                default => 'image/png',
            },
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /**
     * @param  array{command_dashboard: bool, name: ?string, sign_in_message: ?string, support_email: ?string, light_accent: ?string, dark_accent: ?string}  $identity
     * @param  array{logo?: ?UploadedFile, favicon?: ?UploadedFile, background?: ?UploadedFile}  $files
     * @param  array{logo?: bool, favicon?: bool, background?: bool}  $remove
     */
    public function save(array $identity, array $files, array $remove): void
    {
        $uploads = [];

        try {
            foreach ($files as $kind => $file) {
                if ($file !== null) {
                    $path = $kind === 'favicon' && $file->getMimeType() === 'image/png'
                        ? $this->storePngFavicon($file)
                        : $this->files->store($file, 'platform/branding');
                    throw_if($path === '', RuntimeException::class, 'The branding image could not be stored.');
                    $uploads[$kind] = $path;
                }
            }

            $previous = DB::transaction(function () use ($identity, $uploads, $remove): array {
                $settings = $this->settings();
                $previous = $this->decode($settings->lockForUpdate()->value('value'));
                $branding = $identity;
                foreach (['logo', 'favicon', 'background'] as $kind) {
                    $branding[$kind] = $uploads[$kind] ?? (($remove[$kind] ?? false) ? null : $previous[$kind]);
                }

                $settings->update(['value' => json_encode($branding, JSON_THROW_ON_ERROR)]);
                $this->audit->recordPlatform('platform.branding.updated', metadata: [
                    'name' => $identity['name'],
                    'command_dashboard' => $identity['command_dashboard'],
                    'custom_logo' => isset($branding['logo']),
                    'custom_favicon' => isset($branding['favicon']),
                    'custom_background' => isset($branding['background']),
                ]);

                return $previous;
            });
        } catch (\Throwable $error) {
            foreach ($uploads as $path) {
                $this->files->disk()->delete($path);
            }

            throw $error;
        }

        $this->branding = null;
        foreach (['logo', 'favicon', 'background'] as $kind) {
            if ($previous[$kind] && $previous[$kind] !== $this->current()[$kind]) {
                $this->files->disk()->delete($previous[$kind]);
            }
        }
    }

    private function storePngFavicon(UploadedFile $file): string
    {
        app(FileScanner::class)->scan($file);
        $image = @imagecreatefrompng($file->getPathname());
        if ($image === false) {
            throw ValidationException::withMessages(['favicon' => 'Choose a readable PNG or ICO file.']);
        }

        $size = 192;
        $icon = imagecreatetruecolor($size, $size);
        imagealphablending($icon, false);
        imagesavealpha($icon, true);
        imagefill($icon, 0, 0, imagecolorallocatealpha($icon, 0, 0, 0, 127));

        $scale = $size / max(imagesx($image), imagesy($image));
        $width = max(1, (int) round(imagesx($image) * $scale));
        $height = max(1, (int) round(imagesy($image) * $scale));
        imagecopyresampled($icon, $image, intdiv($size - $width, 2), intdiv($size - $height, 2), 0, 0, $width, $height, imagesx($image), imagesy($image));

        $stream = fopen('php://temp', 'w+b');
        $path = 'platform/branding/'.Str::uuid().'.png';
        try {
            throw_unless(imagepng($icon, $stream), RuntimeException::class, 'The favicon could not be encoded.');
            rewind($stream);
            throw_unless($this->files->disk()->put($path, $stream), RuntimeException::class, 'The favicon could not be stored.');
        } finally {
            fclose($stream);
        }

        return $path;
    }

    private function settings(): Builder
    {
        return DB::table('platform_settings')->where('key', 'branding');
    }

    private function decode(?string $value): array
    {
        return array_replace(
            ['command_dashboard' => false, 'name' => null, 'sign_in_message' => null, 'support_email' => null, 'light_accent' => null, 'dark_accent' => null, 'logo' => null, 'favicon' => null, 'background' => null],
            json_decode($value ?? '{}', true, flags: JSON_THROW_ON_ERROR),
        );
    }

    private function assetUrl(string $kind): ?string
    {
        $path = $this->current()[$kind];

        return $path ? route('platform.brand-asset', ['kind' => $kind, 'version' => sha1($path)]) : null;
    }
}
