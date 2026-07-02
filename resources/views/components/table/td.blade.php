@props(['align' => 'left', 'responsive' => null, 'muted' => false, 'mono' => false])

@php
    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => '',
    };
    $responsiveClass = match ($responsive) {
        'md' => 'hidden md:table-cell',
        'lg' => 'hidden lg:table-cell',
        'xl' => 'hidden xl:table-cell',
        default => '',
    };
    $toneClass = $muted ? 'text-zinc-600' : ($mono ? 'font-mono text-xs text-zinc-600' : '');
@endphp

<td {{ $attributes->merge(['class' => trim("table-td {$alignClass} {$responsiveClass} {$toneClass}")]) }}>
    {{ $slot }}
</td>
