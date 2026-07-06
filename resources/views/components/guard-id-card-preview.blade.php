@props([
    'brand',
    'card',
    'side' => 'front',
    'photoUrl' => null,
    'logoUrl' => null,
    'backLogoUrl' => null,
    'qrSvg' => null,
])

@php
    $orientation = $brand['orientation'] ?? 'portrait';
    if (($brand['template'] ?? 'modern') === 'premium') {
        $orientation = 'landscape';
    }
    $isLandscape = $orientation === 'landscape';
    $previewScale = app(\App\Services\GuardIdCardPresenter::class)->previewScale($orientation);
    $previewClasses = 'id-card-preview'.($isLandscape ? ' id-card-preview--landscape' : '');
@endphp

<div {{ $attributes->merge(['class' => $previewClasses]) }}>
    @include('id-cards._styles', ['forPdf' => false])
    @if ($isLandscape)
        @include('id-cards._styles-landscape', [
            'forPdf' => false,
            'forPreview' => true,
            'previewScale' => $previewScale,
        ])
        <style>
            .id-card-preview--landscape {
                width: 100%;
                max-width: 100%;
                display: flex;
                justify-content: center;
                margin: 0 auto;
            }

            .id-card-preview--landscape .orientation-landscape.id-card {
                box-shadow: 0 10px 24px rgba(0, 0, 0, 0.12);
            }
        </style>
    @endif
    @include('id-cards._card', [
        'side' => $side,
        'forPdf' => false,
        'brand' => $brand,
        'card' => $card,
        'photoUrl' => $photoUrl,
        'logoUrl' => $logoUrl ?? ($brand['logo_url'] ?? null),
        'backLogoUrl' => $backLogoUrl ?? ($brand['back_logo_url'] ?? null),
        'qrSvg' => $qrSvg,
    ])
</div>
