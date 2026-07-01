<div>
    <x-page-shell title="Compliance Policies" description="Escalation rules, data retention, and site SLA requirements." >
        <div class="grid gap-4 lg:grid-cols-3">
            <x-form-card title="Escalation rule" description="Auto-notify supervisors after a delay.">
                <form wire:submit="saveEscalation" class="space-y-3">
                    <x-input wire:model="escalationForm.incident_type" label="Incident type" placeholder="theft, assault…" />
                    <x-select wire:model="escalationForm.severity" label="Severity">
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </x-select>
                    <x-input wire:model="escalationForm.notify_after_minutes" label="Notify after (minutes)" type="number" placeholder="15" />
                    <x-button type="submit" class="w-full">Save rule</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Retention policy" description="How long records are kept.">
                <form wire:submit="saveRetention" class="space-y-3">
                    <x-input wire:model="retentionForm.record_type" label="Record type" placeholder="incidents, patrols…" />
                    <x-input wire:model="retentionForm.retention_days" label="Retention (days)" type="number" placeholder="365" />
                    <x-button type="submit" class="w-full">Save policy</x-button>
                </form>
            </x-form-card>

            <x-form-card title="SLA requirement" description="Per-site performance targets.">
                <form wire:submit="saveSla" class="space-y-3">
                    <x-select wire:model="slaForm.site_id" label="Site">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="slaForm.metric" label="Metric" placeholder="response_time_minutes" />
                    <x-input wire:model="slaForm.target_value" label="Target value" placeholder="5" />
                    <x-button type="submit" class="w-full">Save SLA</x-button>
                </form>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-section-card title="Escalation rules">
                @forelse($escalations as $row)
                    <div class="flex items-center justify-between border-t border-zinc-100 py-3 first:border-0 first:pt-0">
                        <div>
                            <div class="text-sm font-medium text-zinc-900">{{ $row->incident_type ?: 'Any type' }}</div>
                            <div class="text-xs text-zinc-500">Notify after {{ $row->notify_after_minutes }}m</div>
                        </div>
                        <x-badge :status="$row->severity" />
                    </div>
                @empty
                    <x-empty-state title="No rules" description="Add an escalation rule above." />
                @endforelse
            </x-section-card>

            <x-section-card title="Retention policies">
                @forelse($retention as $row)
                    <div class="border-t border-zinc-100 py-3 first:border-0 first:pt-0">
                        <div class="text-sm font-medium text-zinc-900">{{ $row->record_type }}</div>
                        <div class="text-xs text-zinc-500">{{ $row->retention_days }} days</div>
                    </div>
                @empty
                    <x-empty-state title="No policies" description="Add a retention policy above." />
                @endforelse
            </x-section-card>

            <x-section-card title="SLA requirements">
                @forelse($sla as $row)
                    <div class="border-t border-zinc-100 py-3 first:border-0 first:pt-0">
                        <div class="text-sm font-medium text-zinc-900">{{ $row->site?->name ?? 'Site' }}</div>
                        <div class="text-xs text-zinc-500">{{ $row->metric }} = {{ $row->target_value }}</div>
                    </div>
                @empty
                    <x-empty-state title="No SLAs" description="Add an SLA requirement above." />
                @endforelse
            </x-section-card>
        </div>
    </x-page-shell>
</div>
