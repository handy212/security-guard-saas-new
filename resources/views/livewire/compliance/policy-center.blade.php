<div>
    <x-page-shell
        title="Compliance Policies"
        description="Tenant-wide escalation rules and data retention. Site SLA targets are managed on each site profile."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Policies'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" wire:click="openRetentionForm">Add retention</x-button>
            <x-button wire:click="openEscalationForm">Add escalation rule</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-back-office-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="stat-grid">
                <x-stat-card compact label="Escalation rules" :value="$escalations->where('is_active', true)->count()" icon="incidents" />
                <x-stat-card compact label="Retention policies" :value="$retention->count()" icon="billing" />
                <x-stat-card compact label="Inactive rules" :value="$escalations->where('is_active', false)->count()" icon="pause" tone="warning" />
                <x-stat-card compact label="Record types" :value="$retention->pluck('record_type')->unique()->count()" icon="plan" tone="info" />
            </div>

            <div class="page-grid-2">
                <x-section-card
                    title="Escalation rules"
                    description="Notify supervisors when incidents remain open."
                    flush
                >
                    <x-slot:actions>
                        <button type="button" wire:click="openEscalationForm" class="table-action">Add</button>
                    </x-slot:actions>
                    @forelse($escalations as $row)
                        <div class="list-row-start" wire:key="escalation-{{ $row->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ $row->incident_type ?: 'Any incident type' }}</div>
                                <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">
                                    After {{ $row->notify_after_minutes }} min
                                    @if ($row->notify_client) · notifies client @endif
                                </div>
                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                    <x-badge :status="$row->is_active ? 'active' : 'inactive'" />
                                    <x-badge :status="$row->severity" />
                                </div>
                            </div>
                            <div class="table-inline-actions shrink-0">
                                <button type="button" wire:click="toggleEscalation({{ $row->id }})" class="table-action">
                                    {{ $row->is_active ? 'Disable' : 'Enable' }}
                                </button>
                                <button type="button" wire:click="deleteEscalation({{ $row->id }})" wire:confirm="Delete this rule?" class="table-action-danger">Delete</button>
                            </div>
                        </div>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No escalation rules" description="Add a rule to define when supervisors should be notified.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openEscalationForm">Add escalation rule</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>

                <x-section-card
                    title="Retention policies"
                    description="How long records are kept before archival."
                    flush
                >
                    <x-slot:actions>
                        <button type="button" wire:click="openRetentionForm" class="table-action">Add</button>
                    </x-slot:actions>
                    @forelse($retention as $row)
                        <div class="list-row" wire:key="retention-{{ $row->id }}">
                            <div class="min-w-0 flex-1">
                                <div class="text-sm font-medium text-zinc-900 dark:text-zinc-100">{{ ucfirst($row->record_type) }}</div>
                                <div class="text-xs tabular-nums text-zinc-500 dark:text-zinc-400">{{ $row->retention_days }} days</div>
                            </div>
                            <button type="button" wire:click="deleteRetention({{ $row->id }})" wire:confirm="Delete this policy?" class="table-action-danger">Delete</button>
                        </div>
                    @empty
                        <div class="p-3">
                            <x-empty-state compact title="No retention policies" description="Define how long each record type should be kept.">
                                <x-slot:actions>
                                    <x-button size="sm" wire:click="openRetentionForm">Add retention</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        </div>
                    @endforelse
                </x-section-card>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>

    @if ($activeDrawer === 'escalation')
        <x-drawer
            title="Add escalation rule"
            description="Notify supervisors when incidents of a given severity stay open too long."
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="saveEscalation" submit-label="Save rule" close-method="closeDrawer" target="saveEscalation">
                <x-form-section title="Rule">
                    <x-input wire:model="escalationForm.incident_type" label="Incident type" placeholder="Any type if blank" class="sm:col-span-2" />
                    <x-select wire:model="escalationForm.severity" label="Minimum severity *" class="sm:col-span-2">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </x-select>
                    <x-input wire:model="escalationForm.notify_after_minutes" label="Notify after (minutes) *" type="number" min="1" class="sm:col-span-2" />
                    <label class="sm:col-span-2 flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" wire:model="escalationForm.notify_client" class="rounded border-zinc-300 dark:border-zinc-600">
                        Notify client contact
                    </label>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif

    @if ($activeDrawer === 'retention')
        <x-drawer
            title="Add retention policy"
            description="Record how long each record type should be kept. Automated purge is planned."
            width="md"
            close-method="closeDrawer"
        >
            <x-drawer-form wire:submit.prevent="saveRetention" submit-label="Save policy" close-method="closeDrawer" target="saveRetention">
                <x-form-section title="Policy">
                    <x-select wire:model="retentionForm.record_type" label="Record type *" class="sm:col-span-2">
                        <option value="incidents">Incidents</option>
                        <option value="patrols">Patrol sessions</option>
                        <option value="reports">Daily reports</option>
                        <option value="attendance">Attendance logs</option>
                        <option value="audit">Audit trail</option>
                    </x-select>
                    <x-input wire:model="retentionForm.retention_days" label="Retention (days) *" type="number" min="30" class="sm:col-span-2" />
                    <p class="sm:col-span-2 text-xs text-zinc-500 dark:text-zinc-400">
                        Policies are recorded for audit today; automated purge jobs are planned.
                    </p>
                </x-form-section>
            </x-drawer-form>
        </x-drawer>
    @endif
</div>
