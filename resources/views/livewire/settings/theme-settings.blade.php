<div>
    <x-page-shell title="Themes" :breadcrumbs="[['label' => 'Settings', 'href' => route('settings.index')], ['label' => 'Themes']]">
        <x-sub-sidebar-layout class="settings-workspace">
            <x-slot:sidebar><x-settings-nav /></x-slot:sidebar>

            <x-flash-status type="success" />

            <form wire:submit="save">
                <x-form-card title="Organization color scheme" description="Set the accent colors everyone in your organization sees in Light and Dark mode.">
                    <x-palette-presets x-data light-model="$wire.lightColor" dark-model="$wire.darkColor" />

                    <div class="grid gap-5 lg:grid-cols-2">
                        @foreach (['lightColor' => 'Light mode', 'darkColor' => 'Dark mode'] as $field => $label)
                            <section
                                x-data="{
                                    color: $wire.entangle(@js($field)),
                                    get previewColor() {
                                        if (/^#[0-9a-f]{6}$/i.test(this.color)) return this.color;
                                        if (@js($field === 'darkColor') && /^#[0-9a-f]{6}$/i.test($wire.lightColor)) return $wire.lightColor;
                                        return getComputedStyle(document.documentElement).getPropertyValue('--tenant-brand').trim();
                                    }
                                }"
                                class="min-w-0 space-y-3"
                            >
                                <label for="{{ $field }}" class="form-label">{{ $label }} accent</label>
                                <div class="flex items-center gap-2">
                                    <input
                                        type="color"
                                        :value="previewColor"
                                        @input="color = $el.value"
                                        aria-label="Choose {{ strtolower($label) }} accent"
                                        class="h-10 w-12 shrink-0 cursor-pointer rounded-lg border border-zinc-200 bg-white p-1 dark:border-zinc-700 dark:bg-zinc-900"
                                    />
                                    <input
                                        id="{{ $field }}"
                                        type="text"
                                        x-model="color"
                                        placeholder="{{ $field === 'lightColor' ? 'Use brand default' : 'Use light accent' }}"
                                        maxlength="7"
                                        spellcheck="false"
                                        autocomplete="off"
                                        @error($field) aria-invalid="true" aria-describedby="{{ $field }}-error" @enderror
                                        class="form-input min-w-0 font-mono"
                                    />
                                    <button type="button" x-show="color" x-cloak @click="color = ''" class="page-link">Clear<span class="sr-only"> {{ strtolower($label) }} accent</span></button>
                                </div>
                                @error($field)
                                    <p id="{{ $field }}-error" class="form-error">{{ $message }}</p>
                                @enderror

                                <div
                                    :style="{ '--preview-color': previewColor }"
                                    @class([
                                        'space-y-3 rounded-lg border p-4',
                                        'border-zinc-200 bg-zinc-50 text-zinc-900' => $field === 'lightColor',
                                        'border-zinc-700 bg-zinc-900 text-white' => $field === 'darkColor',
                                    ])
                                >
                                    <p class="text-sm font-semibold">{{ $label }} preview</p>
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="rounded-md px-3 py-1.5 text-sm font-semibold text-white" style="background-color: oklch(from var(--preview-color) min(l, 0.52) c h)">Primary button</span>
                                        <span class="text-sm font-medium underline" style="color: color-mix(in srgb, var(--preview-color) {{ $field === 'lightColor' ? '72%, black' : '65%, white' }})">Text link</span>
                                    </div>
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <x-slot:footer>
                        <x-button type="submit" wire:loading.attr="disabled" wire:target="save">Save color scheme</x-button>
                    </x-slot:footer>
                </x-form-card>
            </form>
        </x-sub-sidebar-layout>
    </x-page-shell>
</div>
