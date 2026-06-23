@props(['title', 'description' => null, 'actions' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-3 border-b border-slate-200 bg-white px-6 py-5 sm:flex-row sm:items-center sm:justify-between']) }}>
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">{{ $title }}</h1>
        @if ($description)
            <p class="mt-1 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    @if ($actions)
        <div class="flex flex-wrap gap-2">{{ $actions }}</div>
    @endif
</div>
