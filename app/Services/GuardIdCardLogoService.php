<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\Tenant;
use Illuminate\Support\Facades\Storage;

class GuardIdCardLogoService
{
    private const MAX_HEIGHT = 48;

    public function url(?string $path): ?string
    {
        return $path ? route('files.id-card-logo') : null;
    }

    /**
     * @return string|null Absolute path to PNG (within DomPDF chroot)
     */
    public function pngFile(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);

        if (! extension_loaded('gd')) {
            return is_readable($absolute) ? $absolute : null;
        }

        $source = $this->loadImage($absolute);
        if ($source === null) {
            return is_readable($absolute) ? $absolute : null;
        }

        $binary = $this->resizePng($source);
        imagedestroy($source);

        if ($binary === null) {
            return is_readable($absolute) ? $absolute : null;
        }

        $dir = storage_path('app/temp');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        $file = $dir.'/id-card-logo-'.uniqid('', true).'.png';
        if (@file_put_contents($file, $binary) === false) {
            return null;
        }

        return $file;
    }

    public function dataUri(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        $binary = is_readable($absolute) ? (string) file_get_contents($absolute) : null;

        if ($binary === null || $binary === '') {
            return null;
        }

        $mime = match (strtolower(pathinfo($absolute, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            default => 'image/png',
        };

        return 'data:'.$mime.';base64,'.base64_encode($binary);
    }

    public function heightPt(): int
    {
        return 18;
    }

    private function loadImage(string $absolute): ?\GdImage
    {
        $info = @getimagesize($absolute);

        if ($info === false) {
            return null;
        }

        return match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($absolute) ?: null,
            IMAGETYPE_PNG => @imagecreatefrompng($absolute) ?: null,
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? (@imagecreatefromwebp($absolute) ?: null) : null,
            IMAGETYPE_GIF => @imagecreatefromgif($absolute) ?: null,
            default => null,
        };
    }

    private function resizePng(\GdImage $source): ?string
    {
        $sw = imagesx($source);
        $sh = imagesy($source);
        $maxH = self::MAX_HEIGHT;
        $scale = $maxH / $sh;
        $dw = max(1, (int) round($sw * $scale));
        $dh = max(1, (int) round($sh * $scale));

        $dest = imagecreatetruecolor($dw, $dh);
        if ($dest === false) {
            return null;
        }

        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
        imagecopyresampled($dest, $source, 0, 0, 0, 0, $dw, $dh, $sw, $sh);

        ob_start();
        imagepng($dest);
        $png = (string) ob_get_clean();
        imagedestroy($dest);

        return $png !== '' ? $png : null;
    }
}
