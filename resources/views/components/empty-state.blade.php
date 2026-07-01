@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-dashed border-zinc-300 bg-zinc-50/50 px-4 py-8 text-center']) }}>
    <h3 class="text-sm font-semibold text-zinc-900">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1 text-xs text-zinc-500">{{ $description }}</p>
    @endif
    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary mt-3 inline-flex">{{ $actionLabel }}</a>
    @endif
</div>
