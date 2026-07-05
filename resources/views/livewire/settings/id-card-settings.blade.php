<div>
    <x-page-shell
        title="ID Card Settings"
        description="Customize how guard ID cards look. The preview matches what guards see on their profile."
        :breadcrumbs="[
            ['label' => 'Settings', 'href' => route('settings.index')],
            ['label' => 'ID Card'],
        ]"
    >
        <x-settings-nav />

        <div class="grid gap-8 xl:grid-cols-[minmax(0,400px)_minmax(300px,360px)] xl:items-start">
            <div class="space-y-4">
                <x-form-card title="Template & colors">
                    <form wire:submit="save" class="space-y-5">
                        <div>
                            <label class="form-label">Template style</label>
                            <div class="mt-2 grid grid-cols-3 gap-2">
                                @foreach (['modern' => 'Modern', 'minimal' => 'Minimal', 'creative' => 'Creative'] as $value => $label)
                                    <button
                                        type="button"
                                        wire:click="setTemplate('{{ $value }}')"
                                        @class([
                                            'rounded-xl border-2 px-2 py-3 text-center text-xs font-semibold transition',
                                            'border-accent-500 bg-accent-50 text-accent-800' => $template === $value,
                                            'border-zinc-200 text-zinc-600 hover:border-zinc-300' => $template !== $value,
                                        ])
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Card orientation</label>
                            <div class="mt-2 grid grid-cols-2 gap-2">
                                @foreach (['portrait' => 'Portrait', 'landscape' => 'Landscape'] as $value => $label)
                                    <button
                                        type="button"
                                        wire:click="setOrientation('{{ $value }}')"
                                        @class([
                                            'rounded-xl border-2 px-2 py-3 text-center text-xs font-semibold transition',
                                            'border-accent-500 bg-accent-50 text-accent-800' => $orientation === $value,
                                            'border-zinc-200 text-zinc-600 hover:border-zinc-300' => $orientation !== $value,
                                        ])
                                    >
                                        {{ $label }}
                                    </button>
                                @endforeach
                            </div>
                            <p class="mt-1.5 text-xs text-zinc-500">Landscape uses horizontal CR80 (85.6 × 54 mm). Applies to preview, print, and PDF export.</p>
                        </div>

                        <div>
                            <label class="form-label">Theme color</label>
                            <div class="mt-2 flex flex-wrap gap-2">
                                @foreach ($colorPresets as $preset)
                                    <button
                                        type="button"
                                        wire:click="setColor('{{ $preset['primary'] }}', '{{ $preset['dark'] }}')"
                                        @class([
                                            'h-9 w-9 rounded-full border-2 transition',
                                            'border-white ring-2 ring-accent-500 ring-offset-1' => $brandColor === $preset['primary'],
                                            'border-transparent hover:scale-105' => $brandColor !== $preset['primary'],
                                        ])
                                        style="background: {{ $preset['primary'] }}"
                                        title="{{ $preset['primary'] }}"
                                    ></button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <label class="form-label">Company logo</label>
                            <p class="mt-1 text-xs text-zinc-500">PNG or JPG, max 2 MB. Shown on the card header and back.</p>
                            <div class="mt-2 flex items-center gap-3">
                                @if ($logoUrl)
                                    <img src="{{ $logoUrl }}" alt="" class="h-12 max-w-[120px] rounded-lg border border-zinc-200 bg-white object-contain p-1">
                                @else
                                    <div class="flex h-12 w-12 items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-zinc-50 text-[10px] text-zinc-400">No logo</div>
                                @endif
                                <div class="flex flex-col gap-1">
                                    <input wire:model="logoFile" type="file" accept="image/*" class="form-input text-xs">
                                    @if ($logoUrl)
                                        <button type="button" wire:click="removeLogo" class="text-left text-xs text-red-600 hover:underline">Remove logo</button>
                                    @endif
                                </div>
                            </div>
                            @error('logoFile') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>

                        <x-input wire:model.live="tagline" label="Card tagline" placeholder="Employee Identification" />

                        <x-button type="submit" class="w-full">Save settings</x-button>
                    </form>
                </x-form-card>

                <x-form-card title="Card back details">
                    <form wire:submit="save" class="space-y-3">
                        <x-textarea wire:model.live="emergencyText" label="Emergency notice" rows="3" placeholder="This card is the property of..." />
                        <x-input wire:model="phone" label="Contact phone" />
                        <x-input wire:model="phoneSecondary" label="Secondary phone" />
                        <x-input wire:model="email" label="Contact email" type="email" />
                        <x-input wire:model="website" label="Website" placeholder="www.example.com" />
                        <x-textarea wire:model="address" label="Address" rows="2" />
                        <x-button type="submit" variant="secondary" class="w-full">Save contact details</x-button>
                    </form>
                </x-form-card>
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

                    <div class="flex justify-center" wire:key="settings-card-{{ $template }}-{{ $orientation }}-{{ $brandColor }}-{{ $previewSide }}-{{ $existingLogoPath }}-{{ $logoFile ? 'pending' : 'saved' }}">
                        <x-guard-id-card-preview
                            :brand="$previewBrand"
                            :card="$previewCard"
                            :side="$previewSide"
                            :logo-url="$logoUrl"
                            :qr-svg="$previewQrSvg"
                        />
                    </div>

                    <p class="mt-3 text-center text-xs text-zinc-500">
                        Same card design shown on each guard's verification tab.
                    </p>
                </x-section-card>
            </div>
        </div>
    </x-page-shell>
</div>
