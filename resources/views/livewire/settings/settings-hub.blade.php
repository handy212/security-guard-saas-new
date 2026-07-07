<div>
    <x-page-shell title="Settings" description="Organization setup, access control, and integrations.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (app(\App\Services\NavigationBuilder::class)->settingsLinks() as $link)
                    <a href="{{ $link['href'] }}" class="card-surface block p-4 transition hover:border-accent-200 hover:shadow-md">
                        <div class="text-sm font-semibold text-zinc-900">{{ $link['label'] }}</div>
                        <div class="mt-1 text-xs text-zinc-500">
                            @if (($link['href'] ?? '') === '/billing/subscription')
                                Manage your GuardOps subscription and plan limits
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
