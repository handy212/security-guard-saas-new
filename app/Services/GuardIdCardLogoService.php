<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class GuardIdCardLogoService
{
    private const MAX_HEIGHT = 48;

    /** Padding kept around trimmed signature ink (px). */
    private const SIGNATURE_TRIM_PADDING = 4;

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

    /**
     * Signature data URI with transparent / near-white padding removed so ink fills the card pad.
     */
    public function signatureDataUri(?string $path): ?string
    {
        $binary = $this->signaturePngBinary($path);

        if ($binary === null) {
            return $this->dataUri($path);
        }

        return 'data:image/png;base64,'.base64_encode($binary);
    }

    /**
     * Trimmed signature PNG bytes for preview/PDF streaming. Falls back to null if GD unavailable.
     */
    public function signaturePngBinary(?string $path): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path) || ! extension_loaded('gd')) {
            return null;
        }

        $absolute = Storage::disk('public')->path($path);
        $source = $this->loadImage($absolute);

        if ($source === null) {
            return null;
        }

        $trimmed = $this->trimWhitespace($source);
        imagedestroy($source);

        if ($trimmed === null) {
            return null;
        }

        ob_start();
        imagepng($trimmed);
        $png = (string) ob_get_clean();
        imagedestroy($trimmed);

        return $png !== '' ? $png : null;
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

    /**
     * Crop near-transparent / near-white padding so signature ink fills the display box.
     */
    private function trimWhitespace(\GdImage $source): ?\GdImage
    {
        $sw = imagesx($source);
        $sh = imagesy($source);

        if ($sw < 1 || $sh < 1) {
            return null;
        }

        $minX = $sw;
        $minY = $sh;
        $maxX = -1;
        $maxY = -1;

        for ($y = 0; $y < $sh; $y++) {
            for ($x = 0; $x < $sw; $x++) {
                if (! $this->isInkPixel($source, $x, $y)) {
                    continue;
                }

                $minX = min($minX, $x);
                $minY = min($minY, $y);
                $maxX = max($maxX, $x);
                $maxY = max($maxY, $y);
            }
        }

        if ($maxX < $minX || $maxY < $minY) {
            $copy = imagecreatetruecolor($sw, $sh);
            if ($copy === false) {
                return null;
            }
            imagealphablending($copy, false);
            imagesavealpha($copy, true);
            $transparent = imagecolorallocatealpha($copy, 0, 0, 0, 127);
            imagefill($copy, 0, 0, $transparent);
            imagealphablending($copy, true);
            imagecopy($copy, $source, 0, 0, 0, 0, $sw, $sh);
            imagealphablending($copy, false);

            return $copy;
        }

        $pad = self::SIGNATURE_TRIM_PADDING;
        $minX = max(0, $minX - $pad);
        $minY = max(0, $minY - $pad);
        $maxX = min($sw - 1, $maxX + $pad);
        $maxY = min($sh - 1, $maxY + $pad);

        $dw = $maxX - $minX + 1;
        $dh = $maxY - $minY + 1;

        $dest = imagecreatetruecolor($dw, $dh);
        if ($dest === false) {
            return null;
        }

        imagealphablending($dest, false);
        imagesavealpha($dest, true);
        $transparent = imagecolorallocatealpha($dest, 0, 0, 0, 127);
        imagefill($dest, 0, 0, $transparent);
        imagealphablending($dest, true);
        imagecopy($dest, $source, 0, 0, $minX, $minY, $dw, $dh);
        imagealphablending($dest, false);

        return $dest;
    }

    private function isInkPixel(\GdImage $image, int $x, int $y): bool
    {
        $rgba = imagecolorat($image, $x, $y);
        $a = ($rgba & 0x7F000000) >> 24;
        $r = ($rgba >> 16) & 0xFF;
        $g = ($rgba >> 8) & 0xFF;
        $b = $rgba & 0xFF;

        // GD alpha: 0 = opaque, 127 = fully transparent
        if ($a >= 110) {
            return false;
        }

        // Treat near-white as padding (common for scanned / JPEG signatures)
        if ($r >= 245 && $g >= 245 && $b >= 245) {
            return false;
        }

        return true;
    }
}
