<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $card['name'] }} — ID Card</title>
    @php
        $orientation = $brand['orientation'] ?? 'portrait';
        $presenter = app(\App\Services\GuardIdCardPresenter::class);
        $layout = $presenter->layout($orientation);
        $pdfScale = $presenter->printScale($orientation);
    @endphp
    @include('id-cards._styles', ['forPdf' => true])
    @if ($orientation === 'landscape')
        @include('id-cards._styles-landscape', ['forPdf' => true])
    @endif
    <style>
        @page {
            size: {{ $layout['width_mm'] }}mm {{ $layout['height_mm'] }}mm;
            margin: 0;
        }

        html, body {
            margin: 0;
            padding: 0;
        }

        .pdf-page {
            width: {{ $layout['width_mm'] }}mm;
            height: {{ $layout['height_mm'] }}mm;
            overflow: hidden;
            page-break-after: always;
        }

        .pdf-page:last-child {
            page-break-after: auto;
        }

        .id-card-scope {
            width: {{ $layout['width_mm'] }}mm;
            height: {{ $layout['height_mm'] }}mm;
            overflow: hidden;
        }

        .id-card-scale {
            width: {{ $layout['design_width_px'] }}px;
            height: {{ $layout['design_height_px'] }}px;
            transform: scale({{ $pdfScale }});
            transform-origin: top left;
        }

        .id-card-scope .id-card {
            box-shadow: none;
        }
    </style>
</head>
<body>
    <div class="pdf-page">
        <div class="id-card-scope">
            <div class="id-card-scale">
                @include('id-cards._card', [
                    'side' => 'front',
                    'forPdf' => true,
                    'brand' => $brand,
                    'card' => $card,
                    'photoSrc' => $photoSrc,
                    'logoSrc' => $logoSrc,
                    'qrPng' => $qrPng,
                ])
            </div>
        </div>
    </div>
    <div class="pdf-page">
        <div class="id-card-scope">
            <div class="id-card-scale">
                @include('id-cards._card', [
                    'side' => 'back',
                    'forPdf' => true,
                    'brand' => $brand,
                    'card' => $card,
                    'photoSrc' => $photoSrc,
                    'logoSrc' => $logoSrc,
                    'qrPng' => $qrPng,
                ])
            </div>
        </div>
    </div>
</body>
</html>
