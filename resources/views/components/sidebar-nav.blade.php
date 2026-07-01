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
           class="flex items-center rounded-md px-2.5 py-2 text-sm font-medium transition {{ $active ? 'bg-zinc-900 text-white' : 'text-zinc-700 hover:bg-zinc-100' }}">
            {{ $link['label'] }}
        </a>
    @endforeach
  </div>

  @if ($primary->isNotEmpty() && $groups->isNotEmpty())
      <div class="my-3 border-t border-zinc-100"></div>
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
                class="flex w-full items-center justify-between rounded-md px-2.5 py-2 text-left text-sm font-medium transition {{ $groupActive ? 'bg-zinc-50 text-zinc-900' : 'text-zinc-600 hover:bg-zinc-50' }}"
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
                class="mt-0.5 space-y-0.5 border-l border-zinc-200 ml-3 pl-2"
            >
                @foreach ($group['links'] as $link)
                    @php $active = request()->is(ltrim($link['href'], '/').'*'); @endphp
                    <a href="{{ $link['href'] }}"
                       @click="sidebarOpen = false"
                       class="block rounded-md py-1.5 pl-2 pr-2 text-sm transition {{ $active ? 'font-medium text-zinc-900' : 'text-zinc-500 hover:text-zinc-800' }}">
                        {{ $link['label'] }}
                    </a>
                @endforeach
            </div>
        </div>
    @endforeach
  </div>
</nav>
