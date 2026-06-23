@props(['label' => null, 'error' => null, 'hint' => null, 'rows' => 3])

<x-form-field :label="$label" :error="$error" :hint="$hint" {{ $attributes->only('class') }}>
    <textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => 'form-input'])->except(['label', 'error', 'hint', 'rows']) }}>{{ $slot }}</textarea>
</x-form-field>
