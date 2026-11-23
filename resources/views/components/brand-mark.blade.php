@props([
    'size' => 'md',
    'showWordmark' => false,
    'wordmarkClass' => 'text-sm font-semibold tracking-tight text-white',
    'tagline' => null,
    'taglineClass' => 'text-[11px] text-zinc-500',
    'logoOverride' => false,
])

@php
    $logo = ($logoOverride === false ? $platformBranding->logoUrl() : $logoOverride) ?? asset('icons/icon-192.png');
    $box = match ($size) {
        'sm' => 'h-7 w-7',
        'lg' => 'h-10 w-10',
        default => 'h-8 w-8',
    };
@endphp

<div {{ $attributes->class(['flex min-w-0 items-center gap-2.5']) }}>
    <div
        class="{{ $box }} flex shrink-0 items-center justify-center overflow-hidden rounded-md bg-white p-0.5"
        aria-hidden="true"
    >
        <img src="{{ $logo }}" alt="" class="size-full object-contain">
    </div>
    @if ($showWordmark)
        <div class="min-w-0 leading-tight">
            <div class="truncate {{ $wordmarkClass }}">{{ $platformBranding->name() }}</div>
            @if ($tagline)
                <div class="truncate {{ $taglineClass }}">{{ $tagline }}</div>
            @endif
        </div>
    @endif
</div>
