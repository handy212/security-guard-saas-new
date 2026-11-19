@php
    $formTitle = match ($operation) {
        'register' => 'Register reader',
        'settings' => 'Reader settings',
        'handover' => 'Record guard handover',
        'guard' => 'Map external guard ID',
        'checkpoint' => 'Map external checkpoint ID',
    };
@endphp
<x-drawer :title="$formTitle" :return-focus-selector="'[data-reader-action='.$operation.']'" description="Application configuration only. Nothing is written to the reader.">
    <x-drawer-form wire:submit="save" submit-label="Save configuration">
        @if ($errors->any())
            <div role="alert" class="rounded-md border border-red-200 bg-red-50 p-3 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                <p class="font-medium">Configuration was not saved.</p>
                <ul class="mt-1 list-inside list-disc">@foreach ($errors->all() as $message)<li>{{ $message }}</li>@endforeach</ul>
            </div>
        @endif
        @if ($operation === 'register')
            <x-input wire:model="form.name" label="Reader name" maxlength="255" required />
            <x-input wire:model="form.reader_number" label="Reader number" hint="Use the reader's verified device ID, not its model number." maxlength="100" required />
        @elseif ($operation === 'settings')
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="form.is_active" class="mt-1 rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20">
                <span><span class="block font-medium">Enable ingestion</span><span class="mt-1 block text-zinc-500 dark:text-zinc-400">Disabling revokes the existing bridge token. Re-enabling requires a new token through the management API.</span></span>
            </label>
            <label class="flex items-start gap-3 text-sm">
                <input type="checkbox" wire:model="form.require_device_identity" class="mt-1 rounded border-zinc-300 text-accent-600 focus:ring-accent-600/20">
                <span><span class="block font-medium">Require strict checkpoint identification</span><span class="mt-1 block text-zinc-500 dark:text-zinc-400">Require both mapped external IDs and agreement with the guard handover. This is not fingerprint enrollment or proof of a fingerprint match.</span></span>
            </label>
            <p class="text-sm text-amber-800 dark:text-amber-200">Changing this setting affects new uploads. Accepted event replays remain unchanged. No connection or enrollment is started.</p>
        @else
            @if ($operation !== 'handover')
                <x-input wire:model="form.external_id" label="External ID" hint="Exact device-record ID. Preserve case and leading zeroes; no spaces. This mapping cannot be rebound." maxlength="100" autocomplete="off" required />
            @endif
            <x-input wire:model.live.debounce.300ms="targetSearch" label="{{ $operation === 'checkpoint' ? 'Find a checkpoint' : 'Find a guard' }}" type="search" hint="Showing up to 50 matches. Refine the search if the target is missing." />
            @if ($operation === 'checkpoint')
                <x-select wire:model="form.patrol_checkpoint_id" label="Application checkpoint" required>
                    <option value="">Choose checkpoint</option>
                    @foreach ($checkpoints as $checkpoint)<option value="{{ $checkpoint->id }}">{{ $checkpoint->name }} / {{ $checkpoint->code }}</option>@endforeach
                </x-select>
            @else
                <x-select wire:model="form.guard_id" label="Application guard" required>
                    <option value="">Choose guard</option>
                    @foreach ($guards as $guard)<option value="{{ $guard->id }}">{{ $guard->first_name }} {{ $guard->last_name }} / {{ $guard->employee_number }}</option>@endforeach
                </x-select>
            @endif
            @if ($operation === 'handover')
                <x-input wire:model="form.starts_at" label="Starts at (with timezone)" placeholder="2026-11-16T08:00:00Z" hint="Whole seconds with Z or an explicit offset, for example +00:00." required />
                <x-input wire:model="form.ends_at" label="Ends at (with timezone)" placeholder="2026-11-16T16:00:00Z" hint="Must be after the start. Intervals cannot overlap and cannot be edited." required />
            @else
                <p class="text-sm text-amber-800 dark:text-amber-200">Confirm the exact ID and target before saving. Existing IDs cannot be corrected or reused from this screen.</p>
            @endif
        @endif
    </x-drawer-form>
</x-drawer>
