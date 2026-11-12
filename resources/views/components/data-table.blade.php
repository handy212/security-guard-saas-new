@props(['title' => null])

<div {{ $attributes->merge(['class' => 'card-surface overflow-hidden']) }}>
    @if ($title)
        <div class="data-table-heading">{{ $title }}</div>
    @endif
    <div class="overflow-x-auto">
        <table class="data-table" role="table" @if ($title) aria-label="{{ $title }}" @endif>
            {{ $slot }}
        </table>
    </div>
</div>
