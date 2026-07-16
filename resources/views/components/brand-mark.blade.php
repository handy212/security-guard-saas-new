{{-- GuardCore Pro mark: shield with G monogram --}}
@props([
    'size' => 'md',
    'showWordmark' => false,
    'wordmarkClass' => 'text-sm font-semibold tracking-tight text-white',
    'tagline' => null,
    'taglineClass' => 'text-[11px] text-zinc-500',
])

@php
    $box = match ($size) {
        'sm' => 'h-7 w-7',
        'lg' => 'h-10 w-10',
        default => 'h-8 w-8',
    };
    $icon = match ($size) {
        'sm' => 'h-3.5 w-3.5',
        'lg' => 'h-5 w-5',
        default => 'h-4 w-4',
    };
@endphp

<div {{ $attributes->class(['flex min-w-0 items-center gap-2.5']) }}>
    <div
        class="{{ $box }} flex shrink-0 items-center justify-center rounded-md text-white"
        style="background-color: var(--tenant-brand, #0f766e)"
        aria-hidden="true"
    >
        <svg class="{{ $icon }}" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M12 2.5L4.5 5.5v5.2c0 5.1 3.4 9.7 7.5 11.3 4.1-1.6 7.5-6.2 7.5-11.3V5.5L12 2.5z" fill="currentColor" fill-opacity="0.22"/>
            <path d="M12 3.2L5.4 5.9v4.8c0 4.6 3 8.7 6.6 10.2 3.6-1.5 6.6-5.6 6.6-10.2V5.9L12 3.2z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
            <path d="M9.2 12.1c0-1.7 1.1-2.7 2.8-2.7 1.2 0 2 .5 2.4 1.3l-1.15.7c-.2-.4-.55-.7-1.2-.7-.7 0-1.15.45-1.15 1.15v1.55c0 .7.45 1.15 1.15 1.15.65 0 1-.3 1.2-.7l1.15.7c-.4.85-1.25 1.35-2.4 1.35-1.7 0-2.8-1.05-2.8-2.8v-1.1z" fill="currentColor"/>
        </svg>
    </div>
    @if ($showWordmark)
        <div class="min-w-0 leading-tight">
            <div class="truncate {{ $wordmarkClass }}">GuardCore Pro</div>
            @if ($tagline)
                <div class="truncate {{ $taglineClass }}">{{ $tagline }}</div>
            @endif
        </div>
    @endif
</div>
