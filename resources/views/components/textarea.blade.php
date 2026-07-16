@props(['label' => null, 'error' => null, 'hint' => null, 'rows' => 3])

@php
    $errorKey = $attributes->get('wire:model')
        ?? $attributes->get('wire:model.live')
        ?? $attributes->get('wire:model.blur')
        ?? $attributes->get('wire:model.defer');
    $resolvedError = $error ?? ($errorKey ? $errors->first($errorKey) : null);
    $inputClass = 'form-input'.($resolvedError ? ' form-input-error' : '');
@endphp

<x-form-field :label="$label" :error="$resolvedError" :hint="$hint" {{ $attributes->only('class') }}>
    <textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => $inputClass])->except(['label', 'error', 'hint', 'rows']) }}>{{ $slot }}</textarea>
</x-form-field>
