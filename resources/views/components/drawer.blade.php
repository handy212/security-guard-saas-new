@props(['title', 'description' => null, 'width' => 'md', 'closeMethod' => 'closeDrawer'])

@php
    $widthClass = match($width) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-3xl',
        default => 'max-w-md',
    };
@endphp

<div class="fixed inset-0 z-[60] flex justify-end" role="dialog" aria-modal="true">
    <div class="absolute inset-0 bg-zinc-900/50" wire:click="{{ $closeMethod }}"></div>

    <div class="relative flex h-full w-full {{ $widthClass }} flex-col border-l border-zinc-200 bg-white shadow-2xl">
        <div class="flex items-start justify-between border-b border-zinc-100 px-5 py-4">
            <div>
                <h2 class="text-base font-semibold text-zinc-900">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-0.5 text-sm text-zinc-500">{{ $description }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $closeMethod }}" class="rounded-md p-1.5 text-zinc-400 hover:bg-zinc-100 hover:text-zinc-700" aria-label="Close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto px-5 py-4">
            {{ $slot }}
        </div>
    </div>
</div>
