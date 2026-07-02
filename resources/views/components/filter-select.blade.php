@props(['label' => null])

<div {{ $attributes->only('class')->merge(['class' => 'inline-flex items-center gap-2']) }}>
    @if ($label)
        <span class="text-xs font-medium text-zinc-500">{{ $label }}</span>
    @endif
    <select {{ $attributes->except('label')->merge(['class' => 'filter-select']) }}>
        {{ $slot }}
    </select>
</div>
