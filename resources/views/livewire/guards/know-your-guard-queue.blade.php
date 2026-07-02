<div>
    <x-page-shell title="Know Your Guard" description="Guards awaiting vetting and verification.">
        <div class="stat-grid">
            <x-stat-card compact label="Pending review" :value="$guards->total()" icon="guards" :tone="$guards->total() ? 'warning' : 'success'" />
            <x-stat-card compact label="On this page" :value="$guards->count()" icon="users" />
            <x-stat-card compact label="Page" :value="$guards->currentPage().' / '.$guards->lastPage()" icon="plan" />
            <x-stat-card compact label="Status" value="Awaiting KYG" icon="incidents" tone="info" />
        </div>

        <x-page-toolbar search="search" searchPlaceholder="Search pending guards…" />

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Guard</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th>KYG</x-table.th>
                    <x-table.th align="right">Action</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($guards as $guard)
                    <tr class="table-row-hover" wire:key="kyg-{{ $guard->id }}">
                        <x-table.td><span class="font-medium text-zinc-900">{{ $guard->full_name }}</span></x-table.td>
                        <x-table.td><x-badge :status="$guard->status" /></x-table.td>
                        <x-table.td><x-badge :status="$guard->verification_status" /></x-table.td>
                        <x-table.td align="right">
                            <x-button size="sm" variant="secondary" :href="route('guards.show', $guard).'?tab=verification'">Review</x-button>
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="4">
                        <x-empty-state title="All clear" description="No guards pending Know Your Guard verification." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$guards" />
    </x-page-shell>
</div>
