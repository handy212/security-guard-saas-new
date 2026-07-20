<?php

namespace App\Livewire\Dashboard;

use App\Services\DashboardMetricsService;
use App\Support\TenantContext;
use Livewire\Component;

class AttentionIndicator extends Component
{
    public bool $open = false;

    public function render(DashboardMetricsService $metrics)
    {
        if (TenantContext::isPlatformConsole() || ! auth()->user()?->can('dashboard.view')) {
            return view('livewire.dashboard.attention-indicator', [
                'items' => [],
                'count' => 0,
                'hasDanger' => false,
            ]);
        }

        $items = $metrics->attentionItems((int) TenantContext::id());

        return view('livewire.dashboard.attention-indicator', [
            'items' => $items,
            'count' => count($items),
            'hasDanger' => collect($items)->contains(fn (array $item) => ($item['tone'] ?? '') === 'danger'),
        ]);
    }
}
