{{-- Shared font + tenant-driven accent. Include in every HTML layout head. --}}
@php
    $brandColor = $tenantBranding['color'] ?? '#0f766e';
@endphp
<style>
    :root {
        --tenant-brand: {{ $brandColor }};
    }
</style>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
