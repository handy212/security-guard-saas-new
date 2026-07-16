<?php

namespace App\Services;

use App\Support\TenantContext;
use Illuminate\Support\Collection;

class NavigationBuilder
{
    public function __construct(
        private readonly PlanEntitlementService $entitlements,
    ) {}

    public function linkVisible(array $link): bool
    {
        if (! empty($link['hub'])) {
            return collect(config('navigation.'.$link['hub'], []))
                ->contains(fn (array $child) => $this->linkVisible($child));
        }

        if (! empty($link['permission']) && ! auth()->user()?->can($link['permission'])) {
            return false;
        }

        // Guard mobile PWA — office/admin users use the main console instead.
        if (($link['href'] ?? '') === '/guard' && auth()->user()?->can('dashboard.view')) {
            return false;
        }

        $tenantId = TenantContext::current()?->id;

        if ($tenantId && ! empty($link['feature']) && ! $this->entitlements->tenantHasFeature($tenantId, $link['feature'])) {
            return false;
        }

        return true;
    }

    public function settingsLinks(): Collection
    {
        return collect(config('navigation.settings', []))
            ->filter(fn (array $link) => $this->linkVisible($link))
            ->values();
    }

    public function isPlatformConsole(): bool
    {
        return TenantContext::isPlatformAdmin() && ! TenantContext::isViewingAsTenant();
    }

    public function pinned(): Collection
    {
        if ($this->isPlatformConsole()) {
            return collect();
        }

        return collect(config('navigation.pinned', []))->filter(fn (array $link) => $this->linkVisible($link))->values();
    }

    public function groups(): Collection
    {
        if ($this->isPlatformConsole()) {
            return collect();
        }

        return collect(config('navigation.groups', []))->map(function (array $links, string $label) {
            $visible = collect($links)->filter(fn (array $link) => $this->linkVisible($link))->values()->all();

            return [
                'label' => $label,
                'icon' => $this->groupIcon($label),
                'href' => $visible[0]['href'] ?? '#',
                'links' => $visible,
            ];
        })->filter(fn (array $group) => count($group['links']) > 0)->values();
    }

    public function groupIcon(string $label): string
    {
        return match ($label) {
            'Patrols & Reports' => 'patrols',
            'Workforce' => 'workforce',
            'Guardians' => 'guards',
            'Clients' => 'clients',
            'Live ops' => 'dispatch',
            'Libraries' => 'reports',
            'Back Office' => 'billing',
            'Compliance & Insights' => 'analytics',
            default => 'dashboard',
        };
    }

    /** Child links shown in collapsed-rail hover flyouts for hub items. */
    public function flyoutChildren(array $link): array
    {
        $href = $link['href'] ?? '';

        $source = match ($href) {
            '/schedules' => [
                ...config('navigation.schedules', []),
                ...config('navigation.schedules_more', []),
            ],
            '/assets' => config('navigation.assets', []),
            '/settings' => config('navigation.settings', []),
            '/billing' => config('navigation.billing', []),
            '/reports' => config('navigation.reports', []),
            '/patrols' => config('navigation.patrols', []),
            default => [],
        };

        return collect($source)
            ->filter(fn (array $child) => $this->linkVisible($child))
            ->map(fn (array $child) => [
                'href' => $child['href'],
                'label' => $child['label'],
                'icon' => $child['icon'] ?? ($link['icon'] ?? 'dashboard'),
            ])
            ->values()
            ->all();
    }

    public function shortLabel(string $label): string
    {
        $map = [
            'Dashboard' => 'Home',
            'Field app' => 'Field',
            'Live Tracker' => 'Track',
            'Messenger' => 'Chat',
            'Scheduler' => 'Schedule',
            'Incidents' => 'Incidents',
            'Dispatch' => 'Dispatch',
            'Assets' => 'Assets',
            'Settings' => 'Settings',
            'Patrols & Reports' => 'Patrols',
            'Workforce' => 'Workforce',
            'Guardians' => 'Guards',
            'Clients' => 'Clients',
            'Sites' => 'Sites',
            'Guards' => 'Guards',
            'Patrols' => 'Patrols',
            'Reports' => 'Reports',
            'Back Office' => 'Office',
            'Compliance & Insights' => 'Insights',
            'Tenants' => 'Tenants',
            'Plans' => 'Plans',
            'Subscriptions' => 'Billing',
            'Live ops' => 'Live',
            'Libraries' => 'Libs',
        ];

        return $map[$label] ?? \Illuminate\Support\Str::limit($label, 8, '');
    }

