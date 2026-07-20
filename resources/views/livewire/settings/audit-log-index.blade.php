<div>
    <x-page-shell title="Audit trail" description="Immutable log of security-sensitive actions across your organization.">
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <div class="stat-grid">
                <x-stat-card compact label="Total events" :value="$total" icon="billing" />
                <x-stat-card compact label="Today" :value="$today" icon="check" tone="info" />
                <x-stat-card compact label="Filtered" :value="$logs->total()" icon="users" />
                <x-stat-card compact label="Page" :value="$logs->currentPage().' / '.$logs->lastPage()" icon="plan" />
            </div>

            <x-page-toolbar search="search" searchPlaceholder="Search actions or users…">
                <x-slot:controls>
                    <x-filter-select wire:model.live="actionFilter">
                        <option value="all">All actions</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ str_replace('.', ' › ', $action) }}</option>
                        @endforeach
                    </x-filter-select>
                    @if ($hasActiveFilters)
                        <button type="button" wire:click="clearFilters" class="table-action">Clear filters</button>
                    @endif
                </x-slot:controls>
            </x-page-toolbar>

            <x-data-table>
                <x-table.head>
                    <tr>
                        <x-table.th>When</x-table.th>
                        <x-table.th>User</x-table.th>
                        <x-table.th>Action</x-table.th>
                        <x-table.th responsive="lg">Details</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="table-row-hover" wire:key="audit-{{ $log->id }}">
                            <x-table.td mono class="tabular-nums">{{ $log->created_at?->format('M j, H:i') }}</x-table.td>
                            <x-table.td class="font-medium">{{ $log->user?->name ?? 'System' }}</x-table.td>
                            <x-table.td mono>{{ str_replace('.', ' › ', $log->action) }}</x-table.td>
                            <x-table.td responsive="lg" muted>
                                @if ($log->metadata)
                                    {{ collect($log->metadata)->except(['ip', 'user_agent', 'platform'])->map(fn ($v, $k) => "$k: $v")->take(3)->implode(' · ') ?: '—' }}
                                @else
                                    —
                                @endif
                            </x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="4">
                            <x-empty-state
                                compact
                                :title="$hasActiveFilters ? 'No matching events' : 'No audit events yet'"
                                :description="$hasActiveFilters ? 'Try clearing filters or adjusting the search.' : 'Sensitive actions will appear here as your team works.'"
                            >
                                @if ($hasActiveFilters)
                                    <x-slot:actions>
                                        <x-button size="sm" variant="secondary" wire:click="clearFilters">Clear filters</x-button>
                                    </x-slot:actions>
                                @endif
                            </x-empty-state>
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-pagination :paginator="$logs" />
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
