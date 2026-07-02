@props(['label' => null, 'error' => null, 'hint' => null, 'rows' => 3])

@php
    $errorKey = $attributes->get('wire:model') ?? $attributes->get('wire:model.live');
    $resolvedError = $error ?? ($errorKey ? $errors->first($errorKey) : null);
@endphp

<x-form-field :label="$label" :error="$resolvedError" :hint="$hint" {{ $attributes->only('class') }}>
    <textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => 'form-input'])->except(['label', 'error', 'hint', 'rows']) }}>{{ $slot }}</textarea>
</x-form-field>
