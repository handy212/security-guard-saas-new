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

            return ['label' => $label, 'links' => $visible];
        })->filter(fn (array $group) => count($group['links']) > 0)->values();
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
            return request()->is('settings*') || request()->is('mobile/offline-sync*');
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

        return $items;
    }
}
