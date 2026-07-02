@props(['placeholder' => 'Search…'])

<div class="relative">
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-zinc-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
        <path stroke-linecap="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
    </svg>
    <input
        type="search"
        placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'form-input pl-10']) }}
    />
</div>
