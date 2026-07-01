<?php

namespace App\Services;

use BaconQrCode\Common\ErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class QrCodeService
{
    public function svg(string $content, int $size = 120): string
    {
        return $this->writeSvg($content, $size);
    }

    /** DomPDF cannot render inline SVG reliably — use PNG for PDFs. */
    public function pngBase64(string $content, int $size = 120): string
    {
        $binary = $this->pngBinary($content, $size);

        return $binary !== '' ? base64_encode($binary) : '';
    }

    /**
     * Write PNG to storage (within DomPDF chroot) — most reliable for PDF embedding.
     *
     * @return string|null Absolute path to the PNG file
     */
    public function pngFile(string $content, int $size = 120): ?string
    {
        $binary = $this->pngBinary($content, $size);
        if ($binary === '') {
            return null;
        }

        $dir = storage_path('app/temp');
        if (! $this->ensureWritableDirectory($dir)) {
            return null;
        }

        $path = $dir.'/qr-'.bin2hex(random_bytes(8)).'.png';
        if (@file_put_contents($path, $binary) === false) {
            return null;
        }

        return $path;
    }

    private function ensureWritableDirectory(string $dir): bool
    {
        if (! is_dir($dir) && ! @mkdir($dir, 0775, true) && ! is_dir($dir)) {
            return false;
        }

        if (is_writable($dir)) {
            return true;
        }

        @chmod($dir, 0775);

        return is_writable($dir);
    }

    private function pngBinary(string $content, int $size): string
    {
        if (! extension_loaded('gd')) {
            return '';
        }

        $matrix = Encoder::encode($content, ErrorCorrectionLevel::M())->getMatrix();
        $modules = $matrix->getWidth();
        $quiet = 2;
        $grid = $modules + ($quiet * 2);
        $modulePx = max(3, (int) floor($size / $grid));
        $imgSize = $grid * $modulePx;

        $image = imagecreatetruecolor($imgSize, $imgSize);
        if ($image === false) {
            return '';
        }

        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        imagefill($image, 0, 0, $white);

        for ($y = 0; $y < $modules; $y++) {
            for ($x = 0; $x < $modules; $x++) {
                if ($matrix->get($x, $y) !== 1) {
                    continue;
                }

                $left = ($x + $quiet) * $modulePx;
                $top = ($y + $quiet) * $modulePx;
                imagefilledrectangle(
                    $image,
                    $left,
                    $top,
                    $left + $modulePx - 1,
                    $top + $modulePx - 1,
                    $black
                );
            }
        }

        ob_start();
        imagepng($image);
        imagedestroy($image);

        return (string) ob_get_clean();
    }

    private function writeSvg(string $content, int $size): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 1),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($content);
    }
}
