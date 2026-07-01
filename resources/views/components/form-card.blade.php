@props(['title', 'description' => null, 'collapsible' => false, 'open' => true])

<div {{ $attributes->merge(['class' => 'rounded-lg border border-zinc-200 bg-white']) }}
     @if($collapsible) x-data="{ open: {{ $open ? 'true' : 'false' }} }" @endif>
    <div class="flex items-center justify-between border-b border-zinc-100 px-3 py-2">
        <div>
            <h3 class="text-sm font-semibold text-zinc-900">{{ $title }}</h3>
            @if($description)
                <p class="text-xs text-zinc-500">{{ $description }}</p>
            @endif
        </div>
        @if($collapsible)
            <button type="button" @click="open = !open" class="text-xs font-medium text-zinc-600" x-text="open ? 'Hide' : 'Show'"></button>
        @endif
    </div>
    <div class="p-3" @if($collapsible) x-show="open" @endif>
        {{ $slot }}
    </div>
</div>
