<div>
    <x-page-shell
        title="Assets"
        description="Overview of company assets, inventory, and procurement."
        :breadcrumbs="[['label' => 'Assets']]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('assets.index')">Asset list</x-button>
            <x-button :href="route('assets.purchase-orders')">Purchase orders</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-assets-nav /></x-slot:sidebar>

            <x-flash-status />

            <x-section-card title="Deploy kit inventory" description="Vehicles, motors, radios, and bodycams used when deploying guards.">
                <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($kitCategories as $kit)
                        <a href="{{ route('assets.index', ['category' => $kit->id]) }}" class="meta-tile transition hover:border-accent-300 hover:bg-accent-50/50 dark:hover:border-accent-700 dark:hover:bg-accent-950/30" wire:key="kit-{{ $kit->id }}" wire:navigate>
                            <div class="meta-tile-label">{{ $kit->name }}</div>
                            <div class="meta-tile-value tabular-nums">{{ $kit->available_count }} available</div>
                            <div class="mt-0.5 text-[11px] text-zinc-500 dark:text-zinc-400">
                                {{ $kit->assets_count }} total · {{ $kit->issued_count }} issued
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <a href="{{ route('assets.index') }}" class="quick-action" wire:navigate>Open asset list</a>
                    <a href="{{ route('assets.categories') }}" class="quick-action" wire:navigate>Categories</a>
                    <a href="{{ route('patrols.fleet') }}" class="quick-action" wire:navigate>Fleet</a>
                    <a href="{{ route('schedules.deploy') }}" class="quick-action" wire:navigate>Deploy with kit</a>
                </div>
            </x-section-card>

            <div class="stat-grid">
                <a href="{{ route('assets.index') }}" class="block" wire:navigate>
                    <x-stat-card compact label="Total assets" :value="$stats['total_assets']" icon="equipment" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.index', ['status' => 'available']) }}" class="block" wire:navigate>
                    <x-stat-card compact label="Available" :value="$stats['available']" icon="check" tone="success" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.index', ['status' => 'issued']) }}" class="block" wire:navigate>
                    <x-stat-card compact label="Issued" :value="$stats['issued']" icon="guards" tone="info" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.purchase-orders') }}" class="block" wire:navigate>
                    <x-stat-card compact label="Open POs" :value="$stats['open_pos']" icon="billing" :tone="$stats['open_pos'] ? 'warning' : 'default'" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.categories') }}" class="block" wire:navigate>
                    <x-stat-card compact label="Categories" :value="$stats['categories']" icon="equipment" class="transition hover:border-zinc-300" />
                </a>
                <a href="{{ route('assets.vendors') }}" class="block" wire:navigate>
                    <x-stat-card compact label="Vendors" :value="$stats['vendors']" icon="clients" class="transition hover:border-zinc-300" />
                </a>
                <x-stat-card compact label="Asset value" :value="'$'.number_format($stats['asset_value'], 0)" icon="billing" />
                <a href="{{ route('assets.index') }}#warranty" class="block" wire:navigate>
                    <x-stat-card compact label="Warranty alerts" :value="$stats['warranty_expiring']" icon="incidents" :tone="$stats['warranty_expiring'] ? 'warning' : 'default'" class="transition hover:border-zinc-300" />
                </a>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                <x-section-card title="Open purchase orders" flush>
                    <x-slot:actions>
                        <a href="{{ route('assets.purchase-orders') }}" class="page-link" wire:navigate>View all</a>
                    </x-slot:actions>
                    @forelse($openOrders as $po)
                        <a href="{{ route('assets.purchase-orders') }}" class="list-row" wire:key="po-{{ $po->id }}" wire:navigate>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium tabular-nums text-zinc-900 dark:text-zinc-100">{{ $po->po_number }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $po->vendor?->name }}</div>
                            </div>
                            <x-badge :status="$po->status->value" />
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No open POs" description="Create a purchase order to procure gear and supplies.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('assets.purchase-orders')">Purchase orders</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card title="Recent assignments" flush>
                    <x-slot:actions>
                        <a href="{{ route('assets.index', ['status' => 'issued']) }}" class="page-link" wire:navigate>Issued assets</a>
                    </x-slot:actions>
                    @forelse($recentAssignments as $assignment)
                        <a href="{{ route('assets.index', ['status' => 'issued']) }}" class="list-row" wire:key="asg-{{ $assignment->id }}" wire:navigate>
                            <div class="min-w-0 flex-1">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->asset?->name }}</div>
                                <div class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $assignment->assignedGuard?->full_name ?? '—' }}
                                    · {{ $assignment->status }}
                                    · {{ $assignment->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </a>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No recent assignments" description="Issue assets to guards from the asset list.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('assets.index')">Asset list</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card title="Alerts" flush>
                    @if($lowStock->isNotEmpty())
                        <div class="border-b border-zinc-100 px-4 py-2 dark:border-zinc-800">
                            <h4 class="text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Low stock</h4>
                        </div>
                        @foreach($lowStock as $category)
                            <a href="{{ route('assets.index', ['category' => $category->id]) }}" class="list-row" wire:key="low-{{ $category->id }}" wire:navigate>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $category->name }}</div>
                                    <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $category->on_hand }} on hand (min {{ $category->min_stock_level }})</div>
                                </div>
                            </a>
                        @endforeach
                    @endif

                    @if($warrantyAlerts->isNotEmpty())
                        <div class="border-b border-t border-zinc-100 px-4 py-2 dark:border-zinc-800">
                            <h4 class="text-[10px] font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">Warranty expiring</h4>
                        </div>
                        @foreach($warrantyAlerts as $asset)
                            <a href="{{ route('assets.index') }}" class="list-row" wire:key="war-{{ $asset->id }}" wire:navigate>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $asset->name }}</div>
                                    <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $asset->warranty_expires_at->format('M j, Y') }}</div>
                                </div>
                            </a>
                        @endforeach
                    @endif

                    @if($lowStock->isEmpty() && $warrantyAlerts->isEmpty())
                        <div class="p-3">
                            <x-empty-state compact title="No alerts" description="Stock levels and warranties look good." />
                        </div>
                    @endif
                </x-section-card>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
