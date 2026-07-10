<div>
    <x-page-shell title="Guard applications" description="Review applicants who registered through your public application link.">
        <x-slot:actions>
            <x-button variant="secondary" :href="route('guards.kyg')">Know Your Guard</x-button>
            <x-button :href="route('guards.index')">Roster</x-button>
        </x-slot:actions>

        <x-flash-status />

        @if ($publicApplyUrl)
            <section class="card-surface mb-4 flex flex-col gap-3 p-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="min-w-0">
                    <h2 class="text-sm font-semibold text-zinc-900">Public application link</h2>
                    <p class="mt-0.5 text-xs text-zinc-500">Share this tenant-specific link with applicants.</p>
                    <p class="mt-2 break-all font-mono text-xs text-zinc-700">{{ $publicApplyUrl }}</p>
                </div>
                <button
                    type="button"
                    class="btn-secondary shrink-0"
                    x-data
                    x-on:click="navigator.clipboard.writeText(@js($publicApplyUrl)); $el.textContent = 'Copied'; setTimeout(() => $el.textContent = 'Copy link', 1500)"
                >Copy link</button>
            </section>
        @endif

        <x-page-toolbar search="search" searchPlaceholder="Search applicants…">
            <x-slot:tabs>
                <x-segment-control
                    field="statusFilter"
                    :active="$statusFilter"
                    :options="['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected', 'all' => 'All']"
                />
            </x-slot:tabs>
        </x-page-toolbar>

        <x-data-table>
            <x-table.head>
                <tr>
                    <x-table.th>Applicant</x-table.th>
                    <x-table.th responsive="md">Contact</x-table.th>
                    <x-table.th responsive="lg">Duty type</x-table.th>
                    <x-table.th responsive="lg">Branch</x-table.th>
                    <x-table.th>Status</x-table.th>
                    <x-table.th align="right">Action</x-table.th>
                </tr>
            </x-table.head>
            <tbody>
                @forelse($applications as $application)
                    <tr class="table-row-hover" wire:key="app-{{ $application->id }}">
                        <x-table.td>
                            <div class="font-medium text-zinc-900">{{ $application->full_name }}</div>
                            <div class="text-xs text-zinc-500">{{ $application->created_at->diffForHumans() }}</div>
                        </x-table.td>
                        <x-table.td responsive="md" muted>{{ collect([$application->phone, $application->email])->filter()->implode(' · ') ?: '—' }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $application->dutyTypeLabel() }}</x-table.td>
                        <x-table.td responsive="lg" muted>{{ $application->branch?->name ?? '—' }}</x-table.td>
                        <x-table.td><x-badge :status="$application->status" /></x-table.td>
                        <x-table.td align="right">
                            @if ($application->status === 'pending')
                                <div class="flex justify-end gap-2">
                                    <x-button size="sm" wire:click="approve({{ $application->id }})" wire:confirm="Approve and create guard record?">Approve</x-button>
                                    <x-button size="sm" variant="danger" wire:click="reject({{ $application->id }})" wire:confirm="Reject this application?">Reject</x-button>
                                </div>
                            @elseif ($application->guard_id)
                                <x-button size="sm" variant="secondary" :href="route('guards.show', $application->guard_id)">View guard</x-button>
                            @else
                                <span class="text-xs text-zinc-400">—</span>
                            @endif
                        </x-table.td>
                    </tr>
                @empty
                    <x-table.empty colspan="6">
                        <x-empty-state title="No applications" description="Share your public link to start receiving applications." />
                    </x-table.empty>
                @endforelse
            </tbody>
        </x-data-table>

        <x-pagination :paginator="$applications" />
    </x-page-shell>
</div>
