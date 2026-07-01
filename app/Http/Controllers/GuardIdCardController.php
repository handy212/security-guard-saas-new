<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Services\GuardIdCardPhotoService;
use App\Services\GuardIdCardPresenter;
use App\Services\GuardVerificationService;
use App\Services\QrCodeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class GuardIdCardController extends Controller
{
    /** CR80 landscape — 3.375in × 2.125in in PDF points. */
    private const CARD_WIDTH_PT = 243;

    private const CARD_HEIGHT_PT = 153;

    public function __invoke(
        Guard $guard,
        GuardVerificationService $verification,
        GuardIdCardPresenter $presenter,
        GuardIdCardPhotoService $photos,
        QrCodeService $qr,
    ): Response {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) auth()->user()->tenant_id, 404);
        abort_unless($guard->verification_status === 'verified', 403, 'Guard must be verified before downloading an ID card.');

        $guard->loadMissing(['tenant', 'branch']);

        $token = $guard->activeVerificationToken();
        abort_unless($token, 403, 'No active verification token. Regenerate QR from the guard profile.');
        $verifyUrl = $verification->verificationUrl($token);
        $qrSize = 128;
        $qrPath = $qr->pngFile($verifyUrl, $qrSize);
        $qrPng = $qrPath === null ? $qr->pngBase64($verifyUrl, $qrSize) : null;
        $photoPath = $photos->pngFile($guard->photo_path);

        try {
            $pdf = Pdf::loadView('pdf.guard-id-card', [
                'guard' => $guard,
                'brand' => $presenter->branding($guard->tenant, $guard->branch),
                'verifyUrl' => $verifyUrl,
                'verifyHost' => $this->displayHost($verifyUrl),
                'qrPath' => $qrPath,
                'qrPng' => $qrPng,
                'qrSize' => $qrSize,
                'photoPath' => $photoPath,
                'photoWidth' => $photos->widthPt(),
                'photoHeight' => $photos->heightPt(),
            ])
                ->setPaper([0, 0, self::CARD_WIDTH_PT, self::CARD_HEIGHT_PT])
                ->setOptions([
                    'isRemoteEnabled' => false,
                    'isHtml5ParserEnabled' => true,
                    'defaultFont' => 'DejaVu Sans',
                    'chroot' => realpath(base_path()) ?: base_path(),
                ]);

            $filename = 'guard-id-'.($guard->employee_number ?: $guard->id).'.pdf';

            return $pdf->download($filename);
        } finally {
            if ($qrPath && is_file($qrPath)) {
                @unlink($qrPath);
            }
            if ($photoPath && is_file($photoPath)) {
                @unlink($photoPath);
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
}
