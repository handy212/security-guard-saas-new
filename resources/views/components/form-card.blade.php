@props(['title', 'description' => null, 'collapsible' => false, 'open' => true])

<div {{ $attributes->class(['card-surface overflow-hidden']) }}
     @if($collapsible) x-data="{ open: {{ $open ? 'true' : 'false' }} }" @endif>
    <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3">
        <div>
            <h3 class="text-sm font-semibold text-zinc-900">{{ $title }}</h3>
            @if($description)
                <p class="text-xs text-zinc-500">{{ $description }}</p>
            @endif
        </div>
        @if($collapsible)
            <button type="button" @click="open = !open" class="text-xs font-medium text-accent-600 hover:text-accent-700" x-text="open ? 'Hide' : 'Show'"></button>
        @endif
    </div>
    <div class="p-4" @if($collapsible) x-show="open" x-cloak @endif>
        {{ $slot }}
    </div>
</div>
