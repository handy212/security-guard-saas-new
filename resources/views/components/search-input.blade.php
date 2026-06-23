@props(['placeholder' => 'Search…'])

<div {{ $attributes->merge(['class' => 'relative']) }}>
    <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 18a7 7 0 100-14 7 7 0 000 14z"/>
    </svg>
    <input type="search" placeholder="{{ $placeholder }}" {{ $attributes->merge(['class' => 'form-input pl-10']) }} />
</div>
