@props(['title', 'description' => null])

@push('page-header')
    <h1 class="truncate text-base font-semibold text-zinc-900 sm:text-lg">{{ $title }}</h1>
    @if ($description)
        <p class="hidden truncate text-sm text-zinc-500 sm:block">{{ $description }}</p>
    @endif
@endpush

@if (isset($actions))
    @push('page-actions')
        {{ $actions }}
    @endpush
@endif
