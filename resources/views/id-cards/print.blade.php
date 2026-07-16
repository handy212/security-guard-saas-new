<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $card['name'] }} — Print ID Card</title>
    @php
        $orientation = $brand['orientation'] ?? 'portrait';
        $presenter = app(\App\Services\GuardIdCardPresenter::class);
        $layout = $presenter->layout($orientation);
        $cardWidthMm = $layout['width_mm'];
        $cardHeightMm = $layout['height_mm'];
        $designWidthPx = $layout['design_width_px'];
        $designHeightPx = $layout['design_height_px'];
        $printScale = $presenter->printScale($orientation);
        $screenScale = round($printScale * 2, 6);
    @endphp
    @include('id-cards._styles', ['forPdf' => false])
    @if ($orientation === 'landscape')
        @include('id-cards._styles-landscape', ['forPdf' => false])
    @endif
    <style>
        * { box-sizing: border-box; }

        body {
            margin: 0;
            font-family: "IBM Plex Sans", ui-sans-serif, system-ui, sans-serif;
            background: #f4f4f5;
            color: #0f172a;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        .print-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px;
            background: #fff;
            border-bottom: 1px solid #e4e4e7;
        }

        .print-toolbar button {
            appearance: none;
            border: 0;
            border-radius: 8px;
            background: #18181b;
            color: #fff;
            font-size: 14px;
            font-weight: 600;
            padding: 10px 18px;
            cursor: pointer;
        }

        .print-toolbar button.secondary {
            background: #fff;
            color: #18181b;
            border: 1px solid #d4d4d8;
        }

        .print-toolbar p {
            margin: 0;
            flex-basis: 100%;
            text-align: center;
            font-size: 13px;
            color: #71717a;
        }

        .print-stage {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 24px;
            padding: 24px;
        }

        .print-sheet {
            width: {{ $cardWidthMm * 2 }}mm;
            height: {{ $cardHeightMm * 2 }}mm;
            overflow: hidden;
            background: #fff;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
            position: relative;
        }

        .card-fit {
            width: {{ $designWidthPx }}px;
            height: {{ $designHeightPx }}px;
            transform: scale({{ $screenScale }});
            transform-origin: top left;
        }

        .id-card-preview .id-card {
            box-shadow: none;
        }

        @page {
            size: {{ $cardWidthMm }}mm {{ $cardHeightMm }}mm;
            margin: 0;
        }

        @media print {
            body {
                background: #fff;
            }

            .no-print {
                display: none !important;
            }

            .print-stage {
                padding: 0;
                gap: 0;
            }

            .print-sheet {
                width: {{ $cardWidthMm }}mm;
                height: {{ $cardHeightMm }}mm;
                box-shadow: none;
                page-break-after: always;
                break-after: page;
            }

            .print-sheet:last-child {
                page-break-after: auto;
                break-after: auto;
            }

            .card-fit {
                transform: scale({{ $printScale }});
            }
        }
    </style>
</head>
<body>
    <div class="print-toolbar no-print">
        <button type="button" onclick="window.print()">Print ID card</button>
        <button type="button" class="secondary" onclick="window.close()">Close</button>
        <p>
            <strong>{{ ucfirst($orientation) }}</strong> CR80 —
            set paper to <strong>{{ $cardWidthMm }} × {{ $cardHeightMm }} mm</strong>.
            Page 1 = front, page 2 = back.
        </p>
    </div>

    <div class="print-stage">
        <div class="print-sheet">
            <div class="card-fit">
                <div class="id-card-preview">
                    @include('id-cards._card', [
                        'side' => 'front',
                        'forPdf' => false,
                        'brand' => $brand,
                        'card' => $card,
                        'photoUrl' => $photoUrl,
                        'logoUrl' => $logoUrl ?? ($brand['logo_url'] ?? null),
                        'qrSvg' => $qrSvg,
                    ])
                </div>
            </div>
        </div>

        <div class="print-sheet">
            <div class="card-fit">
                <div class="id-card-preview">
                    @include('id-cards._card', [
                        'side' => 'back',
                        'forPdf' => false,
                        'brand' => $brand,
                        'card' => $card,
                        'photoUrl' => $photoUrl,
                        'logoUrl' => $logoUrl ?? ($brand['logo_url'] ?? null),
                        'qrSvg' => $qrSvg,
                    ])
                </div>
            </div>
        </div>
    </div>
</body>
</html>
