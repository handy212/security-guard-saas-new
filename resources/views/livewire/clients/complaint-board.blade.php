<div>
    <x-page-header title="Client Complaints" description="Log, track, and resolve client service issues." />

    <div class="space-y-5 p-6">
        <x-form-card title="Log complaint" description="Record a new client complaint or service issue." collapsible open>
            <form wire:submit="save" class="grid gap-4 md:grid-cols-2">
                <x-select wire:model="form.client_account_id" label="Client" required>
                    <option value="">Select client</option>
                    @foreach($clients as $client)
                        <option value="{{ $client->id }}">{{ $client->name }}</option>
                    @endforeach
                </x-select>
                <x-select wire:model="form.site_id" label="Site (optional)">
                    <option value="">Any site</option>
                    @foreach($sites as $site)
                        <option value="{{ $site->id }}">{{ $site->name }}</option>
                    @endforeach
                </x-select>
                <x-input wire:model="form.subject" label="Subject" placeholder="Late guard arrival" class="md:col-span-2" required />
                <x-textarea wire:model="form.description" label="Description" placeholder="Describe the issue…" class="md:col-span-2" rows="3" required />
                <x-select wire:model="form.priority" label="Priority">
                    <option value="low">Low</option>
                    <option value="normal">Normal</option>
                    <option value="high">High</option>
                </x-select>
                <div class="flex items-end">
                    <x-button type="submit">Log complaint</x-button>
                </div>
            </form>
        </x-form-card>

        <div class="space-y-3">
            @forelse($complaints as $complaint)
                <x-section-card>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-center gap-2">
                                <h3 class="text-base font-bold text-slate-900">{{ $complaint->subject }}</h3>
                                <x-badge :status="$complaint->priority" />
                                <x-badge :status="$complaint->status" />
                            </div>
                            <p class="mt-1 text-sm text-slate-500">
                                {{ $complaint->clientAccount?->name ?? 'Client' }}
                                · {{ $complaint->site?->name ?? 'All sites' }}
                            </p>
                            <p class="mt-2 text-sm text-slate-700">{{ $complaint->description }}</p>
                        </div>
                        @if($complaint->status !== 'resolved')
                            <x-button size="sm" variant="primary" wire:click="resolve({{ $complaint->id }})">Resolve</x-button>
                        @endif
                    </div>
                </x-section-card>
            @empty
                <x-empty-state title="No complaints" description="Client complaints will appear here." />
            @endforelse
        </div>
    </div>
</div>
