<div>
    <x-page-shell
        title="Compliance Overview"
        description="Expiring credentials, site documents, training, and SLA coverage across your organization."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Compliance'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('compliance.policies')">Policies</x-button>
            <x-button variant="secondary" :href="route('analytics.dashboard')">Analytics</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

        <div class="stat-grid">
            <x-stat-card compact label="Expiring certs" :value="$summary['expiring_certs']" icon="guards" :tone="$summary['expiring_certs'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Guard documents" :value="$summary['expiring_guard_docs']" icon="billing" :tone="$summary['expiring_guard_docs'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Site documents" :value="$summary['expiring_site_docs']" icon="sites" :tone="$summary['expiring_site_docs'] ? 'warning' : 'success'" />
            <x-stat-card compact label="Training due" :value="$summary['expiring_training']" icon="plan" :tone="$summary['expiring_training'] ? 'warning' : 'success'" />
        </div>

        <x-section-card title="SLA coverage" class="!p-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <p class="text-2xl font-semibold text-zinc-900 dark:text-zinc-100">{{ $summary['sla']['coverage_percent'] }}%</p>
                    <p class="text-sm text-zinc-500">
                        {{ $summary['sla']['sites_with_sla'] }} of {{ $summary['sla']['active_sites'] }} active sites have SLA targets
                        · {{ $summary['sla']['requirement_count'] }} requirements
                    </p>
                </div>
                <p class="text-xs text-zinc-500">Per-site SLA targets are managed on each <a href="{{ route('sites.index') }}" class="font-medium text-accent-600 hover:underline">site profile</a>.</p>
            </div>
        </x-section-card>

        <div class="page-grid-2">
            <x-section-card title="Expiring guard certifications">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Certification</x-table.th>
                            <x-table.th>Expires</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($certifications as $item)
                            <tr class="table-row-hover" wire:key="cert-{{ $item->id }}">
                                <x-table.td>
                                    @if ($item->assignedGuard)
                                        <a href="{{ route('guards.show', $item->assignedGuard) }}?tab=qualifications" class="font-medium text-accent-700 hover:underline">{{ $item->assignedGuard->full_name }}</a>
                                    @else
                                        —
                                    @endif
                                </x-table.td>
                                <x-table.td muted>{{ $item->name }}</x-table.td>
                                <x-table.td mono>{{ $item->expires_at?->format('M j, Y') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state compact title="No expiring certifications" description="Nothing due in the next {{ $windowDays }} days.">
                                    <x-slot:actions>
                                        <x-button size="sm" :href="route('guards.index')">Manage guards</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>

            <x-section-card title="Expiring guard documents">
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
                                <x-table.td>
                                    @if ($doc->assignedGuard)
                                        <a href="{{ route('guards.show', $doc->assignedGuard) }}?tab=files" class="font-medium text-accent-700 hover:underline">{{ $doc->assignedGuard->full_name }}</a>
                                    @else
                                        —
                                    @endif
                                </x-table.td>
                                <x-table.td muted>{{ $doc->type }}</x-table.td>
                                <x-table.td mono>{{ $doc->expires_at?->format('M j, Y') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state compact title="No expiring documents" description="Guard files with expiry dates will appear here.">
                                    <x-slot:actions>
                                        <x-button size="sm" :href="route('guards.index')">Manage guards</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>

        <div class="page-grid-2">
            <x-section-card title="Expiring site documents">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Site</x-table.th>
                            <x-table.th>Document</x-table.th>
                            <x-table.th>Expires</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($siteDocuments as $doc)
                            <tr class="table-row-hover" wire:key="site-doc-{{ $doc->id }}">
                                <x-table.td>
                                    @if ($doc->site)
                                        <a href="{{ route('sites.show', $doc->site) }}?tab=files" class="font-medium text-accent-700 hover:underline">{{ $doc->site->name }}</a>
                                    @else
                                        —
                                    @endif
                                </x-table.td>
                                <x-table.td muted>{{ $doc->title }}</x-table.td>
                                <x-table.td mono>{{ $doc->expires_on?->format('M j, Y') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state compact title="No expiring site documents" description="Upload permits and SOPs on each site profile.">
                                    <x-slot:actions>
                                        <x-button size="sm" :href="route('sites.index')">Manage sites</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>

            <x-section-card title="Expiring training">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Course</x-table.th>
                            <x-table.th>Expires</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse($training as $row)
                            <tr class="table-row-hover" wire:key="training-{{ $row->id }}">
                                <x-table.td>
                                    @if ($row->assignedGuard)
                                        <a href="{{ route('guards.show', $row->assignedGuard) }}?tab=qualifications" class="font-medium text-accent-700 hover:underline">{{ $row->assignedGuard->full_name }}</a>
                                    @else
                                        —
                                    @endif
                                </x-table.td>
                                <x-table.td muted>{{ $row->course_name }}</x-table.td>
                                <x-table.td mono>{{ $row->expires_on?->format('M j, Y') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="3">
                                <x-empty-state compact title="No expiring training" description="Record guard training and renewal dates on guard profiles.">
                                    <x-slot:actions>
                                        <x-button size="sm" :href="route('guards.index')">Manage guards</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
