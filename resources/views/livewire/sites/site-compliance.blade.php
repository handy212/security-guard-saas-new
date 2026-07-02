<div>
    <x-page-shell title="Site Compliance" description="Emergency contacts, site documents, and SLA requirements.">
        <div class="stat-grid">
            <x-stat-card compact label="Contacts" :value="$contacts->count()" icon="users" />
            <x-stat-card compact label="Documents" :value="$documents->count()" icon="billing" />
            <x-stat-card compact label="SLA requirements" :value="$sla->count()" icon="plan" tone="info" />
            <x-stat-card compact label="Sites" :value="$sites->count()" icon="sites" tone="success" />
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <x-form-card title="Add emergency contact" description="On-call contacts for each site.">
                <form wire:submit="saveContact" class="space-y-3">
                    <x-select wire:model="contactForm.site_id" label="Site" required>
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="contactForm.name" label="Name" placeholder="Site manager" required />
                    <x-input wire:model="contactForm.phone" label="Phone" placeholder="+234…" required />
                    <x-input wire:model="contactForm.role" label="Role" placeholder="Facilities manager" />
                    <x-button type="submit">Save contact</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Add site document" description="SOPs, contracts, and compliance files.">
                <form wire:submit="saveDocument" class="space-y-3">
                    <x-select wire:model="documentForm.site_id" label="Site" required>
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="documentForm.title" label="Title" placeholder="Site SOP" required />
                    <x-input wire:model="documentForm.file_path" label="File path / URL" placeholder="/storage/docs/sop.pdf" required />
                    <x-input wire:model="documentForm.document_type" label="Document type" placeholder="SOP, contract…" />
                    <x-button type="submit">Save document</x-button>
                </form>
            </x-form-card>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <x-data-table title="Emergency contacts">
                <x-table.head>
                    <tr>
                        <x-table.th>Name</x-table.th>
                        <x-table.th>Site</x-table.th>
                        <x-table.th>Phone</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr class="table-row-hover" wire:key="contact-{{ $contact->id }}">
                            <x-table.td>
                                <div class="font-medium text-zinc-900">{{ $contact->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $contact->role ?: '—' }}</div>
                            </x-table.td>
                            <x-table.td muted>{{ $contact->site?->name ?? '—' }}</x-table.td>
                            <x-table.td muted>{{ $contact->phone }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="3">
                            <x-empty-state compact title="No contacts" description="Add emergency contacts above." />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Documents">
                <x-table.head>
                    <tr>
                        <x-table.th>Title</x-table.th>
                        <x-table.th>Site</x-table.th>
                        <x-table.th>Type</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($documents as $document)
                        <tr class="table-row-hover" wire:key="document-{{ $document->id }}">
                            <x-table.td><span class="font-medium text-zinc-900">{{ $document->title }}</span></x-table.td>
                            <x-table.td muted>{{ $document->site?->name ?? '—' }}</x-table.td>
                            <x-table.td muted>{{ $document->document_type ?: '—' }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="3">
                            <x-empty-state compact title="No documents" description="Upload site documents above." />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="SLA requirements">
                <x-table.head>
                    <tr>
                        <x-table.th>Site</x-table.th>
                        <x-table.th>Metric</x-table.th>
                        <x-table.th>Target</x-table.th>
                    </tr>
                </x-table.head>
                <tbody>
                    @forelse($sla as $requirement)
                        <tr class="table-row-hover" wire:key="sla-{{ $requirement->id }}">
                            <x-table.td>{{ $requirement->site?->name ?? '—' }}</x-table.td>
                            <x-table.td muted>{{ $requirement->metric }}</x-table.td>
                            <x-table.td muted>{{ $requirement->target_value }}</x-table.td>
                        </tr>
                    @empty
                        <x-table.empty colspan="3">
                            <x-empty-state compact title="No SLAs" description="Configure SLAs in Compliance Policies." />
                        </x-table.empty>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </x-page-shell>
</div>
