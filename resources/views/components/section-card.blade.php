@props(['title', 'description' => null])

<section {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white p-5 shadow-sm']) }}>
    <div class="mb-4">
        <h2 class="text-base font-bold text-slate-900">{{ $title }}</h2>
        @if($description)
            <p class="mt-0.5 text-sm text-slate-500">{{ $description }}</p>
        @endif
    </div>
    {{ $slot }}
</section>
