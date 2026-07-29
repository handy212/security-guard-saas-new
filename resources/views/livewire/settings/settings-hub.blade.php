<div>
    <x-page-shell
        title="Settings"
        description="Organization setup, access control, and integrations."
        :breadcrumbs="[['label' => 'Settings']]"
    >
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            @php
                $settingsBlurbs = [
                    '/billing/subscription' => 'Manage your GuardCore Pro subscription and plan limits.',
                    '/settings/branches' => 'Offices and operating locations for this tenant.',
                    '/settings/id-card' => 'Guard ID card templates, colors, and logo.',
                    '/settings/know-your-guard' => 'Public verification page clients see when scanning QR codes.',
                    '/settings/roles' => 'Define roles and what each role can access.',
                    '/settings/staff' => 'Invite staff, assign roles, and deactivate accounts.',
                    '/settings/audit-log' => 'Review sensitive actions across your tenant.',
                    '/settings/team' => 'Reset passwords for team members.',
                    '/settings/two-factor' => 'Protect your account with authenticator apps.',
                    '/settings/webhooks' => 'Push events to external systems.',
                    '/settings/notifications' => 'Email and SMS template defaults.',
                    '/mobile/offline-sync' => 'Field device sync status and queues.',
                ];
            @endphp

            <div class="space-y-2">
                <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Configuration</h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">Choose a section to manage your organization.</p>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                @foreach (app(\App\Services\NavigationBuilder::class)->settingsLinks() as $link)
                    <a
                        href="{{ $link['href'] }}"
                        class="card-surface group block p-5 transition hover:-translate-y-0.5 hover:border-accent-300 dark:hover:border-accent-700"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0 space-y-2">
                                <div class="text-base font-semibold text-zinc-900 transition group-hover:text-accent-700 dark:text-zinc-50 dark:group-hover:text-accent-300">
                                    {{ $link['label'] }}
                                </div>
                                <div class="text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">
                                    {{ $settingsBlurbs[$link['href'] ?? ''] ?? ('Configure '.strtolower($link['label'])) }}
                                </div>
                            </div>
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-100 text-zinc-400 transition group-hover:bg-accent-50 group-hover:text-accent-700 dark:bg-zinc-800 dark:text-zinc-500 dark:group-hover:bg-accent-950/50 dark:group-hover:text-accent-300">
                                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            </span>
                        </div>
                    </a>
                @endforeach
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
