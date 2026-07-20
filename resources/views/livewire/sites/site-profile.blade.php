<div>
    <x-page-shell
        :title="$site->name"
        :description="$site->clientAccount?->name ?? 'Post site'"
        :breadcrumbs="[
            ['label' => 'Sites', 'href' => route('sites.index')],
            ['label' => $site->name],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('sites.index')">Back to sites</x-button>
            @if ($site->clientAccount)
                <x-button variant="secondary" :href="route('clients.show', $site->clientAccount)">Client profile</x-button>
            @endif
        </x-slot:actions>

        <x-flash-status type="success" />

        <x-profile-meta :items="array_filter([
            ['type' => 'badge', 'value' => $site->status],
            $site->clientAccount ? ['type' => 'text', 'value' => $site->clientAccount->name] : null,
            $site->address ? ['type' => 'text', 'value' => $site->address] : null,
        ])" />

        <x-profile-layout :tabs="$profileTabs" :active="$activeTab">
        @if ($activeTab === 'overview')
            <div class="space-y-3">
                <x-section-card title="Last 7 days" flush>
                    <div class="overview-stat-grid">
                        <x-stat-card compact label="Guards assigned" :value="$stats['guards_assigned']" icon="guards" hint="Upcoming shifts" />
                        <x-stat-card compact label="Tours completed" :value="$stats['tours_completed']" icon="patrols" />
                        <x-stat-card compact label="Incident reports" :value="$stats['incident_reports']" icon="incidents" tone="warning" />
                        <x-stat-card compact label="Tasks completed" :value="$stats['tasks_completed']" icon="check" />
                        <x-stat-card compact label="Hrs worked" :value="$stats['hours_worked']" icon="schedules" tone="info" />
                        <button type="button" wire:click="setTab('post_orders')" class="min-w-0 text-left">
                            <x-stat-card compact label="Posts" :value="$stats['posts']" icon="sites" class="h-full w-full" />
                        </button>
                    </div>
                </x-section-card>

                <div class="overview-panel-grid">
                    <x-section-card title="Site location" flush wire:key="site-map-{{ $site->id }}">
                        @if (count($mapMarkers) > 0)
                            <div class="p-0">
                                <x-map
                                    id="site-overview-map-{{ $site->id }}"
                                    height="280px"
                                    :lat="$mapCenter['lat']"
                                    :lng="$mapCenter['lng']"
                                    :zoom="$mapCenter['zoom']"
                                    :markers="$mapMarkers"
                                />
                            </div>
                            <p class="border-t border-zinc-100 px-4 py-2 text-xs tabular-nums text-zinc-500 dark:border-zinc-800">Geofence radius: {{ $site->geofence_radius_meters ?? 150 }}m</p>
                        @else
                            <div class="p-4">
                                <x-empty-state compact title="No coordinates set" description="Add latitude and longitude on the Patrol tab.">
                                    <x-slot:actions>
                                        <x-button size="sm" variant="secondary" wire:click="setTab('tours')">Set geofence</x-button>
                                    </x-slot:actions>
                                </x-empty-state>
                            </div>
                        @endif
                    </x-section-card>

                    <x-section-card title="General information">
                        <dl class="profile-dl">
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Site</dt>
                                <dd class="profile-dl-value">{{ $site->name }}</dd>
                            </div>
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Client</dt>
                                <dd class="profile-dl-value font-normal">{{ $site->clientAccount?->name ?? '—' }}</dd>
                            </div>
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Address</dt>
                                <dd class="profile-dl-value font-normal">{{ $site->address ?: '—' }}</dd>
                            </div>
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Posts</dt>
                                <dd class="profile-dl-value tabular-nums font-normal">{{ $stats['posts'] }}</dd>
                            </div>
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Patrol routes</dt>
                                <dd class="profile-dl-value tabular-nums font-normal">{{ $stats['patrol_routes'] }}</dd>
                            </div>
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Emergency contacts</dt>
                                <dd class="profile-dl-value tabular-nums font-normal">{{ $site->emergencyContacts->count() }}</dd>
                            </div>
                            <div class="profile-dl-row">
                                <dt class="profile-dl-label">Status</dt>
                                <dd class="text-right"><x-badge :status="$site->status" /></dd>
                            </div>
                        </dl>
                        <div class="mt-4 flex flex-wrap gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
                            <x-button size="sm" variant="secondary" wire:click="setTab('profile')">Edit profile</x-button>
                            <x-button size="sm" variant="secondary" wire:click="setTab('contacts')">Manage contacts</x-button>
                        </div>
                    </x-section-card>
                </div>

                <x-section-card title="Upcoming shifts">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>When</x-table.th>
                                <x-table.th responsive="md">Post</x-table.th>
                                <x-table.th responsive="lg">Guards</x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($upcomingShifts as $shift)
                                <tr class="table-row-hover" wire:key="shift-{{ $shift->id }}">
                                    <x-table.td class="font-medium tabular-nums">{{ $shift->starts_at?->format('M j, Y H:i') }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $shift->sitePost?->name ?? '—' }}</x-table.td>
                                    <x-table.td responsive="lg" muted>
                                        {{ $shift->assignments->map(fn ($a) => $a->assignedGuard?->full_name)->filter()->join(', ') ?: '—' }}
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="3">
                                    <x-empty-state compact title="No upcoming shifts" description="Schedule shifts from the Schedules module." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>
            </div>
        @endif

        @if ($activeTab === 'profile')
            <div class="space-y-4">
                <x-form-card title="Site profile" description="Name, client, address, and guard instructions">
                    <form wire:submit="saveProfile" class="grid gap-3 sm:grid-cols-2">
                        <x-input wire:model="profileForm.name" label="Site name" class="sm:col-span-2" />
                        <x-select wire:model="profileForm.client_account_id" label="Client" class="sm:col-span-2">
                            @foreach ($clients as $client)
                                <option value="{{ $client->id }}">{{ $client->name }}</option>
                            @endforeach
                        </x-select>
                        <x-select wire:model="profileForm.supervisor_user_id" label="Site supervisor" class="sm:col-span-2">
                            <option value="">No supervisor assigned</option>
                            @foreach ($supervisorCandidates as $candidate)
                                <option value="{{ $candidate->id }}">
                                    {{ $candidate->name }}{{ $candidate->phone ? ' · '.$candidate->phone : '' }}
                                </option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="profileForm.address" label="Address" class="sm:col-span-2" />
                        <x-select wire:model="profileForm.status" label="Status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </x-select>
                        <div class="sm:col-span-2">
                            <label class="form-label">Site instructions</label>
                            <textarea wire:model="profileForm.instructions" rows="4" class="form-input mt-1"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <x-button type="submit">Save profile</x-button>
                        </div>
                    </form>
                </x-form-card>

                <x-form-card title="Site preferences" description="Clock-in, notifications, and patrol defaults">
                    <form wire:submit="saveSettings" class="space-y-4 max-w-lg">
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="settingsForm.require_geofence_clock_in" class="rounded border-zinc-300">
                            Require geofence for clock-in
                        </label>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="settingsForm.notify_on_incident" class="rounded border-zinc-300">
                            Notify supervisors on new incidents
                        </label>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="settingsForm.show_in_client_portal" class="rounded border-zinc-300">
                            Show site in client portal
                        </label>
                        <x-input wire:model="settingsForm.patrol_reminder_minutes" label="Patrol reminder (minutes before due)" type="number" min="0" max="240" />
                        <x-button type="submit">Save settings</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'contacts')
            <div class="space-y-4">
                <div class="page-split">
                    <x-section-card title="Emergency contacts" description="People to call during incidents at this site">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Name</x-table.th>
                                    <x-table.th responsive="md">Role</x-table.th>
                                    <x-table.th responsive="lg">Phone</x-table.th>
                                    <x-table.th responsive="lg">Priority</x-table.th>
                                    <x-table.th align="right" class="w-24"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($site->emergencyContacts as $contact)
                                    <tr class="table-row-hover" wire:key="contact-{{ $contact->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $contact->name }}</x-table.td>
                                        <x-table.td responsive="md" muted>{{ $contact->role ?: '—' }}</x-table.td>
                                        <x-table.td responsive="lg" muted>{{ $contact->phone }}</x-table.td>
                                        <x-table.td responsive="lg" muted class="tabular-nums">{{ $contact->priority }}</x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="editContact({{ $contact->id }})" class="table-action">Edit</button>
                                                <button type="button" wire:click="deleteContact({{ $contact->id }})" wire:confirm="Remove this contact?" class="table-action text-red-600">Remove</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="5">
                                        <x-empty-state compact title="No contacts" description="Add emergency contacts for this site." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card :title="$editingContactId ? 'Edit contact' : 'Add contact'" description="On-site or client emergency contacts">
                        <form wire:submit="saveContact" class="space-y-3">
                            <x-input wire:model="contactForm.name" label="Name" />
                            <x-input wire:model="contactForm.role" label="Role" />
                            <x-input wire:model="contactForm.phone" label="Phone" />
                            <x-input wire:model="contactForm.email" label="Email" type="email" />
                            <x-input wire:model="contactForm.priority" label="Priority (1 = highest)" type="number" min="1" max="10" />
                            <x-button type="submit" size="sm">{{ $editingContactId ? 'Update' : 'Add' }} contact</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Notes" description="Internal history for this site" flush>
                        @forelse ($site->notes as $note)
                            <div class="list-row-start" wire:key="note-{{ $note->id }}">
                                <div class="min-w-0 flex-1">
                                    <p class="whitespace-pre-wrap text-sm text-zinc-800 dark:text-zinc-200">{{ $note->body }}</p>
                                    <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">
                                        {{ $note->author?->name ?? 'System' }} · <span class="tabular-nums">{{ $note->created_at->format('M j, Y g:i A') }}</span>
                                        @if ($note->is_internal)
                                            · <span class="font-medium text-zinc-600 dark:text-zinc-300">Internal</span>
                                        @endif
                                    </p>
                                </div>
                                <div class="table-inline-actions shrink-0">
                                    <button type="button" wire:click="editNote({{ $note->id }})" class="table-action">Edit</button>
                                    <button type="button" wire:click="deleteNote({{ $note->id }})" wire:confirm="Delete this note?" class="table-action text-red-600">Delete</button>
                                </div>
                            </div>
                        @empty
                            <div class="p-3">
                                <x-empty-state compact title="No notes" description="Add internal notes about this site." />
                            </div>
                        @endforelse
                    </x-section-card>

                    <x-form-card :title="$editingNoteId ? 'Edit note' : 'Add note'">
                        <form wire:submit="addNote" class="space-y-3">
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="noteForm.body" rows="5" class="form-input mt-1"></textarea>
                                @error('noteForm.body') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="noteForm.is_internal" class="rounded border-zinc-300">
                                Internal only
                            </label>
                            <x-button type="submit" size="sm">{{ $editingNoteId ? 'Update' : 'Add' }} note</x-button>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'kpis')
            <div class="page-split">
                <x-section-card title="SLA & checklist targets" description="Compliance metrics tracked for this site">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Metric</x-table.th>
                                <x-table.th responsive="md">Target</x-table.th>
                                <x-table.th responsive="md">Frequency</x-table.th>
                                <x-table.th responsive="lg">Grace (min)</x-table.th>
                                <x-table.th>Status</x-table.th>
                                <x-table.th align="right" class="w-24"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($site->slaRequirements as $sla)
                                <tr class="table-row-hover" wire:key="sla-{{ $sla->id }}">
                                    <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $sla->metric }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $sla->target_value }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ ucfirst($sla->frequency) }}</x-table.td>
                                    <x-table.td responsive="lg" muted class="tabular-nums">{{ $sla->grace_minutes }}</x-table.td>
                                    <x-table.td><x-badge :status="$sla->is_active ? 'active' : 'inactive'" /></x-table.td>
                                    <x-table.td align="right">
                                        <div class="table-inline-actions justify-end">
                                            <button type="button" wire:click="editChecklist({{ $sla->id }})" class="table-action">Edit</button>
                                            <button type="button" wire:click="deleteChecklist({{ $sla->id }})" wire:confirm="Delete this checklist item?" class="table-action text-red-600">Delete</button>
                                        </div>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="6">
                                    <x-empty-state compact title="No SLA targets" description="Add checklist items to define compliance targets for this site." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card :title="$editingChecklistId ? 'Edit checklist item' : 'Add checklist item'" description="Define metric, target, and grace period">
                    <form wire:submit="addChecklist" class="space-y-3">
                        <x-input wire:model="checklistForm.metric" label="Metric" placeholder="e.g. Patrol completion rate" />
                        <x-input wire:model="checklistForm.target_value" label="Target value" placeholder="e.g. 95%" />
                        <x-select wire:model="checklistForm.frequency" label="Frequency">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </x-select>
                        <x-input wire:model="checklistForm.grace_minutes" label="Grace period (minutes)" type="number" min="0" />
                        <x-button type="submit" size="sm">{{ $editingChecklistId ? 'Update' : 'Add' }} item</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'post_orders')
            <div class="space-y-4">
                <div class="profile-form-split">
                    <x-section-card title="Posts" description="Guard positions at this site">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Post</x-table.th>
                                    <x-table.th responsive="md">Guards</x-table.th>
                                    <x-table.th>Status</x-table.th>
                                    <x-table.th align="right" class="w-24"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($site->posts as $post)
                                    <tr class="table-row-hover" wire:key="post-{{ $post->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $post->name }}</x-table.td>
                                        <x-table.td responsive="md" muted class="tabular-nums">{{ $post->required_guards }}</x-table.td>
                                        <x-table.td><x-badge :status="$post->status" /></x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="editPost({{ $post->id }})" class="table-action">Edit</button>
                                                <button type="button" wire:click="deletePost({{ $post->id }})" wire:confirm="Delete this post?" class="table-action text-red-600">Delete</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="4">
                                        <x-empty-state compact title="No posts" description="Add guard posts for this site." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card :title="$editingPostId ? 'Edit post' : 'Add post'" description="Named positions and staffing levels">
                        <form wire:submit="addPost" class="space-y-3">
                            <x-input wire:model="postForm.name" label="Post name" />
                            <x-input wire:model="postForm.required_guards" label="Required guards" type="number" min="1" />
                            <x-select wire:model="postForm.status" label="Status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </x-select>
                            <div>
                                <label class="form-label">Description</label>
                                <textarea wire:model="postForm.description" rows="2" class="form-input mt-1"></textarea>
                            </div>
                            <x-button type="submit" size="sm">{{ $editingPostId ? 'Update' : 'Add' }} post</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Post orders" description="Standing instructions for guards">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Title</x-table.th>
                                    <x-table.th responsive="md">Post</x-table.th>
                                    <x-table.th>Active</x-table.th>
                                    <x-table.th align="right" class="w-24"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($site->postOrders as $order)
                                    <tr class="table-row-hover" wire:key="order-{{ $order->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $order->title }}</x-table.td>
                                        <x-table.td responsive="md" muted>{{ $order->sitePost?->name ?? 'Site-wide' }}</x-table.td>
                                        <x-table.td>
                                            <x-badge :status="$order->is_active ? 'active' : 'inactive'" />
                                        </x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="editPostOrder({{ $order->id }})" class="table-action">Edit</button>
                                                <button type="button" wire:click="deletePostOrder({{ $order->id }})" wire:confirm="Delete this post order?" class="table-action text-red-600">Delete</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="4">
                                        <x-empty-state compact title="No post orders" description="Add standing orders for guards at this site." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card :title="$editingPostOrderId ? 'Edit post order' : 'Add post order'" description="Site-wide or post-specific instructions">
                        <form wire:submit="addPostOrder" class="space-y-3">
                            <x-select wire:model="postOrderForm.site_post_id" label="Post (optional)">
                                <option value="">Site-wide</option>
                                @foreach ($site->posts as $post)
                                    <option value="{{ $post->id }}">{{ $post->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input wire:model="postOrderForm.title" label="Title" />
                            <div>
                                <label class="form-label">Instructions</label>
                                <textarea wire:model="postOrderForm.instructions" rows="4" class="form-input mt-1"></textarea>
                            </div>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="postOrderForm.is_active" class="rounded border-zinc-300">
                                Active
                            </label>
                            <x-button type="submit" size="sm">{{ $editingPostOrderId ? 'Update' : 'Add' }} post order</x-button>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'files')
            <div class="page-split">
                <x-section-card title="Files" description="SOPs, permits, and site documents">
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
                            @forelse ($site->documents as $document)
                                <tr class="table-row-hover" wire:key="doc-{{ $document->id }}">
                                    <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $document->title }}</x-table.td>
                                    <x-table.td responsive="md" muted>{{ $documentTypes[$document->document_type] ?? $document->document_type }}</x-table.td>
                                    <x-table.td responsive="lg" muted class="tabular-nums">{{ $document->expires_on?->format('M j, Y') ?? '—' }}</x-table.td>
                                    <x-table.td>
                                        @if ($document->client_visible)
                                            <span class="text-xs font-medium text-emerald-700 dark:text-emerald-400">Visible</span>
                                        @else
                                            <span class="text-xs text-zinc-400">Internal</span>
                                        @endif
                                    </x-table.td>
                                    <x-table.td align="right">
                                        <div class="table-inline-actions justify-end">
                                            <a href="{{ route('files.site-document', $document) }}" class="table-action">Download</a>
                                            <button type="button" wire:click="deleteDocument({{ $document->id }})" wire:confirm="Delete this file?" class="table-action text-red-600">Delete</button>
                                        </div>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="5">
                                    <x-empty-state compact title="No files" description="Upload SOPs, permits, and site documents." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card title="Upload file" description="Attach documents to this site">
                    <form wire:submit="uploadDocument" class="space-y-3">
                        <x-input wire:model="documentForm.title" label="Title" />
                        <x-select wire:model="documentForm.document_type" label="Document type">
                            @foreach ($documentTypes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="documentForm.expires_on" label="Expires on" type="date" />
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="documentForm.client_visible" class="rounded border-zinc-300">
                            Visible in client portal
                        </label>
                        <input wire:model="documentFile" type="file" class="form-input text-sm">
                        @error('documentFile') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                        <x-button type="submit" size="sm">Upload</x-button>
                    </form>
                </x-form-card>
            </div>
        @endif

        @if ($activeTab === 'tasks')
            <div class="page-split">
                <x-section-card title="Checkpoint tasks" description="Questions guards answer at tour tags">
                    <x-data-table>
                        <x-table.head>
                            <tr>
                                <x-table.th>Task</x-table.th>
                                <x-table.th responsive="md">Tour / tag</x-table.th>
                                <x-table.th responsive="lg">Response</x-table.th>
                                <x-table.th>Required</x-table.th>
                                <x-table.th align="right" class="w-24"></x-table.th>
                            </tr>
                        </x-table.head>
                        <tbody>
                            @forelse ($tasks as $task)
                                <tr class="table-row-hover" wire:key="task-{{ $task->id }}">
                                    <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $task->title }}</x-table.td>
                                    <x-table.td responsive="md" muted>
                                        {{ $task->checkpoint?->route?->name }} / {{ $task->checkpoint?->name }}
                                    </x-table.td>
                                    <x-table.td responsive="lg" muted>{{ str_replace('_', ' ', $task->response_type) }}</x-table.td>
                                    <x-table.td>{{ $task->is_required ? 'Yes' : 'No' }}</x-table.td>
                                    <x-table.td align="right">
                                        <div class="table-inline-actions justify-end">
                                            <button type="button" wire:click="editTask({{ $task->id }})" class="table-action">Edit</button>
                                            <button type="button" wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?" class="table-action text-red-600">Delete</button>
                                        </div>
                                    </x-table.td>
                                </tr>
                            @empty
                                <x-table.empty colspan="5">
                                    <x-empty-state compact title="No tasks" description="Add tasks linked to tour tags." />
                                </x-table.empty>
                            @endforelse
                        </tbody>
                    </x-data-table>
                </x-section-card>

                <x-form-card :title="$editingTaskId ? 'Edit task' : 'Add task'" description="Link a prompt to a tour checkpoint">
                    <form wire:submit="addTask" class="space-y-3">
                        <x-select wire:model="taskForm.patrol_checkpoint_id" label="Tour tag">
                            <option value="">Select tag…</option>
                            @foreach ($checkpoints as $checkpoint)
                                <option value="{{ $checkpoint->id }}">{{ $checkpoint->route?->name }} — {{ $checkpoint->name }}</option>
                            @endforeach
                        </x-select>
                        <x-input wire:model="taskForm.title" label="Task title" />
                        <x-select wire:model="taskForm.response_type" label="Response type">
                            <option value="yes_no">Yes / No</option>
                            <option value="text">Text</option>
                            <option value="number">Number</option>
                            <option value="photo">Photo</option>
                        </x-select>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                            <input type="checkbox" wire:model="taskForm.is_required" class="rounded border-zinc-300">
                            Required
                        </label>
                        <x-button type="submit" size="sm">{{ $editingTaskId ? 'Update' : 'Add' }} task</x-button>
                    </form>
                </x-form-card>
            </div>

            <x-section-card title="Recent submissions" description="Latest checkpoint task responses" class="mt-4">
                <x-data-table>
                    <x-table.head>
                        <tr>
                            <x-table.th>Task</x-table.th>
                            <x-table.th>Guard</x-table.th>
                            <x-table.th>Response</x-table.th>
                            <x-table.th responsive="md">Notes</x-table.th>
                            <x-table.th responsive="lg">When</x-table.th>
                        </tr>
                    </x-table.head>
                    <tbody>
                        @forelse ($taskSubmissions as $submission)
                            <tr class="table-row-hover" wire:key="site-sub-{{ $submission->id }}">
                                <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $submission->task?->title ?? '—' }}</x-table.td>
                                <x-table.td muted>{{ $submission->scan?->assignedGuard?->full_name ?? '—' }}</x-table.td>
                                <x-table.td>{{ is_array($submission->response) ? json_encode($submission->response) : ($submission->response ?: '—') }}</x-table.td>
                                <x-table.td responsive="md" muted>{{ $submission->notes ?: '—' }}</x-table.td>
                                <x-table.td responsive="lg" muted class="tabular-nums">{{ $submission->created_at?->format('M j, H:i') }}</x-table.td>
                            </tr>
                        @empty
                            <x-table.empty colspan="5">
                                <x-empty-state compact title="No submissions yet" description="Responses from checkpoint tasks will appear here." />
                            </x-table.empty>
                        @endforelse
                    </tbody>
                </x-data-table>
            </x-section-card>
        @endif

        @if ($activeTab === 'tours')
            <div class="space-y-4">
                <div class="page-split">
                    <x-section-card title="Site tours (patrol routes)" description="Named patrol paths for this site">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Name</x-table.th>
                                    <x-table.th responsive="md">Duration</x-table.th>
                                    <x-table.th responsive="lg">Tags</x-table.th>
                                    <x-table.th>Status</x-table.th>
                                    <x-table.th align="right" class="w-24"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($site->patrolRoutes as $route)
                                    <tr class="table-row-hover" wire:key="route-{{ $route->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $route->name }}</x-table.td>
                                        <x-table.td responsive="md" muted class="tabular-nums">{{ $route->expected_duration_minutes }} min</x-table.td>
                                        <x-table.td responsive="lg" muted class="tabular-nums">{{ $route->checkpoints->count() }}</x-table.td>
                                        <x-table.td><x-badge :status="$route->status" /></x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="editTour({{ $route->id }})" class="table-action">Edit</button>
                                                <button type="button" wire:click="deleteTour({{ $route->id }})" wire:confirm="Delete this tour and all its tags?" class="table-action text-red-600">Delete</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="5">
                                        <x-empty-state compact title="No tours" description="Create patrol routes for this site." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card :title="$editingTourId ? 'Edit site tour' : 'Add site tour'" description="Expected duration and route status">
                        <form wire:submit="addTour" class="space-y-3">
                            <x-input wire:model="tourForm.name" label="Tour name" />
                            <x-input wire:model="tourForm.expected_duration_minutes" label="Expected duration (min)" type="number" min="5" />
                            <x-select wire:model="tourForm.status" label="Status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </x-select>
                            <div>
                                <label class="form-label">Description</label>
                                <textarea wire:model="tourForm.description" rows="2" class="form-input mt-1"></textarea>
                            </div>
                            <x-button type="submit" size="sm">{{ $editingTourId ? 'Update' : 'Create' }} tour</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Tour tags (QR / NFC)" description="Checkpoints scanned during patrols">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Tag</x-table.th>
                                    <x-table.th responsive="md">Code</x-table.th>
                                    <x-table.th responsive="lg">Tour</x-table.th>
                                    <x-table.th>Seq</x-table.th>
                                    <x-table.th responsive="lg">GPS</x-table.th>
                                    <x-table.th align="right" class="w-24"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($checkpoints as $checkpoint)
                                    <tr class="table-row-hover" wire:key="tag-{{ $checkpoint->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $checkpoint->name }}</x-table.td>
                                        <x-table.td responsive="md" muted>{{ $checkpoint->code }}</x-table.td>
                                        <x-table.td responsive="lg" muted>{{ $checkpoint->route?->name }}</x-table.td>
                                        <x-table.td muted class="tabular-nums">{{ $checkpoint->sequence }}</x-table.td>
                                        <x-table.td responsive="lg" muted class="tabular-nums">
                                            @if ($checkpoint->latitude && $checkpoint->longitude)
                                                {{ number_format($checkpoint->latitude, 5) }}, {{ number_format($checkpoint->longitude, 5) }}
                                            @else
                                                —
                                            @endif
                                        </x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="editTourTag({{ $checkpoint->id }})" class="table-action">Edit</button>
                                                <button type="button" wire:click="deleteTourTag({{ $checkpoint->id }})" wire:confirm="Delete this tour tag?" class="table-action text-red-600">Delete</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="6">
                                        <x-empty-state compact title="No tour tags" description="Add QR/NFC checkpoint tags to patrol routes." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card :title="$editingTourTagId ? 'Edit tour tag' : 'Add tour tag'" description="QR/NFC code, sequence, and optional GPS">
                        <form wire:submit="addTourTag" class="space-y-3">
                            <x-select wire:model="tagForm.patrol_route_id" label="Site tour">
                                <option value="">Select tour…</option>
                                @foreach ($site->patrolRoutes as $route)
                                    <option value="{{ $route->id }}">{{ $route->name }}</option>
                                @endforeach
                            </x-select>
                            <x-input wire:model="tagForm.name" label="Tag name" />
                            <x-input wire:model="tagForm.code" label="Code (QR/NFC)" />
                            <x-input wire:model="tagForm.sequence" label="Sequence" type="number" min="1" />
                            <div class="grid grid-cols-2 gap-3">
                                <x-input wire:model="tagForm.latitude" label="Latitude" type="number" step="any" />
                                <x-input wire:model="tagForm.longitude" label="Longitude" type="number" step="any" />
                            </div>
                            <div>
                                <label class="form-label">Instructions</label>
                                <textarea wire:model="tagForm.instructions" rows="2" class="form-input mt-1"></textarea>
                            </div>
                            <x-button type="submit" size="sm">{{ $editingTourTagId ? 'Update' : 'Add' }} tag</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-grid-2">
                    <x-form-card title="Geo-fence settings" description="Site center and clock-in radius">
                        <form wire:submit="saveGeofence" class="grid gap-3 sm:grid-cols-2">
                            <x-input wire:model="geofenceForm.latitude" label="Latitude" type="number" step="any" />
                            <x-input wire:model="geofenceForm.longitude" label="Longitude" type="number" step="any" />
                            <x-input wire:model="geofenceForm.geofence_radius_meters" label="Radius (meters)" type="number" min="10" max="5000" class="sm:col-span-2" />
                            <div class="sm:col-span-2">
                                <x-button type="submit">Save geofence</x-button>
                            </div>
                        </form>
                    </x-form-card>

                    <x-section-card title="Map preview" description="Preview site location and geofence" wire:key="geofence-map-{{ $site->id }}">
                        @if (count($mapMarkers) > 0)
                            <x-map
                                id="site-geofence-map-{{ $site->id }}"
                                height="280px"
                                :lat="$mapCenter['lat']"
                                :lng="$mapCenter['lng']"
                                :zoom="$mapCenter['zoom']"
                                :markers="$mapMarkers"
                            />
                        @else
                            <x-empty-state compact title="Set coordinates" description="Enter latitude and longitude to preview the site on the map." />
                        @endif
                    </x-section-card>
                </div>
            </div>
        @endif

        @if ($activeTab === 'reports')
            <div class="space-y-4">
                <div class="page-split">
                    <x-section-card title="Assigned report templates" description="Templates guards can submit at this site">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Template</x-table.th>
                                    <x-table.th responsive="md">Post</x-table.th>
                                    <x-table.th align="right" class="w-20"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($site->reportAssignments as $assignment)
                                    <tr class="table-row-hover" wire:key="assign-report-{{ $assignment->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $assignment->template?->name ?? 'Template' }}</x-table.td>
                                        <x-table.td responsive="md" muted>{{ $assignment->sitePost?->name ?? 'Site-wide' }}</x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="removeReportAssignment({{ $assignment->id }})" wire:confirm="Remove this assignment?" class="table-action text-red-600">Remove</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="3">
                                        <x-empty-state compact title="No reports assigned" description="Assign report templates to this site." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card title="Assign report template" description="Make a template available site-wide or per post">
                        <form wire:submit="assignReport" class="space-y-3">
                            <x-select wire:model="reportAssignForm.report_template_id" label="Report template">
                                <option value="">Select template…</option>
                                @foreach ($reportTemplates as $template)
                                    <option value="{{ $template->id }}">{{ $template->name }}</option>
                                @endforeach
                            </x-select>
                            <x-select wire:model="reportAssignForm.site_post_id" label="Post (optional)">
                                <option value="">Site-wide</option>
                                @foreach ($site->posts as $post)
                                    <option value="{{ $post->id }}">{{ $post->name }}</option>
                                @endforeach
                            </x-select>
                            <x-button type="submit" size="sm">Assign template</x-button>
                        </form>
                    </x-form-card>
                </div>

                <div class="page-split">
                    <x-section-card title="Email report schedules" description="Automated reports sent for this site">
                        <x-data-table>
                            <x-table.head>
                                <tr>
                                    <x-table.th>Report</x-table.th>
                                    <x-table.th responsive="md">Frequency</x-table.th>
                                    <x-table.th responsive="lg">Recipients</x-table.th>
                                    <x-table.th>Status</x-table.th>
                                    <x-table.th align="right" class="w-20"></x-table.th>
                                </tr>
                            </x-table.head>
                            <tbody>
                                @forelse ($site->reportSchedules as $schedule)
                                    <tr class="table-row-hover" wire:key="schedule-{{ $schedule->id }}">
                                        <x-table.td class="font-medium text-zinc-900 dark:text-zinc-100">{{ $reportTypes[$schedule->report_type] ?? $schedule->report_type }}</x-table.td>
                                        <x-table.td responsive="md" muted>{{ ucfirst($schedule->frequency) }}</x-table.td>
                                        <x-table.td responsive="lg" muted>{{ implode(', ', $schedule->recipients ?? []) }}</x-table.td>
                                        <x-table.td><x-badge :status="$schedule->is_active ? 'active' : 'inactive'" /></x-table.td>
                                        <x-table.td align="right">
                                            <div class="table-inline-actions justify-end">
                                                <button type="button" wire:click="deleteReportSchedule({{ $schedule->id }})" wire:confirm="Delete this schedule?" class="table-action text-red-600">Delete</button>
                                            </div>
                                        </x-table.td>
                                    </tr>
                                @empty
                                    <x-table.empty colspan="5">
                                        <x-empty-state compact title="No schedules" description="Set up automated email reports for this site." />
                                    </x-table.empty>
                                @endforelse
                            </tbody>
                        </x-data-table>
                    </x-section-card>

                    <x-form-card title="Add schedule" description="Email recurring reports to recipients">
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
                                <input wire:model="reportForm.recipients" type="text" class="form-input mt-1" placeholder="ops@client.com, manager@client.com">
                                @error('reportForm.recipients') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                                <input type="checkbox" wire:model="reportForm.is_active" class="rounded border-zinc-300">
                                Active
                            </label>
                            <x-button type="submit" size="sm">Add schedule</x-button>
                        </form>
                    </x-form-card>
                </div>
            </div>
        @endif
        </x-profile-layout>
    </x-page-shell>
</div>
