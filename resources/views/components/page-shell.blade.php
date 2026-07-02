@props(['title' => null, 'description' => null, 'breadcrumbs' => [], 'showHeader' => true])

<div class="flex min-h-full flex-col">
    @if ($showHeader)
        <div class="border-b border-zinc-200 bg-white">
            <div class="page-content flex flex-col gap-3 py-3 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    @if (! empty($breadcrumbs))
                        <nav class="mb-1 flex flex-wrap items-center gap-1.5 text-xs text-zinc-500">
                            @foreach ($breadcrumbs as $crumb)
                                @if (! empty($crumb['href']))
                                    <a href="{{ $crumb['href'] }}" class="hover:text-zinc-800">{{ $crumb['label'] }}</a>
                                    @if (! $loop->last)<span class="text-zinc-300">/</span>@endif
                                @else
                                    <span class="text-zinc-600">{{ $crumb['label'] }}</span>
                                @endif
                            @endforeach
                        </nav>
                    @endif
                    <h1 class="truncate text-lg font-semibold text-zinc-900">{{ $title }}</h1>
                    @if ($description)
                        <p class="mt-0.5 text-xs leading-relaxed text-zinc-500">{{ $description }}</p>
                    @endif
                </div>
                @if (isset($actions))
                    <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
                @endif
            </div>
        </div>
    @endif

    <div class="page-content flex-1 space-y-4 pb-8 pt-4">
        {{ $slot }}
    </div>
</div>
