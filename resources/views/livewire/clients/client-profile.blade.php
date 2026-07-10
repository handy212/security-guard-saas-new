<div>
    <x-page-shell
        :title="$clientAccount->name"
        :description="$clientAccount->industry ?: 'Client account'"
        :breadcrumbs="[
            ['label' => 'Clients', 'href' => route('clients.index')],
            ['label' => $clientAccount->name],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('clients.index')">Back to clients</x-button>
        </x-slot:actions>

        <x-flash-status type="success" />

        <x-profile-meta :items="array_filter([
            ['type' => 'badge', 'value' => $clientAccount->status],
            $clientAccount->portal_enabled ? ['type' => 'chip', 'value' => 'Portal enabled'] : null,
            $clientAccount->email ? ['type' => 'text', 'value' => $clientAccount->email] : null,
        ])" />

        <x-profile-layout :tabs="$profileTabs" :active="$activeTab">
        @if ($activeTab === 'overview')
            <div class="space-y-4">
                <x-section-card title="Last 7 days" class="!p-3">
                    <div class="overview-stat-grid">
                        <button type="button" wire:click="setTab('sites')" class="min-w-0">
                            <x-stat-card stacked label="Sites" :value="$stats['sites']" icon="sites" class="h-full w-full transition hover:border-zinc-300" />
                        </button>
                        <x-stat-card stacked label="Guards assigned" :value="$stats['guards_assigned']" icon="guards" hint="On upcoming shifts" />
                        <x-stat-card stacked label="Tours completed" :value="$stats['tours_completed']" icon="patrols" />
                        <x-stat-card stacked label="Incident reports" :value="$stats['incident_reports']" icon="incidents" tone="warning" />
                        <x-stat-card stacked label="Tasks completed" :value="$stats['tasks_completed']" icon="check" />
                        <x-stat-card stacked label="Hrs worked" :value="$stats['hours_worked']" icon="schedules" tone="info" />
                    </div>
                </x-section-card>

                <div class="overview-panel-grid">
                    <x-section-card title="Client locations" wire:key="client-map-{{ $clientAccount->id }}">
                        @if (count($mapMarkers) > 0)
                            <x-map
                                id="client-overview-map-{{ $clientAccount->id }}"
                                height="300px"
                                :lat="$mapCenter['lat']"
                                :lng="$mapCenter['lng']"
                                :zoom="$mapCenter['zoom']"
                                :markers="$mapMarkers"
                            />
                            <p class="mt-2 text-xs text-zinc-500">{{ count($mapMarkers) }} pin(s) — HQ and post sites with coordinates.</p>
                        @else
                            <x-empty-state compact title="No map pins yet" description="Set HQ coordinates on Profile or add site locations under Post Sites.">
                                <x-slot:actions>
                                    <x-button size="sm" variant="secondary" wire:click="setTab('sites')">Add post site</x-button>
                                </x-slot:actions>
                            </x-empty-state>
                        @endif
                    </x-section-card>

                    <x-section-card title="General information">
                        <dl class="space-y-3 text-sm">
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Company</dt>
                                <dd class="font-medium text-zinc-900 text-right">{{ $clientAccount->name }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Industry</dt>
                                <dd class="text-zinc-900 text-right">{{ $clientAccount->industry ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Phone</dt>
                                <dd class="text-zinc-900 text-right">{{ $clientAccount->phone ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Email</dt>
                                <dd class="text-zinc-900 text-right">{{ $clientAccount->email ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Address</dt>
                                <dd class="text-zinc-900 text-right">{{ $clientAccount->address ?: '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Default monthly rate</dt>
                                <dd class="text-zinc-900 text-right">{{ $clientAccount->default_monthly_rate ? number_format($clientAccount->default_monthly_rate, 2) : '—' }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Contacts</dt>
                                <dd class="text-zinc-900 text-right">{{ $clientAccount->contacts->count() }}</dd>
                            </div>
                            <div class="flex justify-between gap-4">
                                <dt class="text-zinc-500">Status</dt>
                                <dd class="text-right"><x-badge :status="$clientAccount->status" /></dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <x-button size="sm" variant="secondary" wire:click="setTab('profile')">Edit profile</x-button>
                            <x-button size="sm" variant="secondary" wire:click="setTab('contacts')">Manage contacts</x-button>
                        </div>
                    </x-section-card>
                </div>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <x-section-card title="Recent shifts">
                        @forelse ($recentActivity['shifts'] as $shift)
                            <div class="border-t border-zinc-100 py-3 first:border-t-0">
                                <div class="font-medium text-sm">{{ $shift->site?->name ?? 'Site' }}</div>
                                <div class="text-xs text-zinc-500">{{ $shift->starts_at?->format('M j, Y g:i A') }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No shifts scheduled yet.</p>
                        @endforelse
                    </x-section-card>

                    <x-section-card title="Recent patrols">
                        @forelse ($recentActivity['patrols'] as $patrol)
                            <div class="border-t border-zinc-100 py-3 first:border-t-0">
                                <div class="font-medium text-sm">{{ $patrol->route?->site?->name ?? 'Site' }}</div>
                                <div class="text-xs text-zinc-500">
                                    {{ $patrol->assignedGuard?->full_name ?? 'Guard' }} · {{ ucfirst($patrol->status) }}
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No patrol activity yet.</p>
                        @endforelse
                    </x-section-card>

                    <x-section-card title="Recent incidents">
                        @forelse ($recentActivity['incidents'] as $incident)
                            <div class="border-t border-zinc-100 py-3 first:border-t-0">
                                <div class="font-medium text-sm">{{ $incident->title }}</div>
                                <div class="text-xs text-zinc-500">{{ $incident->site?->name }} · {{ ucfirst($incident->status) }}</div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">No incidents reported.</p>
                        @endforelse
                    </x-section-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'profile')
            <x-form-card title="Client profile">
                <form wire:submit="saveProfile" class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="profileForm.name" label="Client name" class="sm:col-span-2" />
                    <x-input wire:model="profileForm.industry" label="Industry" />
                    <x-select wire:model="profileForm.status" label="Status">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </x-select>
                    <x-input wire:model="profileForm.email" label="Email" type="email" />
                    <x-input wire:model="profileForm.phone" label="Phone" />
                    <x-input wire:model="profileForm.address" label="Address" class="sm:col-span-2" />
                    <x-input wire:model="profileForm.latitude" label="Latitude" type="number" step="any" />
                    <x-input wire:model="profileForm.longitude" label="Longitude" type="number" step="any" />
                    <x-input wire:model="profileForm.default_monthly_rate" label="Default monthly rate" type="number" step="0.01" />
                    <div class="sm:col-span-2">
                        <x-button type="submit">Save profile</x-button>
                    </div>
                </form>
            </x-form-card>
        @endif

        @if ($activeTab === 'contacts')
            <div class="page-split">
                <x-section-card title="Contacts">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Name</x-table.th>
                                <x-table.th responsive="md">Role</x-table.th>
                                <x-table.th responsive="lg">Email</x-table.th>
                                <x-table.th responsive="lg">Phone</x-table.th>
                                <x-table.th align="right" class="w-12"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($clientAccount->contacts as $contact)
                                <tr wire:key="contact-{{ $contact->id }}">
                                    <x-table.td class="font-medium">{{ $contact->name }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $contact->role ?: '—' }}</x-table.td>
                                    <x-table.td responsive="lg" muted>{{ $contact->email ?: '—' }}</x-table.td>
                                    <x-table.td responsive="lg" muted>{{ $contact->phone ?: '—' }}</x-table.td>
                                    <x-table.td align="right" class="space-x-2">
                                        <button type="button" wire:click="editContact({{ $contact->id }})" class="text-xs font-medium text-accent-600 hover:underline">Edit</button>
                                        <button type="button" wire:click="deleteContact({{ $contact->id }})" wire:confirm="Remove this contact?" class="text-xs text-red-600 hover:underline">Remove</button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="5">
                                    <x-empty-state compact title="No contacts" description="Add key client contacts on the right." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card :title="$editingContactId ? 'Edit contact' : 'Add contact'">
                    <form wire:submit="saveContact" class="space-y-3">
                        <x-input wire:model="contactForm.name" label="Name" />
                        <x-input wire:model="contactForm.role" label="Role" />
                        <x-input wire:model="contactForm.email" label="Email" type="email" />
                        <x-input wire:model="contactForm.phone" label="Phone" />
                        <div class="flex gap-2">
                            <x-button type="submit" size="sm">{{ $editingContactId ? 'Update' : 'Add' }} contact</x-button>
                            @if ($editingContactId)
                                <x-button type="button" size="sm" variant="secondary" wire:click="cancelContactEdit">Cancel</x-button>
                            @endif
                        </div>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'notes')
            <div class="page-split">
                <x-section-card title="Notes">
                    <div class="space-y-3">
                        @forelse ($clientAccount->notes as $note)
                            <div wire:key="note-{{ $note->id }}" class="rounded-xl border border-zinc-200 bg-zinc-50 p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm text-zinc-800 whitespace-pre-wrap">{{ $note->body }}</p>
                                        <p class="mt-2 text-xs text-zinc-500">
                                            {{ $note->author?->name ?? 'System' }} · {{ $note->created_at->format('M j, Y g:i A') }}
                                            @if ($note->is_internal)
                                                · <span class="font-medium">Internal</span>
                                            @endif
                                        </p>
                                    </div>
                                    <button type="button" wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?" class="shrink-0 text-xs text-red-600 hover:underline">Delete</button>
                                </div>
                            </div>
                        @empty
                            <x-empty-state compact title="No notes" description="Add internal notes about this client." />
                        @endforelse
                    </div>
                </x-section-card>

                <x-form-card title="Add note">
                    <form wire:submit="addNote" class="space-y-3">
                        <div>
                            <label class="form-label">Note</label>
                            <textarea wire:model="noteForm.body" rows="5" class="form-input mt-1"></textarea>
                            @error('noteForm.body') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="noteForm.is_internal" class="rounded border-zinc-300">
                            Internal only (not visible in portal)
                        </label>
                        <x-button type="submit" size="sm">Add note</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'files')
            <div class="page-split">
                <x-section-card title="Files">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Title</x-table.th>
                                <x-table.th responsive="md">Type</x-table.th>
                                <x-table.th responsive="lg">Expires</x-table.th>
                                <x-table.th>Portal</x-table.th>
                                <x-table.th align="right" class="w-24"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($clientAccount->documents as $document)
                                <tr wire:key="doc-{{ $document->id }}">
                                    <x-table.td class="font-medium">{{ $document->title }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $document->document_type }}</x-table.td>
                                    <x-table.td responsive="lg" muted>{{ $document->expires_on?->format('M j, Y') ?? '—' }}</x-table.td>
                                    <x-table.td>
                                        @if ($document->client_visible)
                                            <span class="text-xs font-medium text-emerald-700">Visible</span>
                                        @else
                                            <span class="text-xs text-zinc-400">Internal</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <a href="{{ route('files.client-document', $document) }}" class="text-xs font-medium text-accent-600 hover:underline">Download</a>
                                        <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="Delete this file?" class="ml-2 text-xs text-red-600 hover:underline">Delete</button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="5">
                                    <x-empty-state compact title="No files" description="Upload contracts, SOWs, and other client documents." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card title="Upload file">
                    <form wire:submit="uploadDocument" class="space-y-3">
                        <x-input wire:model="documentForm.title" label="Title" />
                        <x-select wire:model="documentForm.document_type" label="Document type">
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="documentForm.expires_on" label="Expires on" type="date" />
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="documentForm.client_visible" class="rounded border-zinc-300">
                            Visible in client portal
                        </label>
                        <input wire:model="documentFile" type="file" class="form-input text-xs">
                        @error('documentFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <x-button type="submit" size="sm">Upload</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'sites')
            <div class="space-y-4">
                <div class="flex justify-end">
                    <x-button size="sm" wire:click="openSiteForm">Add post site</x-button>
                </div>

                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Site</x-table.th>
                            <x-table.th responsive="md">Address</x-table.th>
                            <x-table.th responsive="lg">Coordinates</x-table.th>
                            <x-table.th>Status</x-table.th>
                            <x-table.th align="right" class="w-24"></x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse ($clientAccount->sites as $site)
                            <tr wire:key="site-{{ $site->id }}">
                                <x-table.td class="font-medium">
                                    <a href="{{ route('sites.show', $site) }}" class="font-medium text-accent-700 hover:underline">{{ $site->name }}</a>
                                </x-table.td>
                                <x-table.td responsive="md" muted>{{ $site->address ?: '—' }}</x-table.td>
                                <x-table.td responsive="lg" muted>
                                    @if ($site->latitude && $site->longitude)
                                        {{ $site->latitude }}, {{ $site->longitude }}
                                    @else
                                        —
                                    @endif
                                </x-table.td>
                                <x-table.td><x-badge :status="$site->status" /></x-table.td>
                                <x-table.td align="right">
                                    <button type="button" wire:click="openSiteForm({{ $site->id }})" class="text-xs font-medium text-accent-600 hover:underline">Edit</button>
                                    <a href="{{ route('sites.show', $site) }}" class="ml-2 text-xs font-medium text-accent-600 hover:underline">Profile</a>
                                    <button type="button" wire:click="deleteSite({{ $site->id }})" wire:confirm="Delete this site?" class="ml-2 text-xs text-red-600 hover:underline">Delete</button>
                                </x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5">
                                <x-empty-state compact title="No post sites" description="Add sites where guards are deployed for this client." />
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>

                @if ($showSiteForm)
                <x-drawer :title="$editingSiteId ? 'Edit post site' : 'Add post site'" width="lg" close-method="closeSiteForm">
                    <x-drawer-form wire:submit="saveSite" submit-label="Save site">
                        <x-input wire:model="siteForm.name" label="Site name" class="sm:col-span-2" />
                        <x-input wire:model="siteForm.address" label="Address" class="sm:col-span-2" />
                        <x-input wire:model="siteForm.latitude" label="Latitude" type="number" step="any" />
                        <x-input wire:model="siteForm.longitude" label="Longitude" type="number" step="any" />
                        <x-input wire:model="siteForm.geofence_radius_meters" label="Geofence radius (m)" type="number" />
                        <x-select wire:model="siteForm.status" label="Status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </x-select>
                        <div class="sm:col-span-2">
                            <label class="form-label">Instructions</label>
                            <textarea wire:model="siteForm.instructions" rows="3" class="form-input mt-1"></textarea>
                        </div>
                    </x-drawer-form>
                </x-drawer>
                @endif
            </div>
        @endif

        @if ($activeTab === 'portal')
            <div class="page-split">
                <x-form-card title="Client portal settings">
                    <form wire:submit="savePortal" class="space-y-4">
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="portalForm.portal_enabled" class="rounded border-zinc-300">
                            Enable client portal for this account
                        </label>
                        <div>
                            <label class="form-label">Welcome message</label>
                            <textarea wire:model="portalForm.portal_welcome_message" rows="4" class="form-input mt-1" placeholder="Shown when portal users sign in…"></textarea>
                        </div>
                        <x-button type="submit">Save portal settings</x-button>
                    </form>
                </x-form-card>

                <x-section-card title="Portal status">
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500">Portal access</dt>
                            <dd><x-badge :status="$clientAccount->portal_enabled ? 'active' : 'inactive'" /></dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500">Portal users</dt>
                            <dd class="font-medium">{{ $clientAccount->portalUsers->where('status', 'active')->count() }} active</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-zinc-500">Visible files</dt>
                            <dd class="font-medium">{{ $clientAccount->documents->where('client_visible', true)->count() }}</dd>
                        </div>
                    </dl>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <x-button size="sm" variant="secondary" wire:click="setTab('users')">Manage users</x-button>
                        @if ($clientAccount->portal_enabled)
                            <x-button size="sm" variant="secondary" :href="route('client-portal.dashboard')" target="_blank">Open portal</x-button>
                        @endif
                    </div>
                </x-section-card>
            </div>
        @endif

        @if ($activeTab === 'users')
            <div class="page-split">
                <x-section-card title="Portal users">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Name</x-table.th>
                                <x-table.th responsive="md">Email</x-table.th>
                                <x-table.th>Status</x-table.th>
                                <x-table.th align="right" class="w-24"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($clientAccount->portalUsers as $user)
                                <tr wire:key="user-{{ $user->id }}">
                                    <x-table.td class="font-medium">{{ $user->name }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $user->email }}</x-table.td>
                                    <x-table.td><x-badge :status="$user->status" /></x-table.td>
                                    <x-table.td align="right">
                                        @if ($user->status === 'active')
                                            <button type="button" wire:click="deactivatePortalUser({{ $user->id }})" wire:confirm="Deactivate this user?" class="text-xs text-red-600 hover:underline">Deactivate</button>
                                        @else
                                            <button type="button" wire:click="reactivatePortalUser({{ $user->id }})" class="text-xs font-medium text-emerald-700 hover:underline">Reactivate</button>
                                        @endif
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="4">
                                    <x-empty-state compact title="No portal users" description="Invite a client contact to access the portal." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card title="Invite portal user">
                    @if (! $clientAccount->portal_enabled)
                        <p class="mb-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-900">
                            Enable the portal on the Client Portal tab before creating users.
                        </p>
                    @endif
                    <form wire:submit="invitePortalUser" class="space-y-3">
                        <x-input wire:model="userForm.name" label="Name" />
                        <x-input wire:model="userForm.email" label="Email" type="email" />
                        <x-input wire:model="userForm.password" label="Temporary password" type="password" />
                        @error('userForm.email') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        @error('userForm.password') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <x-button type="submit" size="sm">Create user</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'reports')
            <div class="page-split">
                <x-section-card title="Email report schedules">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Report</x-table.th>
                                <x-table.th responsive="md">Frequency</x-table.th>
                                <x-table.th responsive="lg">Recipients</x-table.th>
                                <x-table.th responsive="md">Last sent</x-table.th>
                                <x-table.th>Status</x-table.th>
                                <x-table.th align="right" class="w-28"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($clientAccount->reportSchedules as $schedule)
                                <tr wire:key="schedule-{{ $schedule->id }}">
                                    <x-table.td class="font-medium">{{ $reportTypes[$schedule->report_type] ?? $schedule->report_type }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ ucfirst($schedule->frequency) }}</x-table.td>
                                    <x-table.td responsive="lg" muted>{{ implode(', ', $schedule->recipients ?? []) }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $schedule->last_sent_at?->format('M j, Y g:i A') ?? 'Never' }}</x-table.td>
                                    <x-table.td>
                                        <x-badge :status="$schedule->is_active ? 'active' : 'inactive'" />
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <button type="button" wire:click="toggleReportSchedule({{ $schedule->id }})" class="text-xs font-medium text-accent-600 hover:underline">
                                            {{ $schedule->is_active ? 'Pause' : 'Enable' }}
                                        </button>
                                        <button type="button" wire:click="deleteReportSchedule({{ $schedule->id }})" wire:confirm="Delete this schedule?" class="ml-2 text-xs text-red-600 hover:underline">Delete</button>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="6">
                                    <x-empty-state compact title="No schedules" description="Set up automated email reports for this client." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card title="Add schedule">
                    <form wire:submit="addReportSchedule" class="space-y-3">
                        <x-select wire:model="reportForm.report_type" label="Report type">
                            @foreach ($reportTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-select wire:model="reportForm.frequency" label="Frequency">
                            @foreach ($frequencies as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <div>
                            <label class="form-label">Recipients</label>
                            <input wire:model="reportForm.recipients" type="text" class="form-input mt-1" placeholder="email@client.com, ops@client.com">
                            @error('reportForm.recipients') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <label class="flex items-center gap-2 text-sm">
                            <input type="checkbox" wire:model="reportForm.is_active" class="rounded border-zinc-300">
                            Active
                        </label>
                        <x-button type="submit" size="sm">Add schedule</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif
        </x-profile-layout>
    </x-page-shell>
</div>
