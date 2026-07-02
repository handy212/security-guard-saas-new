@props(['title' => null])

<div {{ $attributes->merge(['class' => 'card-surface']) }}>
    @if ($title)
        <div class="table-caption">{{ $title }}</div>
    @endif
    <div class="overflow-x-auto">
        <table class="data-table">
            {{ $slot }}
        </table>
    </div>
</div>
