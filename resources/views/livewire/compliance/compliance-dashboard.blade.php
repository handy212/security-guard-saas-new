<div>
    <x-page-shell title="Compliance Dashboard" description="Certifications and documents nearing expiry.">
        <div class="stat-grid">
            <x-stat-card compact label="Expiring certs" :value="$items->count()" icon="guards" :tone="$items->count() ? 'warning' : 'success'" />
            <x-stat-card compact label="Expiring docs" :value="$documents->count()" icon="billing" :tone="$documents->count() ? 'warning' : 'success'" />
            <x-stat-card compact label="Certifications" :value="$certifications->count()" icon="check" />
            <x-stat-card compact label="Window" value="30 days" icon="plan" tone="info" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-section-card title="Expiring certifications">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Certification</x-table.th>
                            <x-table.th>Expires</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($items as $item)
                            <tr class="table-row-hover" wire:key="cert-{{ $item->id }}">
                                <x-table.td>{{ $item->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td muted>{{ $item->name }}</x-table.td>
                                <x-table.td mono>{{ $item->expires_at?->format('M j, Y') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3"><x-empty-state compact title="No expiring certs" /></x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>

            <x-section-card title="Expiring documents">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Type</x-table.th>
                            <x-table.th>Expires</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($documents as $doc)
                            <tr class="table-row-hover" wire:key="doc-{{ $doc->id }}">
                                <x-table.td>{{ $doc->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td muted>{{ $doc->type }}</x-table.td>
                                <x-table.td mono>{{ $doc->expires_at?->format('M j, Y') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3"><x-empty-state compact title="No expiring documents" /></x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>
    </x-page-shell>
</div>
