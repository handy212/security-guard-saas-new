@props(['label' => null, 'error' => null, 'hint' => null])

@php
    $errorKey = $attributes->get('wire:model') ?? $attributes->get('wire:model.live');
    $resolvedError = $error ?? ($errorKey ? $errors->first($errorKey) : null);
@endphp

<x-form-field :label="$label" :error="$resolvedError" :hint="$hint" {{ $attributes->only('class') }}>
    <input
        type="file"
        {{ $attributes->merge(['class' => 'form-input file:mr-3 file:rounded-md file:border-0 file:bg-accent-50 file:px-3 file:py-1 file:text-xs file:font-medium file:text-accent-700 hover:file:bg-accent-100'])->except(['label', 'error', 'hint']) }}
    />
</x-form-field>
