@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null, 'compact' => false])

<div {{ $attributes->class([
    'rounded-xl border border-dashed border-zinc-200 bg-zinc-50/60 text-center dark:border-zinc-700 dark:bg-zinc-900/40',
    $compact ? 'px-4 py-8' : 'px-6 py-12',
]) }}>
    <div class="mx-auto mb-3 flex h-10 w-10 items-center justify-center rounded-full bg-white text-zinc-400 ring-1 ring-zinc-200 dark:bg-zinc-900 dark:ring-zinc-700">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-width="1.75" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        </svg>
    </div>
    <h3 class="text-sm font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-1.5 max-w-sm text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    @if (isset($actions))
        <div class="mt-4 flex flex-wrap items-center justify-center gap-2">
            {{ $actions }}
        </div>
    @elseif ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary mt-4 inline-flex">{{ $actionLabel }}</a>
    @endif
</div>
