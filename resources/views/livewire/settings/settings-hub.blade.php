<div>
    <x-page-shell
        title="Settings"
        description="Organization setup, access control, and integrations."
        :breadcrumbs="[['label' => 'Settings']]"
    >
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <p class="hidden text-sm text-zinc-500 dark:text-zinc-400 lg:block">Choose a settings section from the sidebar, or open a card below.</p>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                @foreach (app(\App\Services\NavigationBuilder::class)->settingsLinks() as $link)
                    <a
                        href="{{ $link['href'] }}"
                        class="card-surface group block p-4 transition hover:border-accent-300 dark:hover:border-accent-700"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <div class="text-sm font-semibold text-zinc-900 transition group-hover:text-accent-700 dark:text-zinc-100 dark:group-hover:text-accent-300">
                                    {{ $link['label'] }}
                                </div>
                                <div class="mt-1 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">
                                    @if (($link['href'] ?? '') === '/billing/subscription')
                                        Manage your GuardCore Pro subscription and plan limits
                                    @else
                                        Configure {{ strtolower($link['label']) }}
                                    @endif
                                </div>
                            </div>
                            <span class="shrink-0 text-xs font-semibold text-zinc-300 transition group-hover:text-accent-600 dark:text-zinc-600 dark:group-hover:text-accent-400">→</span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
