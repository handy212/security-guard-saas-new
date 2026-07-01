@php
    use App\Services\PlanEntitlementService;
    use App\Support\TenantContext;

    $tenant = TenantContext::current();
    $show = $tenant
        && ! TenantContext::isPlatformAdmin()
        && auth()->user()?->can('billing.manage');

    if ($show) {
        $usage = app(PlanEntitlementService::class)->usageSummary($tenant->id);
        $nearLimit = $usage['guards']['pct'] >= 80 || $usage['sites']['pct'] >= 80;
    }
@endphp

@if ($show && $nearLimit)
    <div class="border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900">
        <div class="flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
            <span>
                Plan usage:
                <strong>{{ $usage['guards']['used'] }}/{{ $usage['guards']['max'] }}</strong> guards,
                <strong>{{ $usage['sites']['used'] }}/{{ $usage['sites']['max'] }}</strong> sites
                @if ($usage['plan']) on <strong>{{ $usage['plan'] }}</strong>@endif.
            </span>
            <a href="{{ route('billing.subscription') }}" class="font-medium underline hover:no-underline">Upgrade plan</a>
        </div>
    </div>
@endif