    public function footer(): Collection
    {
        if ($this->isPlatformConsole()) {
            return collect();
        }

        return collect(config('navigation.footer', []))->filter(fn (array $link) => $this->linkVisible($link))->values();
    }

    public function platform(): Collection
    {
        if (! $this->isPlatformConsole()) {
            return collect();
        }

        return collect(config('navigation.platform', []))->filter(fn (array $link) => $this->linkVisible($link))->values();
    }

    public function activeGroupLabel(): ?string
    {
        return $this->groups()->first(
            fn (array $group) => collect($group['links'])->contains(
                fn (array $link) => request()->is(ltrim($link['href'], '/').'*')
            )
        )['label'] ?? null;
    }

    public function isLinkActive(array $link): bool
    {
        $href = ltrim($link['href'], '/');

        if ($link['href'] === '/settings') {
            return request()->is('settings*') || request()->is('mobile/offline-sync*') || request()->is('billing/subscription*');
        }

        // Scheduler hub: any schedules/* page (including reconciliation under schedules)
        if ($link['href'] === '/schedules') {
            return request()->is('schedules') || request()->is('schedules/*');
        }

        // Exact module roots: avoid highlighting pin for sibling nested routes
        if ($link['href'] === '/guards') {
            return request()->is('guards')
                || (
                    request()->is('guards/*')
                    && ! request()->is('guards/know-your-guard*')
                    && ! request()->is('guards/applications*')
                );
        }

        if ($link['href'] === '/clients') {
            return request()->is('clients')
                || (
                    request()->is('clients/*')
                    && ! request()->is('clients/complaints*')
                );
        }

        if ($link['href'] === '/sites') {
            return request()->is('sites') || request()->is('sites/*');
        }

        if ($link['href'] === '/billing') {
            return request()->is('billing') || request()->is('billing/*')
                || request()->is('compliance') || request()->is('compliance/*')
                || request()->is('analytics');
        }

        if ($link['href'] === '/reports') {
            return request()->is('reports') || request()->is('reports/*');
        }

        if ($link['href'] === '/patrols') {
            return request()->is('patrols') || request()->is('patrols/*')
                || request()->is('passdown') || request()->is('passdown/*');
        }

        return request()->is($href) || request()->is($href.'/*');
    }

    /** @return list<array{label: string, href: string, group: string|null}> */
    public function searchableLinks(): array
    {
        $items = [];

        foreach ($this->pinned() as $link) {
            $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => null];
        }

        foreach ($this->groups() as $group) {
            foreach ($group['links'] as $link) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => $group['label']];
            }
        }

        foreach ($this->footer() as $link) {
            $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Settings'];
        }

        foreach ($this->platform() as $link) {
            $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Platform'];
        }

        foreach (config('navigation.assets', []) as $link) {
            if ($this->linkVisible($link)) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Assets'];
            }
        }

        foreach (config('navigation.billing', []) as $link) {
            if ($this->linkVisible($link)) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Back Office'];
            }
        }

        foreach (config('navigation.reports', []) as $link) {
            if ($this->linkVisible($link)) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Reports'];
            }
        }

        foreach (config('navigation.patrols', []) as $link) {
            if ($this->linkVisible($link)) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Patrols'];
            }
        }

        foreach (config('navigation.schedules', []) as $link) {
            if ($this->linkVisible($link)) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Scheduler'];
            }
        }

        foreach (config('navigation.schedules_more', []) as $link) {
            if ($this->linkVisible($link)) {
                $items[] = ['label' => $link['label'], 'href' => $link['href'], 'group' => 'Scheduler'];
            }
        }

        return $items;
    }
}
