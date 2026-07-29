<div>
    <x-page-shell
        title="ID Card Settings"
        description="Customize how guard ID cards look. The preview matches what guards see on their profile."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'ID Card'],
        ]"
    >
        <x-slot:actions>
            <x-button variant="secondary" :href="route('settings.index')">Cancel</x-button>
            <x-button type="button" wire:click="save" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save">Save settings</span>
                <span wire:loading wire:target="save">Saving…</span>
            </x-button>
        </x-slot:actions>

        <x-sub-sidebar-layout>
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <div class="grid gap-8 xl:grid-cols-[minmax(0,400px)_minmax(300px,360px)] xl:items-start">
            <div class="space-y-4">
                <x-section-card title="Template & colors" description="Style, orientation, brand colors, and front logo.">
                    <form wire:submit="save" class="space-y-5">
                        <div>
                            <label class="form-label">Template style</label>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-4">
                                @foreach (['modern' => 'Modern', 'minimal' => 'Minimal', 'creative' => 'Creative', 'premium' => 'Premium'] as $value => $label)
                                    <button
                                        type="button"
                                        wire:click="setTemplate('{{ $value }}')"
                                        @class([
                                            'rounded-md border px-2 py-3 text-center text-xs font-semibold transition',
                                            'border-accent-500 bg-accent-50 text-accent-800 dark:border-accent-600 dark:bg-accent-950/40 dark:text-accent-200' => $template === $value,
                                            'border-zinc-200/90 text-zinc-600 hover:border-zinc-300 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600' => $template !== $value,
                                        ])
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            @if ($template === 'premium')
                                <p class="mt-1.5 text-xs text-amber-700 dark:text-amber-400">Premium is landscape only (horizontal CR80).</p>
                            @endif
                        </div>

                        @if ($template !== 'premium')
                            <div>
                                <label class="form-label">Card orientation</label>
                                <div class="mt-2 grid grid-cols-2 gap-2">
                                    @foreach (['portrait' => 'Portrait', 'landscape' => 'Landscape'] as $value => $label)
                                        <button
                                            type="button"
                                            wire:click="setOrientation('{{ $value }}')"
                                            @class([
                                                'rounded-md border px-2 py-3 text-center text-xs font-semibold transition',
                                                'border-accent-500 bg-accent-50 text-accent-800 dark:border-accent-600 dark:bg-accent-950/40 dark:text-accent-200' => $orientation === $value,
                                                'border-zinc-200/90 text-zinc-600 hover:border-zinc-300 dark:border-zinc-700 dark:text-zinc-300 dark:hover:border-zinc-600' => $orientation !== $value,
                                            ])
                                        >
                                            {{ $label }}
                                        </button>
                                    @endforeach
                                </div>
                                <p class="mt-1.5 text-xs text-zinc-500 dark:text-zinc-400">Landscape uses horizontal CR80 (85.6 × 54 mm). Applies to preview, print, and PDF export.</p>
                            </div>
                        @endif

                        <div>
                            <label class="form-label">Theme colors</label>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Pick a preset or set custom primary and dark shades.</p>
                            <div class="mt-2 grid grid-cols-8 gap-1.5">
                                @foreach ($colorPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="setColor('{{ $preset['primary'] }}', '{{ $preset['dark'] }}')"
                                        @class([
                                            'h-8 w-full rounded-lg border-2 transition',
                                            'border-white ring-2 ring-accent-500 ring-offset-1' => $brandColor === $preset['primary'],
                                            'border-transparent hover:scale-105' => $brandColor !== $preset['primary'],
                                        ])
                                        style="background: linear-gradient(135deg, {{ $preset['primary'] }} 50%, {{ $preset['dark'] }} 50%);"
                                        title="{{ $preset['label'] ?? $preset['primary'] }}"
                                    ></button>
                                @endforeach
                            </div>
                            <div class="mt-3 grid grid-cols-2 gap-3">
                                <div>
                                    <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Primary</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input wire:model.live="brandColor" type="color" class="h-9 w-10 cursor-pointer rounded border border-zinc-200 bg-white p-0.5 dark:border-zinc-700">
                                        <input wire:model.live="brandColor" type="text" class="form-input text-xs uppercase" maxlength="7">
                                    </div>
                                </div>
                                <div>
                                    <label class="text-xs font-medium text-zinc-600 dark:text-zinc-400">Dark shade</label>
                                    <div class="mt-1 flex items-center gap-2">
                                        <input wire:model.live="brandColorDark" type="color" class="h-9 w-10 cursor-pointer rounded border border-zinc-200 bg-white p-0.5 dark:border-zinc-700">
                                        <input wire:model.live="brandColorDark" type="text" class="form-input text-xs uppercase" maxlength="7">
                                    </div>
                                </div>
                            </div>
                            <button type="button" wire:click="syncDarkFromPrimary" class="page-link mt-2">
                                Auto-generate dark shade from primary
                            </button>
                        </div>

                        <div>
                            <label class="form-label">Front logo</label>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">PNG or JPG, max 2 MB. Shown on the card front.</p>
                            <div class="mt-2 flex items-center gap-3">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="" class="h-12 max-w-[120px] rounded-lg border border-zinc-200 bg-white object-contain p-1 dark:border-zinc-700">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-[10px] text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900">No logo</div>
                                @endif
                                <div class="flex flex-col gap-1">
                                    <input wire:model="logoFile" type="file" accept="image/*" class="form-input text-xs">
                                    @if ($logoUrl)
                                        <button type="button" wire:click="removeLogo" class="text-left text-xs font-semibold text-red-600 hover:underline dark:text-red-400">Remove front logo</button>
                                    @endif
                                </div>
                            </div>
                            @error('logoFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <x-input wire:model.live="tagline" label="Card tagline" placeholder="Employee Identification" />

                        <x-button type="submit" class="w-full" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Save settings</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </x-button>
                    </form>
                </x-section-card>

                <x-section-card title="Card back details" description="Contact block, logos, and authorized signature for the reverse side.">
                    <form wire:submit="save" class="space-y-3">
                        <div>
                            <label class="form-label">Back logo (optional)</label>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">Uses the front logo when empty. Upload a different logo for the card back if needed.</p>
                            <div class="mt-2 flex items-center gap-3">
                                @if ($backLogoUrl)
                                    <img src="{{ $backLogoUrl }}" alt="" class="h-12 max-w-[120px] rounded-lg border border-zinc-200 bg-white object-contain p-1 dark:border-zinc-700">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-[10px] text-zinc-400 dark:border-zinc-700 dark:bg-zinc-900">Default</div>
                                @endif
                                <div class="flex flex-col gap-1">
                                    <input wire:model="backLogoFile" type="file" accept="image/*" class="form-input text-xs">
                                    @if ($backLogoUrl)
                                        <button type="button" wire:click="removeBackLogo" class="text-left text-xs font-semibold text-red-600 hover:underline dark:text-red-400">Remove back logo</button>
                                    @endif
                                </div>
                            </div>
                            @error('backLogoFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label class="form-label">Authorized signature</label>
                            <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">PNG with a transparent background works best. Empty padding is trimmed automatically. Appears above the signature line on the Premium card back.</p>
                            <div class="mt-2 rounded-md border border-zinc-200/90 bg-zinc-50/80 p-3 dark:border-zinc-800 dark:bg-zinc-900/50">
                                <div class="flex h-24 items-end justify-center border-b border-slate-400 pb-1 dark:border-slate-500">
                                    @if ($signatureUrl)
                                        <img src="{{ $signatureUrl }}" alt="" class="max-h-[3.5rem] max-w-[70%] object-contain object-bottom">
                                    @else
                                        <span class="pb-1 text-[11px] text-zinc-400">No signature uploaded</span>
                                    @endif
                                </div>
                                <p class="mt-1.5 text-center text-[10px] font-bold uppercase tracking-wide text-zinc-500">Authorized signature</p>
                                <div class="mt-3 flex flex-col gap-1.5">
                                    <input wire:model="signatureFile" type="file" accept="image/*" class="form-input text-xs">
                                    @if ($signatureUrl)
                                        <button type="button" wire:click="removeSignature" class="text-left text-xs font-semibold text-red-600 hover:underline dark:text-red-400">Remove signature</button>
                                    @endif
                                </div>
                            </div>
                            @error('signatureFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <x-textarea wire:model.live="emergencyText" label="Emergency notice" rows="3" placeholder="This card is the property of..." />
                        <x-input wire:model="phone" label="Contact phone" />
                        <x-input wire:model="phoneSecondary" label="Secondary phone" />
                        <x-input wire:model="email" label="Contact email" type="email" />
                        <x-input wire:model="website" label="Website" placeholder="www.example.com" />
                        <x-textarea wire:model="address" label="Address" rows="2" />
                        <x-button type="submit" variant="secondary" class="w-full" wire:loading.attr="disabled" wire:target="save">
                            <span wire:loading.remove wire:target="save">Save contact details</span>
                            <span wire:loading wire:target="save">Saving…</span>
                        </x-button>
                    </form>
                </x-section-card>
            </div>

            <div class="xl:sticky xl:top-4">
                <x-section-card title="Live preview">
                    <div class="mb-4 flex justify-center">
                        <x-segment-control
                            field="previewSide"
                            :active="$previewSide"
                            :options="['front' => 'Front', 'back' => 'Back']"
                        />
                    </div>

                    <div class="flex justify-center" wire:key="settings-card-{{ $template }}-{{ $orientation }}-{{ $brandColor }}-{{ $previewSide }}-{{ $existingLogoPath }}-{{ $existingBackLogoPath }}-{{ $existingSignaturePath }}-{{ $logoFile ? 'pending' : 'saved' }}-{{ $backLogoFile ? 'back-pending' : 'back-saved' }}-{{ $signatureFile ? 'sig-pending' : 'sig-saved' }}">
                        <x-guard-id-card-preview
                            :brand="$previewBrand"
                            :card="$previewCard"
                            :side="$previewSide"
                            :logo-url="$logoUrl"
                            :back-logo-url="$backLogoUrl"
                            :signature-url="$signatureUrl"
                            :qr-svg="$previewQrSvg"
                        />
                    </div>

                    <p class="mt-3 text-center text-xs text-zinc-500 dark:text-zinc-400">
                        Same card design shown on each guard's verification tab.
                    </p>
                </x-section-card>
            </div>
            </div>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
