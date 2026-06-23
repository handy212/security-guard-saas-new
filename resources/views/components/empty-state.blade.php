@props(['title', 'description' => null, 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-dashed border-slate-300 bg-white p-8 text-center']) }}>
    <div class="mx-auto max-w-md">
        <h3 class="text-lg font-semibold text-slate-900">{{ $title }}</h3>
        @if ($description)
            <p class="mt-2 text-sm text-slate-500">{{ $description }}</p>
        @endif
        @if ($action && $actionLabel)
            <a href="{{ $action }}" class="mt-4 inline-flex rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">{{ $actionLabel }}</a>
        @endif
    </div>
</div>
