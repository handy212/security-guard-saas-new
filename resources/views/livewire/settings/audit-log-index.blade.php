<div>
    <x-page-shell title="Audit trail" description="Immutable log of security-sensitive actions across your organization.">
        <x-settings-nav />

        <div class="grid grid-cols-4 gap-2">
            <x-stat-card compact label="Total events" :value="$total" icon="billing" />
            <x-stat-card compact label="Today" :value="$today" icon="check" tone="info" />
            <x-stat-card compact label="Filtered" :value="$logs->total()" icon="users" />
            <x-stat-card compact label="Page" :value="$logs->currentPage().' / '.$logs->lastPage()" icon="plan" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search actions or users…">
            <x-slot:controls>
                <select wire:model.live="actionFilter" class="form-input text-sm">
                    <option value="all">All actions</option>
                    @foreach ($actions as $action)
                        <option value="{{ $action }}">{{ str_replace('.', ' › ', $action) }}</option>
                    @endforeach
                </select>
                @if ($hasActiveFilters)
                    <button type="button" wire:click="clearFilters" class="text-xs font-medium text-zinc-500 hover:text-zinc-800">Clear filters</button>
                @endif
            </x-slot:controls>
        </x-page-toolbar>

        <x-data-table>
            <thead class="bg-zinc-50 text-left text-xs font-medium text-zinc-500">
                <tr>
                    <th class="px-3 py-2">When</th>
                    <th class="px-3 py-2">User</th>
                    <th class="px-3 py-2">Action</th>
                    <th class="hidden px-3 py-2 lg:table-cell">Details</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($logs as $log)
                    <tr class="table-row-hover" wire:key="audit-{{ $log->id }}">
                        <td class="px-3 py-2 text-xs text-zinc-600">{{ $log->created_at?->format('M j, H:i') }}</td>
                        <td class="px-3 py-2 text-sm">{{ $log->user?->name ?? 'System' }}</td>
                        <td class="px-3 py-2 font-mono text-xs text-zinc-800">{{ $log->action }}</td>
                        <td class="hidden px-3 py-2 text-xs text-zinc-500 lg:table-cell">
                            @if ($log->metadata)
                                {{ collect($log->metadata)->except(['ip', 'user_agent', 'platform'])->map(fn ($v, $k) => "$k: $v")->take(3)->implode(' · ') ?: '—' }}
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="px-3 py-8"><x-empty-state :title="$hasActiveFilters ? 'No matching events' : 'No audit events yet'" /></td></tr>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$logs" />
    </x-page-shell>
</div>
