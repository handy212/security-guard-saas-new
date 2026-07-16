@props(['label' => null, 'error' => null, 'hint' => null])

@php
    $errorKey = $attributes->get('wire:model')
        ?? $attributes->get('wire:model.live')
        ?? $attributes->get('wire:model.blur')
        ?? $attributes->get('wire:model.defer');
    $resolvedError = $error ?? ($errorKey ? $errors->first($errorKey) : null);
    $inputClass = 'form-input file:mr-3 file:rounded-md file:border-0 file:bg-accent-50 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-accent-800 hover:file:bg-accent-100'.($resolvedError ? ' form-input-error' : '');
@endphp

<x-form-field :label="$label" :error="$resolvedError" :hint="$hint" {{ $attributes->only('class') }}>
    <input
        type="file"
        {{ $attributes->merge(['class' => $inputClass])->except(['label', 'error', 'hint']) }}
    />
</x-form-field>
