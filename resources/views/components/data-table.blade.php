@props(['title' => null])

<div {{ $attributes->merge(['class' => 'overflow-hidden rounded-xl border bg-white']) }}>
    @if ($title)
        <div class="border-b px-4 py-3 text-sm font-semibold text-slate-700">{{ $title }}</div>
    @endif
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            {{ $slot }}
        </table>
    </div>
</div>
