<div>
    <x-page-shell
        :title="$guard->full_name"
        :description="$guard->employee_number ? 'Employee #'.$guard->employee_number : 'Guard profile'"
        :breadcrumbs="[
            ['label' => 'Guards', 'href' => route('guards.index')],
            ['label' => $guard->full_name],
        ]"
    >
        <x-slot:actions>
            @if ($idCardEligibility['can_download'])
                <x-button variant="secondary" :href="route('guards.id-card.print', $guard)" target="_blank">Print ID card</x-button>
            @elseif ($idCardEligibility['action'])
                <x-button variant="secondary" wire:click="setTab('overview')" title="{{ $idCardEligibility['message'] }}">Set up ID card</x-button>
            @endif
            <x-button variant="secondary" :href="route('guards.index')">Back to roster</x-button>
        </x-slot:actions>

        <x-flash-status type="success" />

        @error('verification')
            <div class="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ $message }}
            </div>
        @enderror

        <x-profile-meta :items="array_filter([
            ['type' => 'badge', 'value' => $guard->status],
            ['type' => 'badge', 'value' => $guard->verification_status],
            ['type' => 'text', 'value' => $guard->dutyTypeLabel()],
            $guard->branch ? ['type' => 'text', 'value' => $guard->branch->name] : null,
            $guard->rank ? ['type' => 'text', 'value' => $guard->rank] : null,
        ])" />

        <x-profile-layout :tabs="$profileTabs" :active="$activeTab">

        @if ($activeTab === 'overview')
            <div class="space-y-3">
                <x-section-card title="Last 7 days" flush>
                    <div class="overview-stat-grid">
                        <x-stat-card compact label="Shifts completed" :value="$stats['shifts_completed']" icon="schedules" />
                        <x-stat-card compact label="Hours worked" :value="$stats['hours_worked']" icon="schedules" tone="info" />
                        <x-stat-card compact label="Patrols" :value="$stats['patrols_completed']" icon="patrols" />
                        <x-stat-card compact label="Incidents" :value="$stats['incidents_reported']" icon="incidents" :tone="$stats['incidents_reported'] > 0 ? 'warning' : 'default'" />
                        <button type="button" wire:click="setTab('sites')" class="min-w-0 text-left">
                            <x-stat-card compact label="Assigned sites" :value="$stats['sites_assigned']" icon="sites" class="h-full w-full" />
                        </button>
                    </div>
                </x-section-card>

                <div class="stat-grid">
                    @foreach ($statusMetrics as $metric)
                        <div class="card-surface flex min-w-0 items-center gap-3 px-4 py-3" wire:key="status-metric-{{ $loop->index }}">
                            <div class="min-w-0 flex-1">
                                <div @class([
                                    'text-lg font-semibold tracking-tight',
                                    'text-emerald-700 dark:text-emerald-400' => ($metric['tone'] ?? '') === 'success',
                                    'text-amber-700 dark:text-amber-400' => ($metric['tone'] ?? '') === 'warning',
                                    'text-zinc-900 dark:text-zinc-50' => ! in_array($metric['tone'] ?? '', ['success', 'warning'], true),
                                ])>{{ $metric['value'] }}</div>
                                <div class="mt-0.5 text-xs font-medium text-zinc-500">{{ $metric['label'] }}</div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <div class="overview-panel-grid">
                    <x-section-card title="General information">
                        <dl class="divide-y divide-zinc-100 text-sm dark:divide-zinc-800">
                            <div class="flex justify-between gap-4 py-2.5 first:pt-0">
                                <dt class="text-zinc-500">Branch</dt>
                                <dd class="text-right font-medium text-zinc-900 dark:text-zinc-100">{{ $guard->branch?->name ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-zinc-500">Duty</dt>
                                <dd class="text-right text-zinc-900 dark:text-zinc-100">{{ $guard->dutyTypeLabel() }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-zinc-500">Rank</dt>
                                <dd class="text-right text-zinc-900 dark:text-zinc-100">{{ $guard->rank ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-zinc-500">Phone</dt>
                                <dd class="text-right text-zinc-900 dark:text-zinc-100">{{ $guard->phone ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5">
                                <dt class="text-zinc-500">Hire date</dt>
                                <dd class="text-right text-zinc-900 dark:text-zinc-100">{{ $guard->hire_date?->format('M j, Y') ?? '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4 py-2.5 last:pb-0">
                                <dt class="text-zinc-500">Assigned sites</dt>
                                <dd class="text-right text-zinc-900 dark:text-zinc-100">{{ $stats['sites_assigned'] }}</dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <x-button size="sm" variant="secondary" wire:click="setTab('profile')">Edit profile</x-button>
                            <x-button size="sm" variant="secondary" wire:click="setTab('sites')">Assign sites</x-button>
                            <x-button size="sm" variant="secondary" wire:click="setTab('files')">Upload docs</x-button>
                            <x-button size="sm" variant="secondary" wire:click="setTab('qualifications')">Qualifications</x-button>
                        </div>
                    </x-section-card>

                    <x-section-card title="Know Your Guard">
                        <ul class="divide-y divide-zinc-100 dark:divide-zinc-800">
                            @foreach($checklist['items'] as $item)
                                <li class="flex items-center justify-between gap-2 py-2.5 text-sm first:pt-0 last:pb-0">
                                    <div class="flex min-w-0 items-center gap-2.5">
                                        @if ($item['passed'])
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/15 dark:bg-emerald-950/40 dark:text-emerald-300">
                                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                            </span>
                                            <span class="text-zinc-600 dark:text-zinc-300">
                                                {{ $item['label'] }}
                                                @if (! empty($item['optional']))
                                                    <span class="text-xs font-normal text-zinc-400">(optional)</span>
                                                @endif
                                            </span>
                                        @else
                                            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full border border-zinc-200 text-zinc-300 dark:border-zinc-700">○</span>
                                            <span class="{{ ! empty($item['optional']) ? 'text-zinc-500 dark:text-zinc-400' : 'font-medium text-zinc-900 dark:text-zinc-100' }}">
                                                {{ $item['label'] }}
                                                @if (! empty($item['optional']))
                                                    <span class="text-xs font-normal text-zinc-400">(optional)</span>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                    @if (! $item['passed'] && ! empty($item['tab']))
                                        <button type="button" wire:click="setTab('{{ $item['tab'] }}')" class="shrink-0 text-xs font-semibold text-accent-700 hover:underline">
                                            {{ ! empty($item['optional']) ? 'Add →' : 'Fix →' }}
                                        </button>
                                    @endif
                                </li>
                            @endforeach
                        </ul>

                        <div class="mt-4 flex flex-wrap gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            @if ($guard->verification_status !== 'verified' && $guard->verification_status !== 'suspended')
                                <x-button wire:click="submitForReview" variant="secondary" size="sm">Submit for review</x-button>
                            @endif
                            @if ($checklist['ready'] && $guard->verification_status !== 'verified' && $guard->verification_status !== 'suspended')
                                <x-button wire:click="markVerified" size="sm">Mark verified</x-button>
                            @endif
                            @if ($guard->verification_status === 'verified')
                                <x-button wire:click="suspend" variant="danger" size="sm" wire:confirm="Suspend verification? The QR code will stay the same and show suspended when scanned.">Suspend</x-button>
                            @endif
                            @if ($guard->verification_status === 'suspended')
                                <x-button wire:click="reinstate" size="sm" wire:confirm="Reinstate verification? The existing QR code will keep working.">Reinstate</x-button>
                            @endif
                        </div>

                        @if (in_array($guard->verification_status, ['verified', 'suspended'], true) && $verifyUrl && $qrSvg)
                            <div class="mt-4 flex items-start gap-4 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                                <div class="rounded-lg border border-zinc-200 bg-white p-2 dark:border-zinc-700">{!! $qrSvg !!}</div>
                                <div class="text-xs text-zinc-500">
                                    <p class="break-all">{{ $verifyUrl }}</p>
                                    @if ($lastScannedAt)
                                        <p class="mt-1">Last scanned {{ $lastScannedAt->format('M j, Y g:i A') }}</p>
                                    @endif
                                    @if ($guard->verification_status === 'verified')
                                        <x-button wire:click="rotateQrToken" variant="secondary" size="sm" class="mt-2" wire:confirm="Rotate QR code? Printed cards will need reprinting.">Rotate QR</x-button>
                                    @elseif ($guard->verification_status === 'suspended')
                                        <p class="mt-2 font-medium text-red-600">Suspended — scans show suspended status. Reinstate to restore normal verification.</p>
                                    @endif
                                </div>
                            </div>
                        @elseif ($guard->verification_status === 'verified')
                            <x-button wire:click="issueQrToken" size="sm" class="mt-4">Issue QR code</x-button>
                        @endif
                    </x-section-card>

                    <x-section-card title="ID card preview">
                        @if ($idCardEligibility['can_download'])
                            <div class="mb-3 flex justify-center">
                                <x-segment-control field="idCardPreviewSide" :active="$idCardPreviewSide" :options="['front' => 'Front', 'back' => 'Back']" />
                            </div>
                            <div class="flex justify-center" wire:key="card-{{ $idCardPreviewSide }}-{{ $guard->id }}">
                                <x-guard-id-card-preview :brand="$idCardBrand" :card="$idCardData" :side="$idCardPreviewSide" :photo-url="$photoUrl" :logo-url="$idCardBrand['logo_url']" :qr-svg="$qrSvg" />
                            </div>
                            <x-button :href="route('guards.id-card.print', $guard)" target="_blank" class="mt-4 w-full justify-center" size="sm">Print ID card</x-button>
                        @else
                            <x-empty-state compact :title="$idCardEligibility['message'] ?? 'ID card unavailable'" description="Complete KYG verification to enable ID cards." />
                        @endif
                    </x-section-card>
                </div>

                <div class="grid gap-4 md:grid-cols-2">
                    <x-section-card title="Recent shifts">
                        @forelse ($recentActivity['shifts'] as $assignment)
                            <div class="border-t border-zinc-100 py-3 text-sm first:border-t-0 dark:border-zinc-800" wire:key="recent-shift-{{ $assignment->id }}">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->shift?->site?->name ?? 'Site' }}</div>
                                <div class="text-xs text-zinc-500">{{ $assignment->shift?->starts_at?->format('M j, Y g:i A') }} · {{ ucfirst(str_replace('_', ' ', \App\Support\EnumHelper::value($assignment->status))) }}</div>
                            </div>
                        @empty
                            <x-empty-state compact title="No recent shifts" description="Shifts from the last 14 days appear here." />
                        @endforelse
                    </x-section-card>

                    <x-section-card title="Recent incidents">
                        @forelse ($recentActivity['incidents'] as $incident)
                            <div class="border-t border-zinc-100 py-3 text-sm first:border-t-0 dark:border-zinc-800" wire:key="recent-inc-{{ $incident->id }}">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $incident->title ?? 'Incident #'.$incident->id }}</div>
                                <div class="text-xs text-zinc-500">{{ $incident->created_at?->format('M j, Y g:i A') }} · {{ ucfirst(str_replace('_', ' ', \App\Support\EnumHelper::value($incident->status))) }}</div>
                            </div>
                        @empty
                            <x-empty-state compact title="No recent incidents" description="Incidents linked to this guard’s shifts appear here." />
                        @endforelse
                    </x-section-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'profile')
            <div class="space-y-4">
                <div class="grid gap-4 lg:grid-cols-3">
                    <x-section-card title="Photo">
                        @if ($guard->photo_path)
                            <img src="{{ route('files.guard-photo', $guard) }}" alt="" class="mx-auto h-36 w-28 rounded-xl border border-zinc-200 object-cover shadow-sm dark:border-zinc-700">
                        @else
                            <div class="mx-auto flex h-36 w-28 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-2xl font-semibold text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800">
                                {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                            </div>
                        @endif
                        <form wire:submit="uploadPhoto" class="mt-3 space-y-2">
                            <input wire:model="photoFile" type="file" accept="image/*" class="form-input text-xs">
                            @error('photoFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <x-button type="submit" size="sm">Upload photo</x-button>
                        </form>
                    </x-section-card>

                    <div class="lg:col-span-2">
                        <x-form-card title="Personal details">
                            <form wire:submit="saveProfile" class="grid gap-3 sm:grid-cols-2">
                                <x-input wire:model="profileForm.employee_number" label="Employee #" />
                                <x-select wire:model="profileForm.status" label="Status">
                                    <option value="active">Active</option>
                                    <option value="inactive">Inactive</option>
                                </x-select>
                                <x-input wire:model="profileForm.first_name" label="First name" />
                                <x-input wire:model="profileForm.last_name" label="Last name" />
                                <x-input wire:model="profileForm.phone" label="Phone" />
                                <x-input wire:model="profileForm.email" label="Email" type="email" />
                                <x-input wire:model="profileForm.monthly_rate" label="Monthly rate" type="number" step="0.01" />
                                <x-input wire:model="profileForm.hire_date" label="Hire date" type="date" />
                                <x-select wire:model="profileForm.user_id" label="Linked user account" class="sm:col-span-2">
                                    <option value="">None</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->email }})</option>
                                    @endforeach
                                </x-select>
                                <x-input wire:model="profileForm.emergency_contact_name" label="Emergency contact" />
                                <x-input wire:model="profileForm.emergency_contact_phone" label="Emergency phone" />
                                <div class="sm:col-span-2">
                                    <x-button type="submit">Save profile</x-button>
                                </div>
                            </form>
                        </x-form-card>
                    </div>
                </div>

                <div class="grid gap-4 lg:grid-cols-2">
                    <x-form-card title="Department" description="Branch, duty type, and rank.">
                        <form wire:submit="saveDepartment" class="space-y-3">
                            <div>
                                <x-select wire:model="departmentForm.branch_id" label="Branch / department">
                                    <option value="">None</option>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                    @endforeach
                                </x-select>
                                <a href="{{ route('settings.branches') }}" class="mt-1 inline-block text-xs font-medium text-accent-600 hover:underline">Manage branches</a>
                            </div>
                            <x-select wire:model="departmentForm.duty_type" label="Duty type">
                                @foreach($dutyTypes as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                            <x-input wire:model="departmentForm.rank" label="Rank / title" />
                            <x-button type="submit" size="sm">Save department</x-button>
                        </form>
                    </x-form-card>

                    <x-form-card title="Preferences" description="Assignment visibility and notifications.">
                        <form wire:submit="saveSettings" class="space-y-4">
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="settingsForm.show_current_assignment" class="rounded border-zinc-300 dark:border-zinc-600">
                                Show current assignment on public KYG page
                            </label>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="settingsForm.notify_on_shift_change" class="rounded border-zinc-300 dark:border-zinc-600">
                                Notify on shift changes
                            </label>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="settingsForm.allow_open_shift_bids" class="rounded border-zinc-300 dark:border-zinc-600">
                                Allow open shift bids
                            </label>
                            <x-select wire:model="settingsForm.preferred_contact_method" label="Preferred contact method">
                                <option value="phone">Phone</option>
                                <option value="email">Email</option>
                                <option value="sms">SMS</option>
                            </x-select>
                            <x-button type="submit" size="sm">Save preferences</x-button>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'availability')
            <div class="page-split">
                <x-section-card title="Weekly availability">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Day</x-table.th>
                                <x-table.th responsive="md">Hours</x-table.th>
                                <x-table.th>Available</x-table.th>
                                <x-table.th align="right" class="w-24"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($guard->availabilities as $slot)
                                <tr wire:key="avail-{{ $slot->id }}">
                                    <x-table.td class="font-medium">{{ $weekdays[$slot->weekday] ?? $slot->weekday }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ substr($slot->starts_at, 0, 5) }} – {{ substr($slot->ends_at, 0, 5) }}</x-table.td>
                                    <x-table.td><x-badge :status="$slot->is_available ? 'active' : 'inactive'" /></x-table.td>
                                    <x-table.td align="right" class="space-x-2">
                                        <button type="button" wire:click="editAvailability({{ $slot->id }})" class="text-xs font-medium text-accent-600 hover:underline">Edit</button>
                                        <button type="button" wire:click="deleteAvailability({{ $slot->id }})" wire:confirm="Remove?" class="text-xs text-red-600 hover:underline">Remove</button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="4">
                                    <x-empty-state compact title="No availability set" description="Define when this guard is available for shifts." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card :title="$editingAvailabilityId ? 'Edit slot' : 'Add availability'">
                    <form wire:submit="saveAvailability" class="space-y-3">
                        <x-select wire:model="availabilityForm.weekday" label="Day">
                            @foreach ($weekdays as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="availabilityForm.starts_at" label="Start" type="time" />
                        <x-input wire:model="availabilityForm.ends_at" label="End" type="time" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="availabilityForm.is_available" class="rounded border-zinc-300">
                            Available
                        </label>
                        <x-button type="submit" size="sm">{{ $editingAvailabilityId ? 'Update' : 'Add' }} slot</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'files')
            <div class="profile-form-split">
                <x-section-card title="Documents on file">
                    @forelse($guard->documents as $doc)
                        <div wire:key="doc-{{ $doc->id }}" class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0">
                            <div>
                                <div class="font-medium">{{ $doc->typeLabel() }}</div>
                                <div class="text-xs text-zinc-500">{{ $doc->expires_at?->format('M j, Y') ?? 'No expiry' }}</div>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" wire:click="openDocumentPreview({{ $doc->id }})" class="text-xs font-medium text-accent-600 hover:underline">View</button>
                                <button type="button" wire:click="deleteDocument({{ $doc->id }})" wire:confirm="Delete this document?" class="text-xs text-red-600 hover:underline">Delete</button>
                            </div>
                        </div>
                    @empty
                        <x-empty-state compact title="No documents" description="Upload ID, police clearance, and license documents." />
                    @endforelse
                </x-section-card>

                <x-form-card title="Upload document">
                    <form wire:submit="uploadDocument" class="space-y-3">
                        <x-select wire:model="documentForm.type" label="Type">
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="documentForm.expires_at" label="Expires" type="date" />
                        <input wire:model="documentFile" type="file" class="form-input text-sm" accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx">
                        @error('documentFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <p class="text-xs text-zinc-500">Include police clearance and ID for KYG vetting.</p>
                        <x-button type="submit">Upload</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'sites')
            <div class="page-split">
                <x-section-card title="Assigned sites">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Site</x-table.th>
                                <x-table.th responsive="md">Client</x-table.th>
                                <x-table.th>Primary</x-table.th>
                                <x-table.th align="right" class="w-20"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($guard->siteAssignments as $assignment)
                                <tr wire:key="site-assign-{{ $assignment->id }}">
                                    <x-table.td class="font-medium">
                                        <a href="{{ route('sites.show', $assignment->site) }}" class="text-accent-700 hover:underline">{{ $assignment->site?->name }}</a>
                                    </x-table.td>
                                    <x-table.td responsive="md" muted>{{ $assignment->site?->clientAccount?->name ?? '—' }}</x-table.td>
                                    <x-table.td>{{ $assignment->is_primary ? 'Yes' : '—' }}</x-table.td>
                                    <x-table.td align="right">
                                        <button type="button" wire:click="removeSiteAssignment({{ $assignment->id }})" wire:confirm="Remove?" class="text-xs text-red-600 hover:underline">Remove</button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="4">
                                    <x-empty-state compact title="No sites assigned" description="Assign this guard to post sites." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card title="Assign site">
                    <form wire:submit="assignSite" class="space-y-3">
                        <x-select wire:model="siteAssignForm.site_id" label="Site">
                            <option value="">Select site…</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}">{{ $site->name }}</option>
                            @endforeach
                        </x-select>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="siteAssignForm.is_primary" class="rounded border-zinc-300"> Primary site
                        </label>
                        <x-input wire:model="siteAssignForm.notes" label="Notes" />
                        <x-button type="submit" size="sm">Assign site</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'qualifications')
            <div class="profile-stack space-y-4">
                <x-form-card title="Security license">
                    <form wire:submit="saveLicense" class="grid max-w-2xl gap-3 sm:grid-cols-2">
                        <x-input wire:model="licenseForm.license_number" label="License number" />
                        <x-input wire:model="licenseForm.license_expires_at" label="Expires" type="date" />
                        <div class="sm:col-span-2">
                            <x-button type="submit" size="sm">Save license</x-button>
                        </div>
                    </form>
                </x-form-card>

                <div class="profile-form-split">
                    <x-section-card title="Certifications">
                        @forelse($guard->certifications as $cert)
                            <div class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0 dark:border-zinc-800">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $cert->name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $cert->expires_at?->format('M j, Y') ?? 'No expiry' }}</div>
                                </div>
                                <button wire:click="deleteCertification({{ $cert->id }})" wire:confirm="Remove?" class="text-xs text-red-600">Remove</button>
                            </div>
                        @empty
                            <x-empty-state compact title="No certifications" description="Add security certifications for this guard." />
                        @endforelse
                    </x-section-card>

                    <x-form-card title="Add certification">
                        <form wire:submit="saveCertification" class="space-y-3">
                            <x-input wire:model="certForm.name" label="Name" />
                            <x-input wire:model="certForm.issuer" label="Issuer" />
                            <x-input wire:model="certForm.issued_at" label="Issued" type="date" />
                            <x-input wire:model="certForm.expires_at" label="Expires" type="date" />
                            <x-button type="submit" size="sm">Add certification</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Skills">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Skill</x-table.th>
                                    <x-table.th>Level</x-table.th>
                                    <x-table.th align="right" class="w-20"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse($guard->skills as $skill)
                                    <tr wire:key="skill-{{ $skill->id }}">
                                        <x-table.td class="font-medium">{{ $skill->skill }}</x-table.td>
                                        <x-table.td muted>{{ $skillLevels[$skill->level] ?? $skill->level }}</x-table.td>
                                        <x-table.td align="right">
                                            <button wire:click="deleteSkill({{ $skill->id }})" wire:confirm="Remove?" class="text-xs text-red-600 hover:underline">Remove</button>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="3">
                                        <x-empty-state compact title="No skills" description="Record competencies used for deployment matching." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card title="Add skill">
                        <form wire:submit="saveSkill" class="space-y-3">
                            <x-select wire:model.live="skillForm.skill" label="Skill">
                                <option value="">Select…</option>
                                @foreach ($skillOptions as $skill)
                                    <option value="{{ $skill }}">{{ $skill }}</option>
                                @endforeach
                                <option value="_other">Other</option>
                            </x-select>
                            @if ($skillForm['skill'] === '_other')
                                <x-input wire:model="skillForm.skill_custom" label="Custom skill" />
                            @endif
                            <x-select wire:model="skillForm.level" label="Level">
                                @foreach ($skillLevels as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </x-select>
                            <x-button type="submit" size="sm">Add skill</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="profile-form-split">
                    <x-section-card title="Training history">
                        @forelse($guard->trainingRecords as $row)
                            <div wire:key="training-{{ $row->id }}" class="flex items-center justify-between border-t border-zinc-100 py-2 text-sm first:border-0 dark:border-zinc-800">
                                <div>
                                    <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $row->course_name }}</div>
                                    <div class="text-xs text-zinc-500">{{ $row->completed_on?->format('M j, Y') ?? '—' }}</div>
                                </div>
                                <div class="flex gap-2">
                                    <button type="button" wire:click="editTraining({{ $row->id }})" class="text-xs font-medium text-accent-600 hover:underline">Edit</button>
                                    <button type="button" wire:click="deleteTraining({{ $row->id }})" wire:confirm="Delete this training record?" class="text-xs text-red-600 hover:underline">Delete</button>
                                </div>
                            </div>
                        @empty
                            <x-empty-state compact title="No training records" description="Log completed courses and renewals." />
                        @endforelse
                    </x-section-card>

                    <x-form-card :title="$editingTrainingId ? 'Edit training record' : 'Add training record'">
                        <form wire:submit="saveTraining" class="space-y-3">
                            <x-select wire:model.live="trainingForm.course_name" label="Course">
                                <option value="">Select…</option>
                                @foreach ($trainingCourses as $course)
                                    <option value="{{ $course }}">{{ $course }}</option>
                                @endforeach
                                <option value="_other">Other</option>
                            </x-select>
                            @if ($trainingForm['course_name'] === '_other')
                                <x-input wire:model="trainingForm.course_custom" label="Custom course" />
                            @endif
                            <x-input wire:model="trainingForm.provider" label="Provider" />
                            <x-input wire:model="trainingForm.completed_on" label="Completed" type="date" />
                            <x-input wire:model="trainingForm.expires_on" label="Expires" type="date" />
                            <x-button type="submit" size="sm">{{ $editingTrainingId ? 'Update' : 'Save' }} training</x-button>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'hr')
            <div class="space-y-4">
                <div class="page-split">
                    <x-section-card title="Notes">
                        <div class="space-y-3">
                            @forelse ($guard->notes as $note)
                                <div wire:key="note-{{ $note->id }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4 dark:border-zinc-700 dark:bg-zinc-800/40">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $note->body }}</p>
                                            <p class="mt-2 text-xs text-zinc-500">
                                                {{ $note->author?->name ?? 'System' }} · {{ $note->created_at->format('M j, Y g:i A') }}
                                                @if ($note->is_internal) · <span class="font-medium">Internal</span> @endif
                                            </p>
                                        </div>
                                        <button type="button" wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete?" class="text-xs text-red-600 hover:underline">Delete</button>
                                    </div>
                                </div>
                            @empty
                                <x-empty-state compact title="No notes" description="Add internal notes about this guard." />
                            @endforelse
                        </div>
                    </x-section-card>

                    <x-form-card title="Add note">
                        <form wire:submit="addNote" class="space-y-3">
                            <textarea wire:model="noteForm.body" rows="5" class="form-input"></textarea>
                            @error('noteForm.body') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="noteForm.is_internal" class="rounded border-zinc-300 dark:border-zinc-600"> Internal only
                            </label>
                            <x-button type="submit" size="sm">Add note</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Reminders">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Title</x-table.th>
                                    <x-table.th responsive="md">Due</x-table.th>
                                    <x-table.th>Status</x-table.th>
                                    <x-table.th align="right" class="w-28"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($guard->reminders as $reminder)
                                    <tr wire:key="reminder-{{ $reminder->id }}">
                                        <x-table.td class="font-medium">{{ $reminder->title }}</x-table.td>
                                        <x-table.td responsive="md" muted>{{ $reminder->due_at->format('M j, Y g:i A') }}</x-table.td>
                                        <x-table.td><x-badge :status="$reminder->is_completed ? 'active' : 'pending'" /></x-table.td>
                                        <x-table.td align="right" class="space-x-2">
                                            @unless ($reminder->is_completed)
                                                <button type="button" wire:click="completeReminder({{ $reminder->id }})" class="text-xs font-medium text-emerald-700 hover:underline">Done</button>
                                            @endunless
                                            <button type="button" wire:click="deleteReminder({{ $reminder->id }})" wire:confirm="Delete?" class="text-xs text-red-600 hover:underline">Delete</button>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="4">
                                        <x-empty-state compact title="No reminders" description="Set follow-ups for license renewals, training, etc." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card title="Add reminder">
                        <form wire:submit="addReminder" class="space-y-3">
                            <x-input wire:model="reminderForm.title" label="Title" />
                            <x-input wire:model="reminderForm.due_at" label="Due date" type="datetime-local" />
                            @error('reminderForm.due_at') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            <x-button type="submit" size="sm">Add reminder</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Disciplinary records">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Date</x-table.th>
                                    <x-table.th>Type</x-table.th>
                                    <x-table.th responsive="md">Description</x-table.th>
                                    <x-table.th responsive="lg">Action</x-table.th>
                                    <x-table.th align="right" class="w-20"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($guard->disciplinaryRecords as $record)
                                    <tr wire:key="disc-{{ $record->id }}">
                                        <x-table.td muted>{{ $record->occurred_on?->format('M j, Y') }}</x-table.td>
                                        <x-table.td><x-badge :status="$record->type" /></x-table.td>
                                        <x-table.td responsive="md">{{ $record->description }}</x-table.td>
                                        <x-table.td responsive="lg" muted>{{ $record->action_taken }}</x-table.td>
                                        <x-table.td align="right">
                                            <button type="button" wire:click="deleteDisciplinary({{ $record->id }})" wire:confirm="Delete this record?" class="text-xs text-red-600 hover:underline">Delete</button>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="5">
                                        <x-empty-state compact title="No disciplinary records" description="Warnings and actions are recorded here." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card title="Add record">
                        <form wire:submit="saveDisciplinary" class="space-y-3">
                            <x-input wire:model="disciplinaryForm.occurred_on" label="Date" type="date" />
                            <x-select wire:model="disciplinaryForm.type" label="Type">
                                <option value="warning">Warning</option>
                                <option value="reprimand">Reprimand</option>
                                <option value="suspension">Suspension</option>
                                <option value="termination">Termination</option>
                            </x-select>
                            <x-textarea wire:model="disciplinaryForm.description" label="Description" rows="3" />
                            <x-textarea wire:model="disciplinaryForm.action_taken" label="Action taken" rows="2" />
                            <x-button type="submit" size="sm">Add record</x-button>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif

        </x-profile-layout>
    </x-page-shell>

    @if ($previewDocument)
        <x-modal
            :title="$previewDocument->typeLabel()"
            :description="$previewDocument->expires_at ? 'Expires '.$previewDocument->expires_at->format('M j, Y') : 'No expiry'"
            :width="$previewDocument->isPdfPreview() ? 'xl' : 'lg'"
            closeMethod="closeDocumentPreview"
        >
            @if ($previewDocument->isImagePreview())
                <div class="flex items-center justify-center bg-zinc-100 p-4">
                    <img src="{{ route('files.guard-document', $previewDocument) }}" alt="" class="max-h-[70vh] max-w-full object-contain">
                </div>
            @elseif ($previewDocument->isPdfPreview())
                <iframe src="{{ route('files.guard-document', $previewDocument) }}" class="h-[75vh] w-full border-0"></iframe>
            @else
                <div class="px-5 py-8 text-center">
                    <p class="text-sm text-zinc-600">Preview not available for this file type.</p>
                    <x-button variant="secondary" :href="route('files.guard-document', $previewDocument)" target="_blank" class="mt-4">Download</x-button>
                </div>
            @endif
        </x-modal>
    @endif
</div>
