@props(['title' => null, 'description' => null, 'breadcrumbs' => [], 'showHeader' => true])

@php
    // Hide single-item breadcrumbs that just repeat the page context
    $crumbTrail = collect($breadcrumbs)->filter(fn ($c) => ! empty($c['label']))->values();
    $showCrumbs = $crumbTrail->count() > 1 || ($crumbTrail->count() === 1 && ! empty($crumbTrail[0]['href']));
@endphp

<div class="flex min-h-full flex-col">
    @if ($showHeader)
        <div class="border-b border-zinc-200/80 dark:border-zinc-800">
            <div class="page-content flex flex-col gap-3 py-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    @if ($showCrumbs)
                        <nav class="mb-1 flex flex-wrap items-center gap-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                            @foreach ($crumbTrail as $crumb)
                                @if (! empty($crumb['href']))
                                    <a href="{{ $crumb['href'] }}" class="hover:text-zinc-800 dark:hover:text-zinc-200">{{ $crumb['label'] }}</a>
                                    @if (! $loop->last)<span class="text-zinc-300 dark:text-zinc-600">/</span>@endif
                                @else
                                    <span class="text-zinc-600 dark:text-zinc-300">{{ $crumb['label'] }}</span>
                                    @if (! $loop->last)<span class="text-zinc-300 dark:text-zinc-600">/</span>@endif
                                @endif
                            @endforeach
                        </nav>
                    @endif
                    <h1 class="truncate text-xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $title }}</h1>
                    @if ($description)
                        <p class="mt-0.5 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                    @endif
                </div>
                @if (isset($actions))
                    <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="page-content flex-1 space-y-3.5 pb-8 pt-3.5">
        {{ $slot }}
    </div>
</div>
