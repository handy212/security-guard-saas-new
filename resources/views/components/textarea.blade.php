@props(['label' => null, 'error' => null, 'hint' => null, 'rows' => 3])

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
    <textarea rows="{{ $rows }}" {{ $attributes->merge(['class' => 'form-input'])->except(['label', 'error', 'hint', 'rows']) }}>{{ $slot }}</textarea>
</x-form-field>
