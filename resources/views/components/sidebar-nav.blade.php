@php
    use App\Services\PlanEntitlementService;
    use App\Support\TenantContext;

    $isPlatformAdmin = TenantContext::isPlatformAdmin();
    $isViewingAsTenant = TenantContext::isViewingAsTenant();
    $tenantId = TenantContext::current()?->id;
    $entitlements = app(PlanEntitlementService::class);

    $linkVisible = function (array $link) use ($tenantId, $entitlements): bool {
        if (! empty($link['permission']) && ! auth()->user()?->can($link['permission'])) {
            return false;
        }

        if ($tenantId && ! empty($link['feature']) && ! $entitlements->tenantHasFeature($tenantId, $link['feature'])) {
            return false;
        }

        return true;
    };

    if ($isPlatformAdmin && ! $isViewingAsTenant) {
        $primary = collect(config('navigation.platform', []))->filter($linkVisible);
        $groups = collect();
    } else {
        $navigation = config('navigation.navigation');
        $primary = collect($navigation['primary'] ?? [])->filter($linkVisible);
        $groups = collect($navigation['groups'] ?? $navigation)->map(function ($links, $group) use ($linkVisible) {
            if ($group === 'primary') {
                return null;
            }
            $visible = collect($links)->filter($linkVisible);

            return ['label' => $group, 'links' => $visible->values()->all()];
        })->filter(fn ($group) => $group && count($group['links']) > 0);

        $activeGroup = $groups->first(fn ($group) => collect($group['links'])->contains(
            fn ($link) => request()->is(ltrim($link['href'], '/').'*')
        ))['label'] ?? null;
    }

    $linkClass = function (bool $active, bool $highlight = false): string {
        $base = 'flex items-center gap-2 rounded-md px-2.5 py-2 text-sm font-medium transition';
        if ($active) {
            return $base.' bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900';
        }
        if ($highlight) {
            return $base.' text-emerald-800 ring-1 ring-emerald-200 hover:bg-emerald-50 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-950/40';
        }

        return $base.' text-zinc-700 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800';
    };

    $subLinkClass = fn (bool $active): string => $active
        ? 'flex items-center gap-2 rounded-md py-1.5 pl-2 pr-2 text-sm font-medium text-zinc-900 dark:text-zinc-100'
        : 'flex items-center gap-2 rounded-md py-1.5 pl-2 pr-2 text-sm text-zinc-500 transition hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200';
@endphp

<nav class="flex-1 overflow-y-auto overscroll-contain px-2 py-3" x-data="{ open: @js(($isPlatformAdmin && ! $isViewingAsTenant) ? null : ($activeGroup ?? null)) }">
  <div class="space-y-0.5">
    @foreach ($primary as $link)
        @php
            $active = request()->is(ltrim($link['href'], '/').'*')
                || (! $isPlatformAdmin && $link['href'] === '/settings' && (
                    request()->is('settings*')
                    || request()->is('mobile/offline-sync*')
                ));
        @endphp
        <a href="{{ $link['href'] }}"
           @click="sidebarOpen = false"
           class="{{ $linkClass($active, ! empty($link['highlight'])) }}">
            <x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-4 w-4 shrink-0 opacity-80" />
            {{ $link['label'] }}
        </a>
    @endforeach
  </div>

  @if ($primary->isNotEmpty() && $groups->isNotEmpty())
      <div class="my-3 border-t border-zinc-100 dark:border-zinc-800"></div>
  @endif

  <div class="space-y-1">
    @foreach ($groups as $group)
        @php
            $groupActive = collect($group['links'])->contains(fn ($link) => request()->is(ltrim($link['href'], '/').'*'));
        @endphp
        <div>
            <button
                type="button"
                @click="open = open === @js($group['label']) ? null : @js($group['label'])"
                class="flex w-full items-center justify-between rounded-md px-2.5 py-2 text-left text-sm font-medium transition {{ $groupActive ? 'bg-zinc-50 text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100' : 'text-zinc-600 hover:bg-zinc-50 dark:text-zinc-400 dark:hover:bg-zinc-800' }}"
            >
                <span>{{ $group['label'] }}</span>
                <svg class="h-4 w-4 shrink-0 text-zinc-400 transition-transform duration-200" :class="open === @js($group['label']) ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div
                x-show="open === @js($group['label'])"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                class="mt-0.5 space-y-0.5 border-l border-zinc-200 ml-3 pl-2 dark:border-zinc-700"
            >
                @foreach ($group['links'] as $link)
                    @php $active = request()->is(ltrim($link['href'], '/').'*'); @endphp
                    <a href="{{ $link['href'] }}"
                       @click="sidebarOpen = false"
                       class="{{ $subLinkClass($active) }}">
                        <x-nav-icon :name="$link['icon'] ?? 'dashboard'" class="h-3.5 w-3.5 shrink-0 opacity-70" />
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
  </div>
</nav>
