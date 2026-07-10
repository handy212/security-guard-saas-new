<div>
    <x-page-shell title="Assets" description="Overview of company assets, inventory, and procurement.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('assets.index')">Asset list</x-button>
            <x-button :href="route('assets.purchase-orders')">Purchase orders</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>

            <x-flash-status />

            <div class="stat-grid">
                <a href="{{ route('assets.index') }}" class="block">
                    <x-stat-card compact label="Total assets" :value="$stats['total_assets']" icon="equipment" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.index', ['status' => 'available']) }}" class="block">
                    <x-stat-card compact label="Available" :value="$stats['available']" icon="check" tone="success" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.index', ['status' => 'issued']) }}" class="block">
                    <x-stat-card compact label="Issued" :value="$stats['issued']" icon="guards" tone="info" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.purchase-orders') }}" class="block">
                    <x-stat-card compact label="Open POs" :value="$stats['open_pos']" icon="billing" :tone="$stats['open_pos'] ? 'warning' : 'default'" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.categories') }}" class="block">
                    <x-stat-card compact label="Categories" :value="$stats['categories']" icon="equipment" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.vendors') }}" class="block">
                    <x-stat-card compact label="Vendors" :value="$stats['vendors']" icon="clients" class="transition hover:border-zinc-300" />
                </a>
                <x-stat-card compact label="Asset value" :value="'$'.number_format($stats['asset_value'], 0)" icon="billing" />
                <a href="{{ route('assets.index') }}#warranty" class="block">
                    <x-stat-card compact label="Warranty alerts" :value="$stats['warranty_expiring']" icon="incidents" :tone="$stats['warranty_expiring'] ? 'warning' : 'default'" class="transition hover:border-zinc-300" />
                </a>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <x-section-card title="Open purchase orders" class="lg:col-span-1">
                    <div class="mb-2 text-right">
                        <a href="{{ route('assets.purchase-orders') }}" class="text-xs font-medium text-accent-600 hover:underline">View all</a>
                    </div>
                    @forelse($openOrders as $po)
                        <a href="{{ route('assets.purchase-orders') }}" class="block border-t border-zinc-100 py-2 text-sm first:border-0 hover:bg-zinc-50" wire:key="po-{{ $po->id }}">
                            <div class="font-medium">{{ $po->po_number }}</div>
                            <div class="text-xs text-zinc-500">{{ $po->vendor?->name }} · <x-badge :status="$po->status->value" /></div>
                        </a>
                    @empty
                        <x-empty-state compact title="No open POs" />
                    @endforelse
                </x-section-card>

                <x-section-card title="Recent assignments" class="lg:col-span-1">
                    <div class="mb-2 text-right">
                        <a href="{{ route('assets.index', ['status' => 'issued']) }}" class="text-xs font-medium text-accent-600 hover:underline">Issued assets</a>
                    </div>
                    @forelse($recentAssignments as $assignment)
                        <a href="{{ route('assets.index', ['status' => 'issued']) }}" class="block border-t border-zinc-100 py-2 text-sm first:border-0 hover:bg-zinc-50" wire:key="asg-{{ $assignment->id }}">
                            <div class="font-medium">{{ $assignment->asset?->name }}</div>
                            <div class="text-xs text-zinc-500">
                                {{ $assignment->assignedGuard?->full_name ?? '—' }}
                                · {{ $assignment->status }}
                                · {{ $assignment->created_at->diffForHumans() }}
                            </div>
                        </a>
                    @empty
                        <x-empty-state compact title="No recent assignments" />
                    @endforelse
                </x-section-card>

                <x-section-card title="Alerts" class="lg:col-span-1">
                    @if($lowStock->isNotEmpty())
                        <h4 class="mb-2 text-xs font-semibold uppercase text-amber-600">Low stock</h4>
                        @foreach($lowStock as $category)
                            <a href="{{ route('assets.index', ['category' => $category->id]) }}" class="mb-2 block text-sm hover:underline" wire:key="low-{{ $category->id }}">
                                <span class="font-medium">{{ $category->name }}</span>
                                <span class="text-zinc-500"> — {{ $category->on_hand }} on hand (min {{ $category->min_stock_level }})</span>
                            </a>
                        @endforeach
                    @endif

                    @if($warrantyAlerts->isNotEmpty())
                        <h4 class="mb-2 mt-3 text-xs font-semibold uppercase text-amber-600">Warranty expiring</h4>
                        @foreach($warrantyAlerts as $asset)
                            <a href="{{ route('assets.index') }}" class="mb-2 block text-sm hover:underline" wire:key="war-{{ $asset->id }}">
                                <span class="font-medium">{{ $asset->name }}</span>
                                <span class="text-zinc-500"> — {{ $asset->warranty_expires_at->format('M j, Y') }}</span>
                            </a>
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
