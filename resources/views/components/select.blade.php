@props(['label' => null, 'error' => null, 'hint' => null])

@php
    if ($error === null) {
        $modelKey = collect(['wire:model', 'wire:model.live', 'wire:model.blur', 'wire:model.defer'])
            ->map(fn (string $key) => $attributes->get($key))
            ->filter()
            ->first();
        if ($modelKey) {
            $error = $errors->first($modelKey);
        }
    }
@endphp

<x-form-field :label="$label" :error="$error" :hint="$hint" {{ $attributes->only('class') }}>
    <select {{ $attributes->merge(['class' => 'form-input'])->except(['label', 'error', 'hint']) }}>
        {{ $slot }}
    </select>
</x-form-field>
