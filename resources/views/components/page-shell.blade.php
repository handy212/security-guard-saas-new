@props(['title', 'description' => null, 'breadcrumbs' => []])

<div class="flex min-h-full flex-col">
    <div class="sticky top-0 z-20 border-b border-zinc-200 bg-white shadow-sm">
        <div class="page-content flex items-center justify-between gap-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    class="btn-secondary !p-2 lg:hidden"
                    aria-label="Open navigation"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>
                <div class="min-w-0">
                    @if (! empty($breadcrumbs))
                        <nav class="mb-0.5 flex flex-wrap items-center gap-1.5 text-xs text-zinc-500">
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
                        <p class="truncate text-sm text-zinc-500">{{ $description }}</p>
                    @endif
                </div>
            </div>
            @if (isset($actions))
                <div class="flex shrink-0 flex-wrap items-center gap-2">{{ $actions }}</div>
            @endif
        </div>
    </div>

    <div class="page-content flex-1 space-y-4 pb-8">
        {{ $slot }}
    </div>
</div>
