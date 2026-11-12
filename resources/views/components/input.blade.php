@props(['label' => null, 'error' => null, 'hint' => null])

@php
    $errorKey = $attributes->get('wire:model.live')
        ?? $attributes->get('wire:model.live')
        ?? $attributes->get('wire:model.live.blur')
        ?? $attributes->get('wire:model');
    $resolvedError = $error ?? ($errorKey ? $errors->first($errorKey) : null);
    $inputClass = 'form-input'.($resolvedError ? ' form-input-error' : '');
    $inputId = $attributes->get('id') ?? 'field-'.str_replace(['.', '_'], '-', $errorKey ?? \Illuminate\Support\Str::uuid()->toString());
    $descriptionId = $resolvedError ? $inputId.'-error' : ($hint ? $inputId.'-hint' : null);
@endphp

<x-form-field :label="$label" :for="$inputId" :error="$resolvedError" :hint="$hint" {{ $attributes->only('class') }}>
    <input {{ $attributes->merge(['id' => $inputId, 'class' => $inputClass, 'aria-invalid' => $resolvedError ? 'true' : null, 'aria-describedby' => $descriptionId])->except(['label', 'error', 'hint']) }} />
</x-form-field>
