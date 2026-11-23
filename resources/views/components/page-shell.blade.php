@props(['title' => null, 'description' => null, 'breadcrumbs' => [], 'showHeader' => true, 'embedded' => false, 'compact' => false])

@php
    // Hide single-item breadcrumbs that just repeat the page context
    $crumbTrail = collect($breadcrumbs)->filter(fn ($c) => ! empty($c['label']))->values();
    $showCrumbs = $crumbTrail->count() > 1 || ($crumbTrail->count() === 1 && ! empty($crumbTrail[0]['href']));
@endphp

<div @class(['flex min-h-full flex-col', 'scheduler-panel' => $embedded])>
    @if ($showHeader)
        <div class="border-b border-zinc-200/70 bg-white/70 dark:border-zinc-800 dark:bg-transparent">
            <div @class(['page-content flex flex-wrap justify-between', 'items-center gap-x-4 gap-y-2 py-2.5' => $compact, 'flex-col gap-4 py-5 sm:flex-row sm:items-end sm:gap-6' => ! $compact])>
                <div class="flex min-w-0 items-center gap-3">
                    @isset($headingIcon)
                        {{ $headingIcon }}
                    @endisset
                    <div class="min-w-0 space-y-1.5">
                        @if ($showCrumbs && ! $embedded)
                            <nav class="flex flex-wrap items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                                @foreach ($crumbTrail as $crumb)
                                    @if (! empty($crumb['href']))
                                        <a href="{{ $crumb['href'] }}" class="transition hover:text-accent-700 dark:hover:text-accent-300">{{ $crumb['label'] }}</a>
                                        @if (! $loop->last)<span class="text-zinc-300 dark:text-zinc-600">/</span>@endif
                                    @else
                                        <span class="font-medium text-zinc-700 dark:text-zinc-200">{{ $crumb['label'] }}</span>
                                        @if (! $loop->last)<span class="text-zinc-300 dark:text-zinc-600">/</span>@endif
                                    @endif
                                @endforeach
                            </nav>
                        @endif
                        @if ($embedded)
                            <h2 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $title }}</h2>
                        @else
                            <h1 class="truncate text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-[1.75rem]">{{ $title }}</h1>
                        @endif
                        @if ($description)
                            <p class="max-w-2xl text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                        @endif
                    </div>
                </div>
                @if (isset($actions))
                    <div @class(['flex flex-wrap items-center gap-2.5', 'min-w-0' => $compact, 'shrink-0' => ! $compact])>{{ $actions }}</div>
                @endif
            </div>
        </div>
    @endif

    <div @class(['page-content flex-1', 'space-y-2.5 pb-8 pt-2.5' => $compact, 'space-y-5 pb-12 pt-5' => ! $compact])>
        {{ $slot }}
    </div>
</div>
