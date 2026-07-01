@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white p-3']) }}>
    <div class="mb-3">
        <h2 class="text-sm font-semibold text-zinc-900">{{ $title }}</h2>
        @if($description)
            <p class="text-xs text-zinc-500">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
