@props(['align' => 'left', 'responsive' => null])

@php
    $alignClass = match ($align) {
        'right' => 'text-right',
        'center' => 'text-center',
        default => 'text-left',
    };
    $responsiveClass = match ($responsive) {
        'md' => 'hidden md:table-cell',
        'lg' => 'hidden lg:table-cell',
        'xl' => 'hidden xl:table-cell',
        default => '',
    };
@endphp

<th {{ $attributes->merge(['class' => trim("table-th {$alignClass} {$responsiveClass}")]) }}>
    {{ $slot }}
</th>
