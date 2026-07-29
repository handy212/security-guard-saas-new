@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null, 'compact' => false])

<div {{ $attributes->class([
    'rounded-2xl border border-dashed border-zinc-200 bg-slate-50/80 text-center dark:border-zinc-700 dark:bg-zinc-900/40',
    $compact ? 'px-5 py-10' : 'px-8 py-14',
]) }}>
    <div class="mx-auto mb-4 flex h-12 w-12 items-center justify-center rounded-full bg-white text-zinc-400 shadow-[var(--shadow-card-sm)] ring-1 ring-zinc-200/80 dark:bg-zinc-900 dark:ring-zinc-700 dark:shadow-none">
        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-width="1.75" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
        </svg>
    </div>
    <h3 class="text-base font-semibold tracking-tight text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
    @if ($description)
        <p class="mx-auto mt-2 max-w-md text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    @if (isset($actions))
        <div class="mt-5 flex flex-wrap items-center justify-center gap-2.5">
            {{ $actions }}
        </div>
    @elseif ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary mt-5 inline-flex">{{ $actionLabel }}</a>
    @endif
</div>
