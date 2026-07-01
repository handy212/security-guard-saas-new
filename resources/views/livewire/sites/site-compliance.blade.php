<div>
    <x-page-shell title="Site Compliance" description="Emergency contacts, site documents, and SLA requirements." >
        <div class="grid gap-3 md:grid-cols-3">
        <x-stat-card label="Documents" :value="$documents->count()" />
        <x-stat-card label="SLA requirements" :value="$sla->count()" tone="info" />
        </div>

    <div class="space-y-4 page-content pt-0">
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
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Name</th>
                        <th class="px-3 py-2">Site</th>
                        <th class="px-3 py-2">Phone</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2">
                                <div class="font-medium text-zinc-900">{{ $contact->name }}</div>
                                <div class="text-xs text-zinc-500">{{ $contact->role ?: '—' }}</div>
                            </td>
                            <td class="px-3 py-2 text-zinc-600">{{ $contact->site?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $contact->phone }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-8"><x-empty-state title="No contacts" description="Add emergency contacts above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="Documents">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Title</th>
                        <th class="px-3 py-2">Site</th>
                        <th class="px-3 py-2">Type</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($documents as $document)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2 font-medium text-zinc-900">{{ $document->title }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $document->site?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $document->document_type ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-8"><x-empty-state title="No documents" description="Upload site documents above." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>

            <x-data-table title="SLA requirements">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500">
                    <tr>
                        <th class="px-3 py-2">Site</th>
                        <th class="px-3 py-2">Metric</th>
                        <th class="px-3 py-2">Target</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sla as $requirement)
                        <tr class="table-row-hover">
                            <td class="px-3 py-2 text-zinc-900">{{ $requirement->site?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $requirement->metric }}</td>
                            <td class="px-3 py-2 text-zinc-600">{{ $requirement->target_value }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="px-3 py-8"><x-empty-state title="No SLAs" description="Configure SLAs in Compliance Policies." /></td></tr>
                    @endforelse
                </tbody>
            </x-data-table>
        </div>
    </x-page-shell>
</div>
