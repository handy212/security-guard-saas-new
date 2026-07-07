@props(['items' => []])

@if (! empty($items))
    <div {{ $attributes->merge(['class' => 'profile-meta']) }}>
        @foreach ($items as $item)
            @if (($item['type'] ?? '') === 'badge')
                <x-badge :status="$item['value']" />
            @elseif (($item['type'] ?? '') === 'chip')
                <span class="profile-meta-chip">{{ $item['value'] }}</span>
            @else
                <span class="profile-meta-text">{{ $item['value'] }}</span>
            @endif
        @endforeach
    </div>
@endif
