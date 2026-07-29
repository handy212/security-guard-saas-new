@props(['title', 'description' => null, 'collapsible' => false, 'open' => true])

<div {{ $attributes->class(['card-surface overflow-hidden']) }}
     @if($collapsible) x-data="{ open: {{ $open ? 'true' : 'false' }} }" @endif>
    <div class="card-header">
        <div class="min-w-0">
            <h3 class="card-header-title">{{ $title }}</h3>
            @if($description)
                <p class="card-header-meta">{{ $description }}</p>
            @endif
        </div>
        @if($collapsible)
            <button type="button" @click="open = !open" class="page-link shrink-0" x-text="open ? 'Hide' : 'Show'"></button>
        @endif
    </div>
    <div class="p-5 sm:p-6" @if($collapsible) x-show="open" x-cloak @endif>
        {{ $slot }}
    </div>
    @isset($footer)
        <div class="form-card-footer" @if($collapsible) x-show="open" x-cloak @endif>
            {{ $footer }}
        </div>
    @endisset
</div>
