@props(['title', 'description' => null, 'collapsible' => false, 'open' => true])

<div {{ $attributes->merge(['class' => 'rounded-xl border border-slate-200 bg-white shadow-sm']) }}
     @if($collapsible) x-data="{ open: {{ $open ? 'true' : 'false' }} }" @endif>
    <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
        <div>
            <h3 class="text-sm font-bold text-slate-900">{{ $title }}</h3>
            @if($description)
                <p class="mt-0.5 text-xs text-slate-500">{{ $description }}</p>
            @endif
        </div>
        @if($collapsible)
            <button type="button" @click="open = !open" class="text-xs font-medium text-brand-700" x-text="open ? 'Hide' : 'Show'"></button>
        @endif
    </div>
    <div class="p-5" @if($collapsible) x-show="open" @endif>
        {{ $slot }}
    </div>
</div>
