@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null, 'compact' => false])

<div {{ $attributes->class([
    'rounded-lg border border-dashed border-zinc-300 bg-zinc-50/50 text-center dark:border-zinc-700 dark:bg-zinc-900/50',
    $compact ? 'px-3 py-6' : 'px-4 py-8',
]) }}>
    <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
    @endif

    @if (isset($actions))
        <div class="mt-3 flex flex-wrap items-center justify-center gap-2">
            {{ $actions }}
        </div>
    @elseif ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary mt-3 inline-flex">{{ $actionLabel }}</a>
    @endif
</div>
