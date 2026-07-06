@props(['title', 'description' => null, 'width' => 'lg', 'closeMethod' => 'closeModal'])

@php
    $widthClass = match ($width) {
        'sm' => 'max-w-sm',
        'md' => 'max-w-md',
        'xl' => 'max-w-5xl',
        default => 'max-w-3xl',
    };
@endphp

<div class="fixed inset-0 z-[60] flex items-center justify-center p-4" role="dialog" aria-modal="true">
    <div
        class="absolute inset-0 bg-zinc-900/50 backdrop-blur-[1px]"
        wire:click="{{ $closeMethod }}"
    ></div>

    <div class="relative flex max-h-[90vh] w-full {{ $widthClass }} flex-col overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-2xl">
        <div class="flex shrink-0 items-start justify-between border-b border-zinc-100 px-5 py-4">
            <div class="min-w-0 pr-4">
                <h2 class="text-base font-semibold text-zinc-900">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $description }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $closeMethod }}" class="rounded-md p-1.5 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700" aria-label="Close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="min-h-0 flex-1 overflow-auto">
            {{ $slot }}
        </div>
    </div>
</div>
