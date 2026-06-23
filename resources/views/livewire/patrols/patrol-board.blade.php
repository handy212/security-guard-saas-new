<div>
    <x-page-header title="Patrol Routes & Checkpoints" description="Define guard tour routes with sequenced QR/NFC checkpoints." />

    <div class="space-y-5 p-6">
        <div class="grid gap-5 lg:grid-cols-2">
            <x-form-card title="Create patrol route">
                <form wire:submit="saveRoute" class="space-y-4">
                    <x-select wire:model="routeForm.site_id" label="Site">
                        <option value="">Select site</option>
                        @foreach($sites as $site)
                            <option value="{{ $site->id }}">{{ $site->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="routeForm.name" label="Route name" />
                    <x-input wire:model="routeForm.expected_duration_minutes" label="Expected duration (min)" type="number" />
                    <x-button type="submit">Save route</x-button>
                </form>
            </x-form-card>

            <x-form-card title="Add checkpoint">
                <form wire:submit="saveCheckpoint" class="space-y-4">
                    <x-select wire:model="checkpointForm.patrol_route_id" label="Route">
                        <option value="">Select route</option>
                        @foreach($routes as $route)
                            <option value="{{ $route->id }}">{{ $route->name }}</option>
                        @endforeach
                    </x-select>
                    <x-input wire:model="checkpointForm.name" label="Checkpoint name" />
                    <x-input wire:model="checkpointForm.code" label="QR / NFC code" hint="Scanned by guards in the field app." />
                    <x-input wire:model="checkpointForm.sequence" label="Sequence" type="number" min="1" />
                    <x-button type="submit">Save checkpoint</x-button>
                </form>
            </x-form-card>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            @forelse($routes as $route)
                <x-section-card :title="$route->name" :description="$route->site?->name.' · '.$route->checkpoints->count().' checkpoints'">
                    <ol class="space-y-2">
                        @foreach($route->checkpoints->sortBy('sequence') as $cp)
                            <li class="flex items-center gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2 text-sm">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-white text-xs font-bold text-slate-600">{{ $cp->sequence }}</span>
                                <div class="flex-1">
                                    <div class="font-medium">{{ $cp->name }}</div>
                                    <div class="font-mono text-xs text-slate-500">{{ $cp->code }}</div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                </x-section-card>
            @empty
                <div class="md:col-span-2">
                    <x-empty-state title="No patrol routes" description="Create a route and add checkpoints for guards to scan." />
                </div>
            @endforelse
        </div>
    </div>
</div>
