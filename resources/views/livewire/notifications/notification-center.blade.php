<div>
    <x-page-shell title="Notification Center" :breadcrumbs="[['label' => 'Notifications']]">
        <div class="space-y-5">
            @if ($metrics)
                <dl class="grid grid-cols-2 gap-3 sm:grid-cols-4" aria-label="Alarm totals">
                    @foreach (['total' => 'Total alarms', 'critical' => 'Critical', 'pending' => 'Awaiting acknowledgment', 'escalated' => 'Escalated', 'resolved' => 'Resolved', 'breaches' => 'Acknowledgment SLA breaches', 'escalation_failures' => 'Escalation needs a recipient'] as $key => $label)
                        <div class="card-surface p-3"><dt class="text-xs">{{ $label }}</dt><dd class="mt-1 text-xl font-semibold">{{ $metrics->{$key} }}</dd></div>
                    @endforeach
                    <div class="card-surface p-3"><dt class="text-xs">Average acknowledgment</dt><dd class="mt-1 text-xl font-semibold">{{ $metrics->acknowledgment_seconds === null ? '—' : round($metrics->acknowledgment_seconds).' s' }}</dd></div>
                    <div class="card-surface p-3"><dt class="text-xs">Average resolution</dt><dd class="mt-1 text-xl font-semibold">{{ $metrics->resolution_seconds === null ? '—' : round($metrics->resolution_seconds).' s' }}</dd></div>
                </dl>
            @endif
            @if ($canConfigure)
                <section class="card-surface p-4" aria-labelledby="broadcast-delivery-heading">
                    <h2 id="broadcast-delivery-heading" class="font-semibold">Alarm broadcast delivery</h2>
                    <dl class="mt-2 grid grid-cols-2 gap-3 text-sm sm:grid-cols-4">
                        <div><dt>Pending</dt><dd class="font-semibold">{{ $deliveryMetrics->pending }}</dd></div>
                        <div><dt>Handed to broadcaster</dt><dd class="font-semibold">{{ $deliveryMetrics->sent }}</dd></div>
                        <div><dt>Failed</dt><dd class="font-semibold">{{ $deliveryMetrics->failed }}</dd></div>
                        <div><dt>Send attempts</dt><dd class="font-semibold">{{ $deliveryMetrics->attempts }}</dd></div>
                    </dl>
                </section>
                <details class="card-surface p-4">
                    <summary class="cursor-pointer font-semibold">Alarm broadcast failures <span class="font-normal">({{ $broadcastFailures }})</span></summary>
                    @if ($recentBroadcastFailures->isEmpty())
                        <p class="mt-2 text-sm">No failures in the selected date range.</p>
                    @else
                        <ul class="mt-2 list-disc space-y-1 pl-5 text-sm">
                            @foreach ($recentBroadcastFailures as $failure)
                                <li>Failed {{ $failure->created_at->format('Y-m-d H:i:s T') }} · Audit record {{ $failure->id }}</li>
                            @endforeach
                        </ul>
                    @endif
                </details>
                <details class="card-surface p-4">
                    <summary class="cursor-pointer font-semibold">Alarm policies</summary>
                    <form wire:submit="savePolicy" class="mt-4 flex flex-wrap items-end gap-4">
                        <label class="grid gap-1 text-sm">Event type
                            <select wire:model.live="policyEvent" class="rounded-lg border border-zinc-300 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="">Select an event</option>
                                @foreach (config('notifications.operational_events') as $type => $definition)
                                    <option value="{{ $type }}">{{ $type }} ({{ $definition['severity'] }})</option>
                                @endforeach
                            </select>
                        </label>
                        @if ($policyEvent && $policy)
                            <label class="grid gap-1 text-sm">Acknowledgment deadline (seconds)
                                <input type="number" min="10" max="86400" wire:model="policy.acknowledgment_seconds" class="rounded-lg border border-zinc-300 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            </label>
                            <label class="grid gap-1 text-sm">Queue priority
                                <input type="number" min="0" max="255" wire:model="policy.priority" class="w-28 rounded-lg border border-zinc-300 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            </label>
                            <label class="grid gap-1 text-sm">Escalation recipient permission
                                <select wire:model="policy.escalation_permission" class="rounded-lg border border-zinc-300 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                                    @foreach ($escalationPermissions as $permission)
                                        <option value="{{ $permission }}">{{ $permission }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="grid gap-1 text-sm">Backup delay after deadline (seconds)
                                <input type="number" min="10" max="86400" wire:model="policy.backup_delay_seconds" placeholder="Optional" class="rounded-lg border border-zinc-300 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                            </label>
                            <label class="grid gap-1 text-sm">Backup recipient permission
                                <select wire:model="policy.backup_permission" class="rounded-lg border border-zinc-300 bg-white p-2 dark:border-zinc-700 dark:bg-zinc-900">
                                    @foreach ($escalationPermissions as $permission)
                                        <option value="{{ $permission }}">{{ $permission }}</option>
                                    @endforeach
                                </select>
                            </label>
                            <x-button type="submit" size="sm" wire:loading.attr="disabled">Save policy</x-button>
                        @endif
                        @if ($errors->any()) <p role="alert" class="w-full text-sm text-red-700 dark:text-red-300">{{ $errors->first() }}</p> @endif
                    </form>
                </details>
            @endif
            <div class="flex flex-wrap gap-4">
                <label class="text-sm font-medium">
                    Status
                    <select wire:model.live="filter" class="ml-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                        <option value="all">All</option>
                        <option value="unread">Unread</option>
                        @if ($canArchive)<option value="archived">Archived</option>@endif
                        @if ($showAlarms)
                            <option value="critical">Critical</option>
                            <option value="pending">Requires acknowledgment</option>
                            <option value="acknowledged">Acknowledged</option>
                            <option value="resolved">Resolved</option>
                        @endif
                    </select>
                </label>
                <label class="text-sm font-medium">From
                    <input type="date" wire:model.live="fromDate" class="ml-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                </label>
                <label class="text-sm font-medium">Through
                    <input type="date" wire:model.live="toDate" class="ml-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                </label>
                @if ($showAlarms && $alarms)
                    <label class="text-sm font-medium">Alarm severity
                        <select wire:model.live="severity" class="ml-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="">All severities</option>
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="warning">Warning</option>
                            <option value="info">Info</option>
                        </select>
                    </label>
                    <label class="text-sm font-medium">
                        Alarm event
                        <select wire:model.live="eventType" class="ml-2 rounded-lg border border-zinc-300 bg-white px-3 py-2 dark:border-zinc-700 dark:bg-zinc-900">
                            <option value="">All events</option>
                            @foreach (array_keys(config('notifications.operational_events')) as $type)
                                <option value="{{ $type }}">{{ str($type)->replace('.', ' ')->headline() }}</option>
                            @endforeach
                        </select>
                    </label>
                @endif
            </div>
            @if ($showAlarms && $alarms)
                <section class="card-surface divide-y divide-zinc-200 dark:divide-zinc-800" aria-labelledby="center-alarms">
                    <h2 id="center-alarms" class="p-4 font-semibold">Operational alarms</h2>
                    @forelse ($alarms as $alarm)
                        <article wire:key="alarm-history-{{ $alarm->id }}" class="space-y-2 p-4">
                            <p class="text-xs font-bold uppercase {{ $alarm->severity === 'critical' ? 'text-red-700 dark:text-red-300' : 'text-amber-700 dark:text-amber-300' }}">{{ $alarm->severity }} · {{ $alarm->event_type }}</p>
                            <a class="font-semibold underline" href="{{ $alarm->action_url }}">{{ $alarm->title }}</a>
                            <p class="text-sm">{{ $alarm->instructions }}</p>
                            @if ($alarm->context)
                                <dl class="flex flex-wrap gap-x-4 gap-y-1 text-sm">
                                    @foreach ($alarm->context as $label => $value)
                                        <div><dt class="inline font-semibold">{{ $label }}</dt> <dd class="inline">{{ $value }}</dd></div>
                                    @endforeach
                                </dl>
                            @endif
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">
                                Received {{ $alarm->created_at->format('Y-m-d H:i T') }}
                                @if ($alarm->acknowledged_at) · Acknowledged {{ $alarm->acknowledged_at->format('Y-m-d H:i T') }}{{ $alarm->acknowledgedBy ? ' by '.$alarm->acknowledgedBy->name : '' }} @endif
                                @if ((($alarm->escalation_failed_at && ! $alarm->backup_escalated_at) || $alarm->backup_escalation_failed_at) && ! $alarm->resolved_at && ! $alarm->acknowledged_at) · Escalation needs an eligible recipient @endif
                                @if ($alarm->escalated_at) · Escalated {{ $alarm->escalated_at->format('Y-m-d H:i T') }} @endif
                                @if ($alarm->backup_escalated_at) · Backup notified {{ $alarm->backup_escalated_at->format('Y-m-d H:i T') }} @endif
                                @if ($alarm->resolved_at) · Resolved {{ $alarm->resolved_at->format('Y-m-d H:i T') }} @endif
                            </p>
                            @if (! $alarm->acknowledged_at && ! $alarm->resolved_at)
                                <x-button size="sm" wire:click="acknowledge({{ $alarm->id }})" wire:loading.attr="disabled">Acknowledge alarm</x-button>
                            @endif
                        </article>
                    @empty
                        <p class="p-4 text-sm">No matching alarms.</p>
                    @endforelse
                    @if ($alarms->hasPages()) <div class="p-4">{{ $alarms->links() }}</div> @endif
                </section>
            @endif
            @if ($notifications)
                <section class="card-surface divide-y divide-zinc-200 dark:divide-zinc-800" aria-labelledby="center-inbox">
                    <h2 id="center-inbox" class="p-4 font-semibold">Inbox</h2>
                    @forelse ($notifications as $notification)
                        <article wire:key="inbox-{{ $notification->id }}" class="space-y-2 p-4">
                            <a class="font-semibold underline" href="{{ $notification->data['action_url'] ?? '#' }}">{{ $notification->data['title'] ?? 'Notification' }}</a>
                            <p class="text-xs font-semibold uppercase">{{ $notification->data['severity'] ?? 'info' }}{{ isset($notification->data['event_type']) ? ' · '.$notification->data['event_type'] : '' }}</p>
                            <p class="text-sm">{{ $notification->data['body'] ?? '' }}</p>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400">{{ $notification->created_at->format('Y-m-d H:i T') }} · {{ $notification->read_at ? 'Read' : 'Unread' }}</p>
                            @if ($canArchive && $notification->archived_at)
                                <x-button size="sm" wire:click="restore('{{ $notification->id }}')">Restore</x-button>
                            @elseif ($canArchive)
                                <x-button size="sm" variant="secondary" wire:click="archive('{{ $notification->id }}')">Archive</x-button>
                            @endif
                            @if (! $notification->read_at)
                                <x-button size="sm" wire:click="read('{{ $notification->id }}')">Mark read</x-button>
                            @else
                                <x-button size="sm" wire:click="unread('{{ $notification->id }}')">Mark unread</x-button>
                            @endif
                        </article>
                    @empty
                        <p class="p-4 text-sm">No matching notifications.</p>
                    @endforelse
                    @if ($notifications->hasPages()) <div class="p-4">{{ $notifications->links() }}</div> @endif
                </section>
            @endif
        </div>
    </x-page-shell>
</div>
