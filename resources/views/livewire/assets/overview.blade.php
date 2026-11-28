<div>
    <x-page-shell title="Assets" compact stable-header>
        <x-sub-sidebar-layout class="billing-workspace assets-workspace">
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>
            <x-asset-tabs />

            <x-flash-status />

            <x-section-card title="Deploy kit readiness" description="Field equipment currently available for deployment." class="assets-readiness-card">
                <div class="assets-kit-grid">
                    @foreach ($kitCategories as $kit)
                        <a href="{{ route('assets.index', ['category' => $kit->id]) }}" class="assets-kit-tile" wire:key="kit-{{ $kit->id }}" wire:navigate>
                            <div class="flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="meta-tile-label">{{ $kit->name }}</div>
                                    <div class="mt-1 text-lg font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">{{ $kit->available_count }}</div>
                                </div>
                                <span class="assets-readiness-dot {{ $kit->available_count > 0 ? 'assets-readiness-dot-ready' : 'assets-readiness-dot-empty' }}" aria-hidden="true"></span>
                            </div>
                            <div class="mt-2 flex items-center justify-between gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
                                <span>{{ $kit->available_count > 0 ? 'Ready to issue' : 'Fully issued' }}</span>
                                <span class="tabular-nums">{{ $kit->assets_count }} total</span>
                            </div>
                        </a>
                    @endforeach
                </div>
                <div class="assets-readiness-actions">
                    <a href="{{ route('assets.inventory') }}" class="quick-action" wire:navigate>View inventory</a>
                    <a href="{{ route('patrols.fleet') }}" class="quick-action" wire:navigate>Manage fleet</a>
                    <a href="{{ route('schedules.deploy') }}" class="quick-action" wire:navigate>Deploy with kit</a>
                </div>
            </x-section-card>

            <section aria-labelledby="assets-availability-title">
                <div class="assets-section-heading">
                    <div>
                        <h2 id="assets-availability-title">Availability at a glance</h2>
                        <p>Current asset coverage and procurement workload.</p>
                    </div>
                </div>
                <div class="assets-primary-stats">
                    <a href="{{ route('assets.index') }}" wire:navigate>
                        <x-stat-card compact label="Total assets" :value="$stats['total_assets']" icon="equipment" />
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'available']) }}" wire:navigate>
                        <x-stat-card compact label="Available" :value="$stats['available']" icon="check" tone="success" />
                    </a>
                    <a href="{{ route('assets.index', ['status' => 'issued']) }}" wire:navigate>
                        <x-stat-card compact label="Issued" :value="$stats['issued']" icon="guards" tone="info" />
                    </a>
                    <a href="{{ route('assets.purchase-orders') }}" wire:navigate>
                        <x-stat-card compact label="Open POs" :value="$stats['open_pos']" icon="billing" :tone="$stats['open_pos'] ? 'warning' : 'default'" />
                    </a>
                </div>
            </section>

            <section class="assets-summary-strip" aria-labelledby="assets-summary-title">
                <div>
                    <p id="assets-summary-title" class="assets-summary-title">Workspace summary</p>
                    <p class="assets-summary-copy">Supporting inventory data and risk indicators.</p>
                </div>
                <a href="{{ route('assets.categories') }}" wire:navigate><span>Categories</span><strong>{{ $stats['categories'] }}</strong></a>
                <a href="{{ route('assets.vendors') }}" wire:navigate><span>Vendors</span><strong>{{ $stats['vendors'] }}</strong></a>
                <div><span>Asset value</span><strong>{{ money($stats['asset_value'], 0) }}</strong></div>
                <a href="{{ route('assets.index', ['warranty' => 'expiring']) }}" wire:navigate><span>Warranty alerts</span><strong>{{ $stats['warranty_expiring'] }}</strong></a>
            </section>

            <div class="assets-overview-grid">
                <x-section-card title="Open purchase orders" compact flush>
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
                            <x-empty-state inline title="No open POs" description="Create a purchase order to procure gear and supplies.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('assets.purchase-orders')">Purchase orders</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card title="Recent assignments" compact flush>
                    <x-slot:actions>
                        <a href="{{ route('assets.index') }}" class="page-link" wire:navigate>View assets</a>
                    </x-slot:actions>
                    @forelse($recentAssignments as $assignment)
                        <a href="{{ route('assets.index', ['search' => $assignment->asset?->name]) }}" class="list-row" wire:key="asg-{{ $assignment->id }}" wire:navigate>
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
                            <x-empty-state inline title="No recent assignments" description="Issue assets to guards from the asset list.">
                                <x-slot:actions>
                                    <x-button size="sm" :href="route('assets.index')">Asset list</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card title="Inventory alerts" compact flush>
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
                            <a href="{{ route('assets.index', ['warranty' => 'expiring', 'search' => $asset->name]) }}" class="list-row" wire:key="war-{{ $asset->id }}" wire:navigate>
                                <div class="min-w-0 flex-1">
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $asset->name }}</div>
                                    <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $asset->warranty_expires_at->format('M j, Y') }}</div>
                                </div>
                            </a>
                        @endforeach
                    @endif

                    @if($lowStock->isEmpty() && $warrantyAlerts->isEmpty())
                        <div class="p-3">
                            <x-empty-state inline title="No alerts" description="Stock levels and warranties look good." />
                        </div>
                    @endif
                </x-section-card>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
