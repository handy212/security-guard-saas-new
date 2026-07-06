<?php

namespace App\Services;

use App\Models\Guard;
use App\Models\GuardVerificationToken;
use App\Models\Tenant;

class GuardIdCardRenderService
{
    public function __construct(
        private GuardIdCardPresenter $presenter,
        private GuardIdCardPhotoService $photos,
        private GuardIdCardLogoService $logos,
        private GuardVerificationService $verification,
        private QrCodeService $qr,
    ) {}

    /**
     * @return array{viewData: array<string, mixed>, tempFiles: string[]}
     */
    public function forGuard(Guard $guard): array
    {
        $guard->loadMissing(['tenant', 'branch']);

        $token = $guard->activeVerificationToken();
        abort_unless($token, 403, 'No active verification token.');

        $brand = $this->presenter->branding($guard->tenant, $guard->branch);
        $card = $this->presenter->cardData($guard, $token);

        $verifyUrl = $this->verification->verificationUrl($token);
        $qrSize = 128;
        $qrPath = $this->qr->pngFile($verifyUrl, $qrSize);
        $qrPng = $this->qrPngPayload($qrPath, $verifyUrl, $qrSize);

        if ($qrPath === null && ($qrPng === null || $qrPng === '')) {
            abort(503, 'Could not generate the QR code for this ID card.');
        }

        $photoStyle = $this->photoStyle($brand);
        $photoPath = $this->photos->pngFile($guard->photo_path, $photoStyle)
            ?? $this->photos->initialsFile($card['initial'], $photoStyle);
        $logoPath = $this->logos->pngFile($brand['logo_path']);
        $backLogoPath = $this->logos->pngFile($brand['back_logo_path'] ?? null);

        $tempFiles = array_values(array_filter([$qrPath, $photoPath, $logoPath, $backLogoPath]));

        return [
            'viewData' => [
                'guard' => $guard,
                'brand' => $brand,
                'card' => $card,
                'verifyUrl' => $verifyUrl,
                'verifyHost' => $this->displayHost($verifyUrl),
                'qrPath' => $qrPath,
                'qrPng' => $qrPng,
                'qrSize' => $qrSize,
                'photoPath' => $photoPath,
                'photoUrl' => $guard->photo_path ? route('files.guard-photo', $guard) : null,
                'photoWidth' => $this->photos->widthPt($photoStyle),
                'photoHeight' => $this->photos->heightPt($photoStyle),
                'logoPath' => $logoPath,
                'logoHeight' => $this->logos->heightPt(),
                'logoUrl' => $brand['logo_url'],
            ],
            'tempFiles' => $tempFiles,
        ];
    }

    /**
     * @param  array<string, mixed>  $brandOverrides
     * @return array<string, mixed>
     */
    public function forSample(Tenant $tenant, array $brandOverrides = []): array
    {
        $brand = $this->presenter->brandingWithOverrides($tenant, $brandOverrides);
        $card = $this->presenter->sampleCardData();

        $verifyUrl = url('/g/SAMPLE');
        $qrSize = 96;
        $qrPng = $this->qr->pngBase64($verifyUrl, $qrSize);

        return [
            'brand' => $brand,
            'card' => $card,
            'verifyUrl' => $verifyUrl,
            'qrSvg' => $this->qr->svg($verifyUrl, 56),
            'qrPng' => $qrPng,
            'qrSize' => $qrSize,
            'photoUrl' => null,
            'logoUrl' => $brand['logo_url'] ?? null,
        ];
    }

    /**
     * Embed images as data URIs so PDF HTML is self-contained (Browsershot).
     *
     * @param  array<string, mixed>  $viewData
     * @return array<string, mixed>
     */
    public function browserPdfViewData(array $viewData): array
    {
        $guard = $viewData['guard'];
        $card = $viewData['card'];
        $brand = $viewData['brand'];
        $photoPath = $guard->photo_path ?? null;
        $photoStyle = $this->photoStyle($brand);

        $photoSrc = $this->photos->dataUri($photoPath, $photoStyle)
            ?? $this->photos->initialsDataUri($card['initial'], $photoStyle);

        $logoSrc = ! empty($brand['logo_path'])
            ? $this->logos->dataUri($brand['logo_path'])
            : null;

        $backLogoSrc = ! empty($brand['back_logo_path'])
            ? $this->logos->dataUri($brand['back_logo_path'])
            : null;

        $qrPng = $this->qrPngPayload(
            $viewData['qrPath'] ?? null,
            $viewData['verifyUrl'] ?? '',
            (int) ($viewData['qrSize'] ?? 128),
            $viewData['qrPng'] ?? null,
        );

        return array_merge($viewData, [
            'forPdf' => true,
            'photoSrc' => $photoSrc,
            'logoSrc' => $logoSrc,
            'backLogoSrc' => $backLogoSrc,
            'photoUrl' => null,
            'logoUrl' => null,
            'qrSvg' => null,
            'qrPng' => $qrPng,
        ]);
    }

    private function qrPngPayload(?string $qrPath, string $verifyUrl, int $size, ?string $qrPng = null): ?string
    {
        if (is_string($qrPng) && $qrPng !== '') {
            return $qrPng;
        }

        if (is_string($qrPath) && is_file($qrPath)) {
            $binary = file_get_contents($qrPath);

            return $binary !== false && $binary !== '' ? base64_encode($binary) : null;
        }

        $encoded = $this->qr->pngBase64($verifyUrl, $size);

        return $encoded !== '' ? $encoded : null;
    }

    public function cleanup(array $tempFiles): void
    {
        foreach ($tempFiles as $file) {
            if (is_string($file) && is_file($file)) {
                @unlink($file);
            }
        }
    }

    private function displayHost(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        if (! $host || in_array($host, ['localhost', '127.0.0.1'], true)) {
            $appHost = parse_url(config('app.url'), PHP_URL_HOST);

            return $appHost && ! in_array($appHost, ['localhost', '127.0.0.1'], true) ? $appHost : null;
        }

        return $host;
    }

    /**
     * @param  array<string, mixed>  $brand
     */
    private function photoStyle(array $brand): string
    {
        return ($brand['template'] ?? 'modern') === 'premium' ? 'rectangular' : 'circular';
    }
}
