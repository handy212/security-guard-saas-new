@php
    $brandColor = $tenantBranding['color'] ?? $brandColor ?? '#0f766e';
    $defaults = $platformBranding->current();
    $tenantLight = $tenantBranding['theme']['light'] ?? null;
    $light = $tenantLight ?? $defaults['light_accent'];
    $dark = $tenantBranding['theme']['dark'] ?? $tenantLight ?? $defaults['dark_accent'];
@endphp
<style>
    :root {
        --tenant-brand: {{ $brandColor }};
        @if ($light)
            --tenant-accent: {{ $light }};
        @endif
        @if ($dark)
            --tenant-dark-accent: {{ $dark }};
        @endif
    }
</style>
