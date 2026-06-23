@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="form-label">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if($hint && ! $error)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="text-xs text-red-600">{{ $error }}</p>
    @endif
</div>
