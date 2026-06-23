@props(['label' => null, 'error' => null, 'hint' => null])

<x-form-field :label="$label" :error="$error" :hint="$hint" {{ $attributes->only('class') }}>
    <select {{ $attributes->merge(['class' => 'form-input'])->except(['label', 'error', 'hint']) }}>
        {{ $slot }}
    </select>
</x-form-field>
