<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
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
                    'Landscape ID card PDFs require Chrome (Browsershot). Set ID_CARD_PDF_DRIVER=browsershot and install Chromium.',
                    0,
                    $e
                );
            }

            return $this->generateWithDompdf($viewData);
        }
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

        $chrome = config('id_card.chrome_path');
        if ($chrome && is_executable($chrome)) {
            $shot->setChromePath($chrome);
        }

        $shot->setOption('args', ['--no-sandbox', '--disable-setuid-sandbox', '--disable-dev-shm-usage']);

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
}
