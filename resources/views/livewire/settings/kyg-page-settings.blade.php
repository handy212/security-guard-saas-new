<div>
    <x-page-shell
        title="Know Your Guard Page"
        description="Customize the public verification page clients see when scanning a guard's ID card QR code."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'Know Your Guard'],
        ]"
    >
        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-form-card title="Public page content">
            <form wire:submit="save" class="space-y-5">
                <div>
                    <label class="form-label" for="subtitle">Page subtitle</label>
                    <input id="subtitle" type="text" wire:model="subtitle" class="form-input mt-1">
                    @error('subtitle') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="accessGuidance">Client access guidance</label>
                    <textarea id="accessGuidance" wire:model="accessGuidance" rows="3" class="form-input mt-1"></textarea>
                    @error('accessGuidance') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="securityNotice">Security notice (footer)</label>
                    <textarea id="securityNotice" wire:model="securityNotice" rows="3" class="form-input mt-1"></textarea>
                    @error('securityNotice') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="verifiedByLabel">Assignment verified-by label</label>
                    <input id="verifiedByLabel" type="text" wire:model="verifiedByLabel" class="form-input mt-1">
                    @error('verifiedByLabel') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="form-label" for="expectedAppearanceText">Expected appearance checklist</label>
                    <p class="mt-1 text-xs text-zinc-500">One item per line. Shown on the public verification page.</p>
                    <textarea id="expectedAppearanceText" wire:model="expectedAppearanceText" rows="5" class="form-input mt-1 font-mono text-sm"></textarea>
                    @error('expectedAppearanceText') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label class="form-label" for="reportConcernPhone">Report concern phone</label>
                        <input id="reportConcernPhone" type="text" wire:model="reportConcernPhone" class="form-input mt-1" placeholder="Optional — defaults to ID card secondary phone">
                        @error('reportConcernPhone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="form-label" for="reportConcernEmail">Report concern email</label>
                        <input id="reportConcernEmail" type="email" wire:model="reportConcernEmail" class="form-input mt-1" placeholder="Optional — defaults to ID card email">
                        @error('reportConcernEmail') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>

                <p class="text-xs text-zinc-500">
                    Control room phone numbers come from <a href="{{ route('settings.id-card') }}" class="font-medium text-accent-600 hover:underline">ID Card settings</a>.
                    Company logo on the verification page uses the ID card logo upload.
                </p>

                <div class="flex items-center gap-3">
                    <x-button type="submit">Save settings</x-button>
                    @if (session('status'))
                        <span class="text-sm text-emerald-700">{{ session('status') }}</span>
                    @endif
                </div>
            </form>
            </x-form-card>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
