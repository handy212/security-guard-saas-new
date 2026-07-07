<div>
    <x-page-shell title="Assets" description="Overview of company assets, inventory, and procurement.">
        
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>


        <x-flash-status />

        <div class="stat-grid">
            <x-stat-card compact label="Total assets" :value="$stats['total_assets']" icon="equipment" />
            <x-stat-card compact label="Available" :value="$stats['available']" icon="check" tone="success" />
            <x-stat-card compact label="Issued" :value="$stats['issued']" icon="guards" tone="info" />
            <x-stat-card compact label="Open POs" :value="$stats['open_pos']" icon="billing" :tone="$stats['open_pos'] ? 'warning' : 'default'" />
            <x-stat-card compact label="Categories" :value="$stats['categories']" icon="equipment" />
            <x-stat-card compact label="Vendors" :value="$stats['vendors']" icon="clients" />
            <x-stat-card compact label="Asset value" :value="'$'.number_format($stats['asset_value'], 0)" icon="billing" />
            <x-stat-card compact label="Warranty alerts" :value="$stats['warranty_expiring']" icon="incidents" :tone="$stats['warranty_expiring'] ? 'warning' : 'default'" />
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-section-card title="Open purchase orders" class="lg:col-span-1">
                @forelse($openOrders as $po)
                    <div class="border-t border-zinc-100 py-2 text-sm first:border-0" wire:key="po-{{ $po->id }}">
                        <div class="font-medium">{{ $po->po_number }}</div>
                        <div class="text-xs text-zinc-500">{{ $po->vendor?->name }} · <x-badge :status="$po->status->value" /></div>
                    </div>
                @empty
                    <x-empty-state compact title="No open POs" />
                @endforelse
            </x-section-card>

            <x-section-card title="Recent assignments" class="lg:col-span-1">
                @forelse($recentAssignments as $assignment)
                    <div class="border-t border-zinc-100 py-2 text-sm first:border-0" wire:key="asg-{{ $assignment->id }}">
                        <div class="font-medium">{{ $assignment->asset?->name }}</div>
                        <div class="text-xs text-zinc-500">
                            {{ $assignment->assignedGuard?->full_name ?? '—' }}
                            · {{ $assignment->status }}
                            · {{ $assignment->created_at->diffForHumans() }}
                        </div>
                    </div>
                @empty
                    <x-empty-state compact title="No recent assignments" />
                @endforelse
            </x-section-card>

            <x-section-card title="Alerts" class="lg:col-span-1">
                @if($lowStock->isNotEmpty())
                    <h4 class="mb-2 text-xs font-semibold uppercase text-amber-600">Low stock</h4>
                    @foreach($lowStock as $category)
                        <div class="mb-2 text-sm" wire:key="low-{{ $category->id }}">
                            <span class="font-medium">{{ $category->name }}</span>
                            <span class="text-zinc-500"> — {{ $category->on_hand }} on hand (min {{ $category->min_stock_level }})</span>
                        </div>
                    @endforeach
                @endif

                @if($warrantyAlerts->isNotEmpty())
                    <h4 class="mb-2 mt-3 text-xs font-semibold uppercase text-amber-600">Warranty expiring</h4>
                    @foreach($warrantyAlerts as $asset)
                        <div class="mb-2 text-sm" wire:key="war-{{ $asset->id }}">
                            <span class="font-medium">{{ $asset->name }}</span>
                            <span class="text-zinc-500"> — {{ $asset->warranty_expires_at->format('M j, Y') }}</span>
                        </div>
                    @endforeach
                @endif

                @if($lowStock->isEmpty() && $warrantyAlerts->isEmpty())
                    <x-empty-state compact title="No alerts" />
                @endif
            </x-section-card>
        </div>
            </x-sub-sidebar-layout>
    </x-page-shell>
</div>
