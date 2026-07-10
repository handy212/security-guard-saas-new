<div class="mx-auto max-w-lg px-4 py-8 sm:px-6">
    <header class="mb-6 rounded-2xl bg-zinc-950 px-5 py-6 text-white">
        <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ strtoupper($companyName) }}</p>
        <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Guard application</h1>
    </header>

    @if ($submitted)
        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">
            <h2 class="text-lg font-semibold text-emerald-900">Application received</h2>
            <p class="mt-2 text-sm text-zinc-600">Thank you. Our team will review your application and contact you if selected.</p>
        </div>
    @else
        <form wire:submit="submit" class="space-y-4 rounded-2xl border border-zinc-200 bg-white p-5 shadow-sm">
            <div class="grid gap-3 sm:grid-cols-2">
                <x-input wire:model="first_name" label="First name" />
                <x-input wire:model="last_name" label="Last name" />
            </div>
            <x-input wire:model="phone" label="Phone" />
            <x-input wire:model="email" label="Email" type="email" />
            <x-select wire:model="duty_type" label="Duty type">
                @foreach($dutyTypes as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </x-select>
            @if ($branches->isNotEmpty())
                <x-select wire:model="branch_id" label="Preferred branch">
                    <option value="">No preference</option>
                    @foreach($branches as $branch)
                        <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                    @endforeach
                </x-select>
            @endif
            <x-textarea wire:model="notes" label="Notes (optional)" rows="3" />
            <div>
                <label class="mb-1 block text-sm font-medium text-zinc-700">Photo (optional)</label>
                <input type="file" wire:model="photo" accept="image/*" class="form-input text-xs">
                @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
            </div>
            <x-button type="submit" class="w-full justify-center">Submit application</x-button>
        </form>
    @endif
</div>
