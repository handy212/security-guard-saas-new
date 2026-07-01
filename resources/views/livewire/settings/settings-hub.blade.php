<div>
    <x-page-shell title="Settings" description="Organization setup, access control, and integrations.">
        <x-settings-nav />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (collect(config('navigation.settings', []))->filter(fn ($link) => empty($link['permission']) || auth()->user()->can($link['permission'])) as $link)
                <a href="{{ $link['href'] }}" class="flex items-start gap-3 rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-zinc-600">
                    <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        <x-nav-icon :name="$link['icon'] ?? 'settings'" class="h-4 w-4" />
                    </div>
                    <div>
                        <div class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $link['label'] }}</div>
                        <div class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Configure {{ strtolower($link['label']) }}</div>
                    </div>
                </a>
            @endforeach
        </div>
    </x-page-shell>
</div>
