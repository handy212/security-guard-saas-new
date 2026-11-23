<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardMetricsService;
use App\Services\NavigationBuilder;
use App\Support\TenantContext;
use Livewire\Component;

class AttentionIndicator extends Component
{
    public bool $open = false;

    public function render(DashboardMetricsService $metrics, NavigationBuilder $nav)
    {
        if (TenantContext::isPlatformConsole() || ! auth()->user()?->can('dashboard.view')) {
            return view('livewire.dashboard.attention-indicator', [
                'items' => [],
                'count' => 0,
                'hasDanger' => false,
            ]);
        }

        $destinations = [
            'sos' => ['dispatch.control-room', 'dispatch.manage'],
            'dispatch' => ['dispatch.control-room', 'dispatch.manage'],
            'staffing' => ['schedules.index', 'schedules.manage'],
            'incidents' => ['incidents.index', 'incidents.manage'],
            'idle' => ['tracking.live', 'dispatch.manage'],
            'kyg' => ['guards.kyg', 'guards.manage'],
        ];
        $items = array_values(array_filter(
            $metrics->attentionItems(TenantContext::id()),
            fn (array $item): bool => isset($destinations[$item['key']]) && $nav->routeVisible(...$destinations[$item['key']]),
        ));

        return view('livewire.dashboard.attention-indicator', [
            'items' => $items,
            'count' => count($items),
            'hasDanger' => collect($items)->contains(fn (array $item) => ($item['tone'] ?? '') === 'danger'),
        ]);
    }
}
