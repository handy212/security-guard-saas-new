@props(['title' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-lg border border-zinc-200 bg-white']) }}>
    @if ($title)
        <div class="border-b border-zinc-100 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-zinc-500">{{ $title }}</div>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
