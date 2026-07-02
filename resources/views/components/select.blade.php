@props(['label' => null, 'error' => null, 'hint' => null])

@php
    $errorKey = $attributes->get('wire:model')
        ?? $attributes->get('wire:model.live')
        ?? $attributes->get('wire:model.blur')
        ?? $attributes->get('wire:model.defer');
    $resolvedError = $error ?? ($errorKey ? $errors->first($errorKey) : null);
@endphp

<x-form-field :label="$label" :error="$resolvedError" :hint="$hint" {{ $attributes->only('class') }}>
    <select {{ $attributes->merge(['class' => 'form-input'])->except(['label', 'error', 'hint']) }}>
        {{ $slot }}
    </select>
</x-form-field>
