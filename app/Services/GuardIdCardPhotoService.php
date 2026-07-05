<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class GuardIdCardPhotoService
{
    /** Render size in pixels (@96dpi, ~1.33× PDF points). */
    private const WIDTH = 96;

    private const HEIGHT = 201;

    private const RADIUS = 20;

    private const BORDER = 2;

    private const CIRCLE_SIZE = 118;

    private const CIRCLE_BORDER = 4;

    public function dataUri(?string $path, string $style = 'rounded'): ?string
    {
        $binary = $this->pngBinary($path, $style);

        return $binary !== null ? 'data:image/png;base64,'.base64_encode($binary) : null;
    }

    /**
     * @return string|null Absolute path to PNG (within DomPDF chroot)
     */
    public function pngFile(?string $path, string $style = 'circular'): ?string
    {
        $binary = $this->pngBinary($path, $style);
        if ($binary === null) {
            return null;
        }

        return $this->writeTempPng($binary);
    }

    public function initialsFile(string $initial): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $binary = $this->initialsPng(strtoupper(substr($initial, 0, 1)));
        if ($binary === null) {
            return null;
        }

        return $this->writeTempPng($binary);
    }

    public function initialsDataUri(string $initial): ?string
    {
        if (! extension_loaded('gd')) {
            return null;
        }

        $binary = $this->initialsPng(strtoupper(substr($initial, 0, 1)));

        return $binary !== null ? 'data:image/png;base64,'.base64_encode($binary) : null;
    }

    private function writeTempPng(string $binary): ?string
    {
        $dir = storage_path('app/temp');
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return null;
        }

        $file = $dir.'/guard-photo-'.uniqid('', true).'.png';
        if (@file_put_contents($file, $binary) === false) {
            return null;
        }

        return $file;
    }

    public function widthPt(string $style = 'rounded'): int
    {
        return $style === 'circular' ? 59 : 72;
    }

    public function heightPt(string $style = 'rounded'): int
    {
        return $style === 'circular' ? 59 : 151;
    }

    private function pngBinary(?string $path, string $style = 'rounded'): ?string
    {
        if (! $path || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        if (! extension_loaded('gd')) {
            $absolute = Storage::disk('public')->path($path);

            return is_readable($absolute) ? (string) file_get_contents($absolute) : null;
        }

        $absolute = Storage::disk('public')->path($path);
        $source = $this->loadImage($absolute);

        if ($source === null) {
            return is_readable($absolute) ? (string) file_get_contents($absolute) : null;
        }

        $framed = $style === 'circular'
            ? $this->frameCircular($source)
            : $this->frameWithRoundedTop($source);
        imagedestroy($source);

        if ($framed === null) {
            return null;
        }

        ob_start();
        imagepng($framed);
        $png = (string) ob_get_clean();
        imagedestroy($framed);

        return $png;
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
            default => null,
        };
    }

    private function frameCircular(\GdImage $source): ?\GdImage
    {
        $size = self::CIRCLE_SIZE;
        $border = self::CIRCLE_BORDER;
        $total = $size + ($border * 2);

        $canvas = imagecreatetruecolor($total, $total);
        if ($canvas === false) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledellipse($canvas, (int) ($total / 2), (int) ($total / 2), $total - 1, $total - 1, $white);

        $photo = imagecreatetruecolor($size, $size);
        if ($photo === false) {
            imagedestroy($canvas);

            return null;
        }

        $this->copyCover($source, $photo, $size, $size);
        $masked = $this->applyCircleMask($photo, $size);
        imagedestroy($photo);

        if ($masked === null) {
            imagedestroy($canvas);

            return null;
        }

        imagecopy($canvas, $masked, $border, $border, 0, 0, $size, $size);
        imagedestroy($masked);

        imagealphablending($canvas, true);

        return $canvas;
    }

    private function applyCircleMask(\GdImage $image, int $size): ?\GdImage
    {
        $mask = imagecreatetruecolor($size, $size);
        if ($mask === false) {
            return null;
        }

        $black = imagecolorallocate($mask, 0, 0, 0);
        $white = imagecolorallocate($mask, 255, 255, 255);
        imagefill($mask, 0, 0, $black);
        imagefilledellipse($mask, (int) ($size / 2), (int) ($size / 2), $size - 1, $size - 1, $white);

        $output = imagecreatetruecolor($size, $size);
        if ($output === false) {
            imagedestroy($mask);

            return null;
        }

        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transparent);

        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                if ((imagecolorat($mask, $x, $y) & 0xFF) > 127) {
                    imagesetpixel($output, $x, $y, imagecolorat($image, $x, $y));
                }
            }
        }

        imagedestroy($mask);

        return $output;
    }

    private function frameWithRoundedTop(\GdImage $source): ?\GdImage
    {
        $w = self::WIDTH;
        $h = self::HEIGHT;
        $radius = self::RADIUS;
        $border = self::BORDER;
        $innerW = $w - ($border * 2);
        $innerH = $h;

        $canvas = imagecreatetruecolor($w, $h);
        if ($canvas === false) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $photo = imagecreatetruecolor($innerW, $innerH);
        if ($photo === false) {
            imagedestroy($canvas);

            return null;
        }

        $this->copyCover($source, $photo, $innerW, $innerH);

        $masked = $this->applyTopRoundMask($photo, $innerW, $innerH, $radius);
        imagedestroy($photo);

        if ($masked === null) {
            imagedestroy($canvas);

            return null;
        }

        imagecopy($canvas, $masked, $border, 0, 0, 0, $innerW, $innerH);
        imagedestroy($masked);

        $black = imagecolorallocate($canvas, 17, 17, 17);
        $this->drawTopRoundBorder($canvas, $border, $innerW, $h, $radius, $black, $border);

        imagealphablending($canvas, true);

        return $canvas;
    }

    private function copyCover(\GdImage $source, \GdImage $dest, int $dw, int $dh): void
    {
        $sw = imagesx($source);
        $sh = imagesy($source);
        $scale = max($dw / $sw, $dh / $sh);
        $cw = (int) round($sw * $scale);
        $ch = (int) round($sh * $scale);
        $sx = (int) round(($cw - $dw) / 2);
        $sy = (int) round(($ch - $dh) / 2);

        $tmp = imagecreatetruecolor($cw, $ch);
        imagecopyresampled($tmp, $source, 0, 0, 0, 0, $cw, $ch, $sw, $sh);
        imagecopy($dest, $tmp, 0, 0, $sx, $sy, $dw, $dh);
        imagedestroy($tmp);
    }

    private function applyTopRoundMask(\GdImage $image, int $w, int $h, int $radius): ?\GdImage
    {
        $mask = imagecreatetruecolor($w, $h);
        if ($mask === false) {
            return null;
        }

        $black = imagecolorallocate($mask, 0, 0, 0);
        $white = imagecolorallocate($mask, 255, 255, 255);
        imagefill($mask, 0, 0, $black);
        imagefilledrectangle($mask, 0, $radius, $w - 1, $h - 1, $white);
        imagefilledellipse($mask, $radius, $radius, $radius * 2 - 1, $radius * 2 - 1, $white);
        imagefilledellipse($mask, $w - $radius, $radius, $radius * 2 - 1, $radius * 2 - 1, $white);
        imagefilledrectangle($mask, $radius, 0, $w - $radius, $radius, $white);

        $output = imagecreatetruecolor($w, $h);
        if ($output === false) {
            imagedestroy($mask);

            return null;
        }

        imagealphablending($output, false);
        imagesavealpha($output, true);
        $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
        imagefill($output, 0, 0, $transparent);

        for ($y = 0; $y < $h; $y++) {
            for ($x = 0; $x < $w; $x++) {
                if ((imagecolorat($mask, $x, $y) & 0xFF) > 127) {
                    imagesetpixel($output, $x, $y, imagecolorat($image, $x, $y));
                }
            }
        }

        imagedestroy($mask);

        return $output;
    }

    private function drawTopRoundBorder(
        \GdImage $canvas,
        int $offsetX,
        int $innerW,
        int $innerH,
        int $radius,
        int $color,
        int $thickness,
    ): void {
        if (function_exists('imageantialias')) {
            imageantialias($canvas, true);
        }

        imagesetthickness($canvas, $thickness);

        $x1 = $offsetX;
        $x2 = $offsetX + $innerW - 1;
        $yTop = (int) floor($thickness / 2);
        $yBottom = $innerH - 1;

        imageline($canvas, $x1 + $radius, $yTop, $x2 - $radius, $yTop, $color);
        imageline($canvas, $x1, $yTop + $radius, $x1, $yBottom, $color);
        imageline($canvas, $x2, $yTop + $radius, $x2, $yBottom, $color);
        imagearc($canvas, $x1 + $radius, $yTop + $radius, $radius * 2, $radius * 2, 180, 270, $color);
        imagearc($canvas, $x2 - $radius, $yTop + $radius, $radius * 2, $radius * 2, 270, 360, $color);

        imagesetthickness($canvas, 1);
    }

    private function initialsPng(string $initial): ?string
    {
        $size = self::CIRCLE_SIZE;
        $border = self::CIRCLE_BORDER;
        $total = $size + ($border * 2);

        $canvas = imagecreatetruecolor($total, $total);
        if ($canvas === false) {
            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefill($canvas, 0, 0, $transparent);

        $white = imagecolorallocate($canvas, 255, 255, 255);
        imagefilledellipse($canvas, (int) ($total / 2), (int) ($total / 2), $total - 1, $total - 1, $white);

        $fill = imagecreatetruecolor($size, $size);
        if ($fill === false) {
            imagedestroy($canvas);

            return null;
        }

        $bg = imagecolorallocate($fill, 226, 232, 240);
        imagefill($fill, 0, 0, $bg);
        $textColor = imagecolorallocate($fill, 100, 116, 139);
        $font = 5;
        $tw = imagefontwidth($font) * strlen($initial);
        $th = imagefontheight($font);
        imagestring($fill, $font, (int) (($size - $tw) / 2), (int) (($size - $th) / 2), $initial, $textColor);

        $masked = $this->applyCircleMask($fill, $size);
        imagedestroy($fill);

        if ($masked === null) {
            imagedestroy($canvas);

            return null;
        }

        imagecopy($canvas, $masked, $border, $border, 0, 0, $size, $size);
        imagedestroy($masked);
        imagealphablending($canvas, true);

        ob_start();
        imagepng($canvas);
        $png = (string) ob_get_clean();
        imagedestroy($canvas);

        return $png !== '' ? $png : null;
    }
}
