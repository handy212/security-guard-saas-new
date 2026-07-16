<div>
    <x-page-shell
        title="Compliance Policies"
        description="Tenant-wide escalation rules and data retention. Site SLA targets are managed on each site profile."
        :breadcrumbs="[
            ['label' => 'Back Office', 'href' => route('billing.hub')],
            ['label' => 'Policies'],
        ]"
    >
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
            <x-form-card title="Escalation rule" description="Notify supervisors when incidents remain open.">
                <form wire:submit="saveEscalation" class="space-y-3">
                    <x-input wire:model="escalationForm.incident_type" label="Incident type (optional)" placeholder="Any type if blank" />
                    <x-select wire:model="escalationForm.severity" label="Minimum severity">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </x-select>
                    <x-input wire:model="escalationForm.notify_after_minutes" label="Notify after (minutes)" type="number" min="1" />
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" wire:model="escalationForm.notify_client" class="rounded border-zinc-300">
                        Notify client contact
                    </label>
                    <x-button type="submit" size="sm">Save rule</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Retention policy" description="How long records are kept before archival.">
                <form wire:submit="saveRetention" class="space-y-3">
                    <x-select wire:model="retentionForm.record_type" label="Record type">
                        <option value="incidents">Incidents</option>
                        <option value="patrols">Patrol sessions</option>
                        <option value="reports">Daily reports</option>
                        <option value="attendance">Attendance logs</option>
                        <option value="audit">Audit trail</option>
                    </x-select>
                    <x-input wire:model="retentionForm.retention_days" label="Retention (days)" type="number" min="30" />
                    <x-button type="submit" size="sm">Save policy</x-button>
                </form>
                <p class="mt-3 text-xs text-zinc-500">Automated purge jobs are planned; policies are recorded for audit purposes today.</p>
            </x-form-card>
        </div>

        <div class="page-grid-2">
            <x-section-card title="Escalation rules">
                @forelse($escalations as $row)
                    <div class="flex items-start justify-between gap-3 border-t border-zinc-100 py-3 first:border-0 first:pt-0" wire:key="escalation-{{ $row->id }}">
                        <div>
                            <div class="text-sm font-medium text-zinc-900">{{ $row->incident_type ?: 'Any incident type' }}</div>
                            <div class="text-xs text-zinc-500">
                                After {{ $row->notify_after_minutes }} min
                                @if ($row->notify_client) · notifies client @endif
                            </div>
                            <x-badge :status="$row->is_active ? 'active' : 'inactive'" class="mt-1" />
                        </div>
                        <div class="flex shrink-0 flex-col items-end gap-1">
                            <x-badge :status="$row->severity" />
                            <button type="button" wire:click="toggleEscalation({{ $row->id }})" class="text-xs font-medium text-accent-600 hover:underline">
                                {{ $row->is_active ? 'Disable' : 'Enable' }}
                            </button>
                            <button type="button" wire:click="deleteEscalation({{ $row->id }})" wire:confirm="Delete this rule?" class="text-xs text-red-600 hover:underline">Delete</button>
                        </div>
                    </div>
                @empty
                    <x-empty-state compact title="No escalation rules" description="Add a rule to define when supervisors should be notified." />
                @endforelse
            </x-section-card>

            <x-section-card title="Retention policies">
                @forelse($retention as $row)
                    <div class="flex items-start justify-between gap-3 border-t border-zinc-100 py-3 first:border-0 first:pt-0" wire:key="retention-{{ $row->id }}">
                        <div>
                            <div class="text-sm font-medium text-zinc-900">{{ ucfirst($row->record_type) }}</div>
                            <div class="text-xs text-zinc-500">{{ $row->retention_days }} days</div>
                        </div>
                        <button type="button" wire:click="deleteRetention({{ $row->id }})" wire:confirm="Delete this policy?" class="text-xs text-red-600 hover:underline">Delete</button>
                    </div>
                @empty
                    <x-empty-state compact title="No retention policies" description="Define how long each record type should be kept." />
                @endforelse
            </x-section-card>
        </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
