@props(['type' => 'info'])

@if (session('error'))
    <div {{ $attributes->class(['mb-4']) }}>
        <x-alert tone="danger" title="Something went wrong">
            {{ session('error') }}
        </x-alert>
    </div>
@endif

@if (session('status'))
    @php
        $tone = match ($type) {
            'success' => 'success',
            'warning' => 'warning',
            'error', 'danger' => 'danger',
            default => 'info',
        };
        $title = match ($type) {
            'success' => 'Saved successfully',
            'warning' => 'Attention needed',
            'error', 'danger' => 'Something went wrong',
            default => null,
        };
    @endphp
    <div {{ $attributes->class(['mb-4']) }}>
        <x-alert :tone="$tone" :title="$title">
            {{ session('status') }}
        </x-alert>
    </div>
@endif
