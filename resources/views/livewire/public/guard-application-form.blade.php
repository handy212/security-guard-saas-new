<div class="mx-auto max-w-lg px-4 py-8 sm:px-6 sm:py-10">
    <header class="relative mb-5 overflow-hidden rounded-[var(--radius-card)] bg-zinc-950 px-5 py-6 text-white">
        <div class="pointer-events-none absolute inset-0 opacity-50" style="background:
            radial-gradient(ellipse 80% 70% at 15% 20%, color-mix(in srgb, var(--tenant-brand, #0f766e) 50%, transparent), transparent 55%);"></div>
        <div class="relative">
            <x-brand-mark size="sm" />
            <p class="mt-4 text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ strtoupper($companyName) }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Guard application</h1>
            <p class="mt-1.5 text-sm text-zinc-400">Apply to join the field team. We'll review and contact you if selected.</p>
        </div>
    </header>

    @if ($submitted)
        <div class="card-surface p-6">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white" aria-hidden="true">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </span>
                <div>
                    <h2 class="text-base font-semibold text-zinc-900 dark:text-zinc-100">Application received</h2>
                    <p class="mt-1.5 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">Thank you. Our team will review your application and contact you if selected.</p>
                </div>
            </div>
        </div>
    @else
        <form wire:submit="submit" class="card-surface overflow-hidden">
            <div class="card-header">
                <div>
                    <h2 class="card-header-title">Your details</h2>
                    <p class="card-header-meta">Required fields help us place you correctly</p>
                </div>
            </div>
            <div class="space-y-4 p-4 sm:p-5">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-input wire:model="first_name" label="First name" required />
                    <x-input wire:model="last_name" label="Last name" required />
                </div>
                <x-input wire:model="phone" label="Phone" autocomplete="tel" />
                <x-input wire:model="email" label="Email" type="email" autocomplete="email" />
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
                    <label class="mb-1 block text-xs font-semibold text-zinc-600 dark:text-zinc-400">Photo (optional)</label>
                    <input type="file" wire:model="photo" accept="image/*" class="form-input w-full text-sm">
                    <p class="mt-1 text-[11px] text-zinc-500 dark:text-zinc-400">Clear headshot preferred. Max 5 MB.</p>
                    @error('photo') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <x-button type="submit" class="w-full justify-center" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="submit">Submit application</span>
                    <span wire:loading wire:target="submit">Submitting…</span>
                </x-button>
            </div>
        </form>
    @endif
</div>
