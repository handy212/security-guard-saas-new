<div>
    <x-page-shell title="Company Profile" :breadcrumbs="[['label' => 'Settings', 'href' => route('settings.index')], ['label' => 'Company Profile']]">
        <x-sub-sidebar-layout class="settings-workspace">
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <form wire:submit="save">
                <x-form-card title="Document issuer" description="These details appear on your invoices, estimates, and incident report PDFs. Issued invoices and estimates keep the details they were issued with.">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <x-input wire:model="profile.name" label="Legal company name" required maxlength="255" class="sm:col-span-2" />
                        <x-input wire:model="profile.tax_id" label="Tax ID" maxlength="80" />
                        <x-input wire:model="profile.registration_number" label="Registration number" maxlength="80" />
                        <x-input wire:model="profile.email" label="Company email" type="email" maxlength="255" />
                        <x-input wire:model="profile.phone" label="Company phone" type="tel" maxlength="40" />
                        <x-input wire:model="profile.website" label="Website" type="url" placeholder="https://example.com" maxlength="255" class="sm:col-span-2" />
                        <x-textarea wire:model="profile.address" label="Billing address" rows="3" maxlength="500" class="sm:col-span-2" />
                    </div>

                    <div class="mt-6 space-y-3 border-t border-zinc-200 pt-5 dark:border-zinc-800">
                        <x-input wire:model="logo" label="Document logo" type="file" accept="image/png,image/jpeg" hint="PNG or JPEG, up to 1 MB and 2000 × 2000 pixels." />
                        @if ($currentLogo)
                            <img src="{{ $currentLogo }}" alt="Current document logo" class="max-h-20 max-w-48 rounded-md border border-zinc-200 bg-white object-contain p-2">
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" wire:model="removeLogo" class="size-4 accent-accent-600">
                                Remove current logo
                            </label>
                        @endif
                    </div>

                    <x-slot:footer>
                        <x-button type="submit" wire:loading.attr="disabled" wire:target="logo,save">Save company profile</x-button>
                    </x-slot:footer>
                </x-form-card>
            </form>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
