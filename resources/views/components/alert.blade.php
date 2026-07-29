@props([
    'tone' => 'info',
    'title' => null,
    'dismissible' => false,
])

@php
    $toneClass = match ($tone) {
        'success' => 'alert-banner-success',
        'warning' => 'alert-banner-warning',
        'danger', 'error' => 'alert-banner-danger',
        default => 'alert-banner-info',
    };
@endphp

<div
    {{ $attributes->class(['alert-banner', $toneClass]) }}
    role="alert"
    @if ($dismissible) x-data="{ show: true }" x-show="show" x-cloak @endif
>
    <div class="flex min-w-0 flex-1 items-start gap-3">
        @if ($title || $slot->isNotEmpty())
            <div class="min-w-0">
                @if ($title)
                    <p class="text-sm font-semibold">{{ $title }}</p>
                @endif
                @if ($slot->isNotEmpty())
                    <div @class(['text-xs opacity-90', 'mt-0.5' => (bool) $title])>{{ $slot }}</div>
                @endif
            </div>
        @endif
    </div>

    <div class="flex shrink-0 items-center gap-2">
        @isset($action)
            {{ $action }}
        @endisset
        @if ($dismissible)
            <button type="button" @click="show = false" class="rounded-md p-1 opacity-70 transition hover:opacity-100" aria-label="Dismiss">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        @endif
    </div>
</div>
