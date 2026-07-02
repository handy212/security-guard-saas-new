@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->only('class')->merge(['class' => 'form-field']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="form-label">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if($hint && ! $error)
        <p class="form-hint">{{ $hint }}</p>
    @endif
    @if($error)
        <p class="form-error">{{ $error }}</p>
    @endif
</div>
