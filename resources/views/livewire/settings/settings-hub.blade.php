<div>
    <x-page-shell title="Settings" description="Organization setup, access control, and integrations.">
        <x-settings-nav />

        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
            @foreach (collect(config('navigation.settings', []))->filter(fn ($link) => empty($link['permission']) || auth()->user()->can($link['permission'])) as $link)
                <a href="{{ $link['href'] }}" class="rounded-lg border border-zinc-200 bg-white p-4 transition hover:border-zinc-300 hover:shadow-sm">
                    <div class="text-sm font-semibold text-zinc-900">{{ $link['label'] }}</div>
                    <div class="mt-1 text-xs text-zinc-500">Configure {{ strtolower($link['label']) }}</div>
                </a>
            @endforeach
        </div>
    </x-page-shell>
</div>
