@props(['title' => null, 'description' => null, 'breadcrumbs' => [], 'showHeader' => true])

@php
    // Hide single-item breadcrumbs that just repeat the page context
    $crumbTrail = collect($breadcrumbs)->filter(fn ($c) => ! empty($c['label']))->values();
    $showCrumbs = $crumbTrail->count() > 1 || ($crumbTrail->count() === 1 && ! empty($crumbTrail[0]['href']));
@endphp

<div class="flex min-h-full flex-col">
    @if ($showHeader)
        <div class="border-b border-zinc-200/70 bg-white/70 dark:border-zinc-800 dark:bg-transparent">
            <div class="page-content flex flex-col gap-4 py-5 sm:flex-row sm:items-end sm:justify-between sm:gap-6">
                <div class="min-w-0 space-y-1.5">
                    @if ($showCrumbs)
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
                    <h1 class="truncate text-2xl font-semibold tracking-tight text-zinc-900 dark:text-zinc-50 sm:text-[1.75rem]">{{ $title }}</h1>
                    @if ($description)
                        <p class="max-w-2xl text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                    @endif
                </div>
                @if (isset($actions))
                    <div class="flex shrink-0 flex-wrap items-center gap-2.5">{{ $actions }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="page-content flex-1 space-y-5 pb-12 pt-5">
        {{ $slot }}
    </div>
</div>
