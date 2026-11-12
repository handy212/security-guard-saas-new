@props(['label' => null, 'for' => null, 'error' => null, 'hint' => null])

<div {{ $attributes->only('class')->merge(['class' => 'form-field']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="form-label">{{ $label }}</label>
    @endif
    {{ $slot }}
    @if($hint && ! $error)
        <p @if($for) id="{{ $for }}-hint" @endif class="form-hint">{{ $hint }}</p>
    @endif
    @if($error)
        <p @if($for) id="{{ $for }}-error" @endif class="form-error" role="alert">{{ $error }}</p>
    @endif
</div>
