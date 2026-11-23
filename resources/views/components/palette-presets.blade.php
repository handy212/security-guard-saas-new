@props(['lightModel', 'darkModel'])

<fieldset {{ $attributes->class("mb-5") }}>
    <legend class="form-label mb-2">Preset palettes</legend>
    <div class="grid gap-2 sm:grid-cols-2">
        @foreach ([
            ['Red, White & Black', '#b91c1c', '#ef4444'],
            ['Royal Blue', '#1d4ed8', '#60a5fa'],
            ['Forest Green', '#15803d', '#4ade80'],
            ['Deep Violet', '#6d28d9', '#a78bfa'],
        ] as [$name, $light, $dark])
            <button
                type="button"
                @click="{{ $lightModel }} = @js($light); {{ $darkModel }} = @js($dark)"
                :aria-pressed="{{ $lightModel }}?.toLowerCase() === @js($light) && {{ $darkModel }}?.toLowerCase() === @js($dark)"
                class="flex flex-wrap items-center gap-2 rounded-lg border border-zinc-200 p-3 text-left text-sm font-medium hover:border-accent-500 aria-pressed:border-accent-500 aria-pressed:bg-accent-50 dark:border-zinc-700 dark:aria-pressed:border-accent-400 dark:aria-pressed:bg-accent-950"
            >
                <span aria-hidden="true" class="inline-flex shrink-0 -space-x-1">
                    <span class="size-4 rounded-full border border-zinc-300 bg-white"></span>
                    <span class="size-4 rounded-full border border-white/40" style="background-color: {{ $light }}"></span>
                    <span class="size-4 rounded-full border border-zinc-500 bg-zinc-950"></span>
                </span>
                {{ $name }}
            </button>
        @endforeach
    </div>
</fieldset>
