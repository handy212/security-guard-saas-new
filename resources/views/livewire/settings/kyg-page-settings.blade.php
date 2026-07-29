<div>
    <x-page-shell
        title="Know Your Guard Page"
        description="Customize the public verification page clients see when scanning a guard's ID card QR code."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'Know Your Guard'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('settings.id-card')">ID card settings</x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <x-section-card title="Public page content" description="Copy shown when someone scans a verified guard QR code.">
                <form id="kyg-settings-form" wire:submit="save" class="space-y-5">
                    <x-form-section title="Page copy">
                        <x-input wire:model="subtitle" label="Page subtitle *" class="sm:col-span-2" />
                        <x-textarea wire:model="accessGuidance" label="Client access guidance *" rows="3" class="sm:col-span-2" />
                        <x-textarea wire:model="securityNotice" label="Security notice (footer) *" rows="3" class="sm:col-span-2" />
                        <x-input wire:model="verifiedByLabel" label="Assignment verified-by label *" class="sm:col-span-2" />
                    </x-form-section>

                    <x-form-section
                        title="Appearance checklist"
                        description="Uniform / ID standards only (one per line). Radios, bodycams, and other kit appear automatically from assets issued on the current shift."
                    >
                        <x-textarea wire:model="expectedAppearanceText" label="Expected appearance" rows="4" class="sm:col-span-2 font-mono text-sm" placeholder="Branded uniform&#10;Visible staff ID" />
                    </x-form-section>

                    <x-form-section title="Report concern contacts" description="Optional — falls back to ID card contact details.">
                        <x-input wire:model="reportConcernPhone" label="Phone" placeholder="Optional" />
                        <x-input wire:model="reportConcernEmail" type="email" label="Email" placeholder="Optional" />
                    </x-form-section>

                    <p class="text-xs text-zinc-500 dark:text-zinc-400">
                        Control room phone numbers come from <a href="{{ route('settings.id-card') }}" class="page-link">ID Card settings</a>.
                        Company logo on the verification page uses the ID card logo upload.
                    </p>
                </form>

                <x-slot:footer>
                    <x-button variant="secondary" :href="route('settings.index')">Cancel</x-button>
                    <x-button type="submit" form="kyg-settings-form" wire:loading.attr="disabled" wire:target="save">
                        <span wire:loading.remove wire:target="save">Save settings</span>
                        <span wire:loading wire:target="save">Saving…</span>
                    </x-button>
                </x-slot:footer>
            </x-section-card>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
