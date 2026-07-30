<?php

namespace App\Services;

use App\Models\Guard;
use App\Support\ZipBuilder;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\View;
use Spatie\Browsershot\Browsershot;
use Symfony\Component\HttpFoundation\Response;

class GuardIdCardPdfService
{
    public function __construct(
        private GuardIdCardRenderService $renderer,
    ) {}

    /**
     * @param  array<string, mixed>  $viewData
     */
    public function downloadResponse(array $viewData, string $filename): Response
    {
        $pdf = $this->generate($viewData);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function downloadPngZipResponse(array $viewData, string $basename): Response
    {
        $zip = $this->generatePngZip($viewData, $basename);

        return response($zip, 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$basename.'.zip"',
        ]);
    }

    /**
     * Chromium/Browsershot work should run on the queue-heavy worker, not PHP-FPM.
     *
     * @param  array<string, mixed>  $viewData
     */
    public function requiresHeavyWorker(array $viewData, string $format = 'pdf'): bool
    {
        if ($format === 'png') {
            return true;
        }

        $orientation = $viewData['brand']['orientation'] ?? 'portrait';

        if ($orientation === 'landscape') {
            return true;
        }

        return config('id_card.pdf_driver') === 'browsershot';
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    public function generate(array $viewData): string
    {
        $orientation = $viewData['brand']['orientation'] ?? 'portrait';

        if (config('id_card.pdf_driver') === 'dompdf') {
            if ($orientation === 'landscape') {
                return $this->generateWithBrowsershot($viewData);
            }

            return $this->generateWithDompdf($viewData);
        }

        try {
            return $this->generateWithBrowsershot($viewData);
        } catch (\Throwable $e) {
            report($e);

            if ($orientation === 'landscape') {
                throw new \RuntimeException(
                    'Landscape ID card PDFs require Chrome + Node (Browsershot). Ensure the queue-heavy image includes chromium and nodejs, then rebuild: docker compose build queue-heavy && docker compose up -d queue-heavy',
                    0,
                    $e
                );
            }

            return $this->generateWithDompdf($viewData);
        }
    }

    /**
     * @param  array<string, mixed>  $viewData
     * @return array{front: string, back: string}
     */
    public function generatePngSides(array $viewData): array
    {
        return [
            'front' => $this->generateSidePng($viewData, 'front'),
            'back' => $this->generateSidePng($viewData, 'back'),
        ];
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    public function generatePngZip(array $viewData, string $basename): string
    {
        $sides = $this->generatePngSides($viewData);
        $zip = new ZipBuilder;
        $zip->add($basename.'-front.png', $sides['front']);
        $zip->add($basename.'-back.png', $sides['back']);

        return $zip->build();
    }

    /**
     * @param  Collection<int, Guard>  $guards
     */
    public function generateBulkZip(Collection $guards, string $format = 'pdf'): string
    {
        abort_unless(in_array($format, ['pdf', 'png'], true), 422, 'Format must be pdf or png.');

        $zip = new ZipBuilder;
        $errors = 0;
        $lastError = null;

        foreach ($guards as $guard) {
            $basename = $this->safeBasename($guard);

            try {
                $built = $this->renderer->forGuard($guard);

                try {
                    if ($format === 'png') {
                        $sides = $this->generatePngSides($built['viewData']);
                        $zip->add($basename.'-front.png', $sides['front']);
                        $zip->add($basename.'-back.png', $sides['back']);
                    } else {
                        $zip->add($basename.'.pdf', $this->generate($built['viewData']));
                    }
                } finally {
                    $this->renderer->cleanup($built['tempFiles']);
                }
            } catch (\Throwable $e) {
                report($e);
                $lastError = $e;
                $errors++;
            }
        }

        abort_if($zip->isEmpty(), 503, $errors > 0
            ? 'Could not generate any ID cards: '.($lastError?->getMessage() ?: 'Chromium/Node unavailable on queue-heavy.')
            : 'No verified guards with active QR tokens to download.');

        return $zip->build();
    }

    public function safeBasename(Guard $guard): string
    {
        $raw = $guard->employee_number ?: (string) $guard->id;

        return 'guard-id-'.preg_replace('/[^a-zA-Z0-9._-]+/', '-', $raw);
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function generateSidePng(array $viewData, string $side): string
    {
        $orientation = $viewData['brand']['orientation'] ?? 'portrait';
        $layout = app(GuardIdCardPresenter::class)->layout($orientation);
        $dpi = max(72, (int) config('id_card.png_dpi', 300));
        // CSS px at 96dpi for the physical card; deviceScaleFactor yields print DPI.
        $cssWidth = max(1, (int) round($layout['width_in'] * 96));
        $cssHeight = max(1, (int) round($layout['height_in'] * 96));
        $scaleFactor = round($dpi / 96, 4);

        $html = View::make('id-cards.side', array_merge(
            $this->renderer->browserPdfViewData($viewData),
            ['side' => $side],
        ))->render();

        $shot = Browsershot::html($html)
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->windowSize($cssWidth, $cssHeight)
            ->deviceScaleFactor($scaleFactor)
            ->setScreenshotType('png')
            ->timeout(120);

        $this->configureChrome($shot);

        return $shot->screenshot();
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function generateWithBrowsershot(array $viewData): string
    {
        $html = View::make('id-cards.document', $this->renderer->browserPdfViewData($viewData))->render();

        $orientation = $viewData['brand']['orientation'] ?? 'portrait';
        $layout = app(GuardIdCardPresenter::class)->layout($orientation);

        $shot = Browsershot::html($html)
            ->showBackground()
            ->margins(0, 0, 0, 0)
            ->paperSize($layout['width_mm'], $layout['height_mm'], 'mm')
            ->timeout(120);

        $this->configureChrome($shot);

        return $shot->pdf();
    }

    /**
     * @param  array<string, mixed>  $viewData
     */
    private function generateWithDompdf(array $viewData): string
    {
        $orientation = $viewData['brand']['orientation'] ?? 'portrait';
        $layout = app(GuardIdCardPresenter::class)->layout($orientation);
        $mmPerPt = 25.4 / 72;
        $widthPt = (int) round($layout['width_mm'] / $mmPerPt);
        $heightPt = (int) round($layout['height_mm'] / $mmPerPt);

        return Pdf::loadView('pdf.guard-id-card', $viewData)
            ->setPaper([0, 0, $widthPt, $heightPt])
            ->setOptions([
                'isRemoteEnabled' => false,
                'isHtml5ParserEnabled' => true,
                'defaultFont' => 'DejaVu Sans',
                'chroot' => realpath(base_path()) ?: base_path(),
            ])
            ->output();
    }

    private function configureChrome(Browsershot $shot): void
    {
        $chrome = config('id_card.chrome_path');
        if (is_string($chrome) && $chrome !== '' && is_executable($chrome)) {
            $shot->setChromePath($chrome);
        }

        $node = config('id_card.node_path');
        if (is_string($node) && $node !== '' && is_executable($node)) {
            $shot->setNodeBinary($node);
        }

        $npm = config('id_card.npm_path');
        if (is_string($npm) && $npm !== '' && is_executable($npm)) {
            $shot->setNpmBinary($npm);
        }

        // Prefer the project's node_modules so we don't need a global npm install of puppeteer.
        $modules = base_path('node_modules');
        if (is_dir($modules)) {
            $shot->setNodeModulePath($modules);
        }

        $shot->noSandbox()
            ->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']);
    }
}
