<div>
    <x-page-shell
        title="Settings"
        description="Organization setup, access control, and integrations. Use the sidebar on desktop to jump to a section."
        :breadcrumbs="[['label' => 'Settings']]"
    >
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <p class="mb-4 hidden text-sm text-zinc-500 lg:block">Choose a settings section from the sidebar, or pick a card below.</p>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (app(\App\Services\NavigationBuilder::class)->settingsLinks() as $link)
                    <a href="{{ $link['href'] }}" class="card-surface block p-4 transition hover:border-accent-300">
                        <div class="text-sm font-semibold text-zinc-900">{{ $link['label'] }}</div>
                        <div class="mt-1 text-xs text-zinc-500">
                            @if (($link['href'] ?? '') === '/billing/subscription')
                                Manage your GuardCore Pro subscription and plan limits
                            @else
                                Configure {{ strtolower($link['label']) }}
                            @endif
                        </div>
                    </a>
                @endforeach
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
