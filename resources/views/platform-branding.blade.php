@php
    $defaultName = config('app.name', 'GuardCore Pro');
    $defaultFavicon = asset('icons/icon-192.png');
    $name = old('name', $identity['name']);
    $message = old('sign_in_message', $identity['sign_in_message']);
    $email = old('support_email', $identity['support_email']);
    $name = is_string($name) ? $name : '';
    $message = is_string($message) ? $message : '';
    $email = is_string($email) ? $email : '';
@endphp

@component('layouts.app')
    <x-page-shell title="App Branding" :breadcrumbs="[['label' => 'SaaS', 'href' => route('saas')], ['label' => 'App Branding']]">
        <x-status-toast :message="session('branding_status')" />

        <div
            x-data="{
                name: @js($name),
                message: @js($message),
                email: @js($email),
                lightAccent: @js(old('light_accent', $identity['light_accent']) ?? ''),
                darkAccent: @js(old('dark_accent', $identity['dark_accent']) ?? ''),
                accent(value, fallback) { return /^#[0-9a-f]{6}$/i.test(value) ? value : fallback },
                removeLogo: @js((bool) old('remove_logo')),
                removeFavicon: @js((bool) old('remove_favicon')),
                removeBackground: @js((bool) old('remove_background')),
                draftLogo: null,
                draftFavicon: null,
                draftBackground: null,
                get displayName() { return this.name.trim() || @js($defaultName) },
                get logo() { return this.draftLogo || (this.removeLogo ? null : @js($logoUrl)) },
                get favicon() { return this.draftFavicon || (this.removeFavicon ? @js($defaultFavicon) : @js($faviconUrl)) },
                get background() { return this.draftBackground || (this.removeBackground ? @js(asset('icons/sign-in-background.webp')) : @js($backgroundUrl)) },
                preview(field, file) {
                    if (this[field]) URL.revokeObjectURL(this[field]);
                    this[field] = file ? URL.createObjectURL(file) : null;
                },
                destroy() {
                    this.preview('draftLogo');
                    this.preview('draftFavicon');
                    this.preview('draftBackground');
                }
            }"
            class="grid items-start gap-5 xl:grid-cols-[minmax(0,1.6fr)_minmax(0,1fr)]"
        >
            <form method="POST" action="{{ route('saas.branding.update') }}" enctype="multipart/form-data" class="min-w-0 space-y-4">
                @csrf
                <x-form-card title="App identity">
                    <x-input id="app-name" name="name" label="App name" :value="$name" :error="$errors->first('name')" x-model="name" :placeholder="$defaultName" maxlength="100" hint="Leave blank to use the built-in name." />

                    <div class="mt-5 grid gap-5 sm:grid-cols-2">
                        <div class="min-w-0 space-y-3">
                            <x-input id="app-logo" name="logo" label="App logo" type="file" accept="image/png,image/jpeg,image/webp" :error="$errors->first('logo')" @change="preview('draftLogo', $event.target.files[0])" hint="PNG, JPEG, or WebP, up to 2 MB and 3000 × 3000 pixels." />
                            <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <img src="{{ $logoUrl ?? $defaultFavicon }}" alt="Current app logo" class="size-8 rounded border border-zinc-200 bg-white object-contain">
                                <span>{{ $logoUrl ? 'Custom logo in use' : 'Default logo in use' }}</span>
                            </div>
                            @if ($logoUrl)
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="remove_logo" value="1" x-model="removeLogo" @checked(old('remove_logo')) class="size-4 accent-accent-600">
                                    Restore built-in logo
                                </label>
                            @endif
                        </div>
                        <div class="min-w-0 space-y-3">
                            <x-input id="app-favicon" name="favicon" label="Favicon" type="file" accept="image/png,image/x-icon,image/vnd.microsoft.icon,.ico" :error="$errors->first('favicon')" @change="preview('draftFavicon', $event.target.files[0])" hint="PNG or ICO, up to 2 MB. PNGs up to 3000 × 3000 are fitted to a square." />
                            <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                                <img src="{{ $faviconUrl }}" alt="Current favicon" class="size-8 rounded border border-zinc-200 bg-white object-contain">
                                <span>{{ $identity['favicon'] ? 'Custom favicon in use' : 'Default favicon in use' }}</span>
                            </div>
                            @if ($identity['favicon'])
                                <label class="flex items-center gap-2 text-sm">
                                    <input type="checkbox" name="remove_favicon" value="1" x-model="removeFavicon" @checked(old('remove_favicon')) class="size-4 accent-accent-600">
                                    Restore built-in favicon
                                </label>
                            @endif
                        </div>
                    </div>
                    <div class="mt-5 space-y-3">
                        <x-input id="sign-in-background" name="background" label="Sign-in background" type="file" accept="image/png,image/jpeg,image/webp" :error="$errors->first('background')" @change="preview('draftBackground', $event.target.files[0])" hint="PNG, JPEG, or WebP, up to 2 MB and 4000 × 4000 pixels." />
                        <div class="flex items-center gap-2 text-xs text-zinc-600 dark:text-zinc-400">
                            <img src="{{ $backgroundUrl }}" alt="Current sign-in background" class="h-10 w-16 rounded border border-zinc-200 bg-white object-cover">
                            <span>{{ $identity['background'] ? 'Custom background in use' : 'Default background in use' }}</span>
                        </div>
                        @if ($identity['background'])
                            <label class="flex items-center gap-2 text-sm">
                                <input type="checkbox" name="remove_background" value="1" x-model="removeBackground" @checked(old('remove_background')) class="size-4 accent-accent-600">
                                Restore default background
                            </label>
                        @endif
                    </div>
                    <p x-show="draftLogo || draftFavicon || draftBackground" x-cloak class="mt-4 text-sm text-zinc-600 dark:text-zinc-400" role="status">Save to publish the selected images.</p>
                </x-form-card>

                <x-form-card title="Color scheme" description="Set the app defaults. Companies can override these colors under Settings → Themes.">
                    <x-palette-presets light-model="lightAccent" dark-model="darkAccent" />

                    <div class="grid gap-4 sm:grid-cols-2">
                        @foreach (['light' => 'Light accent', 'dark' => 'Dark accent'] as $mode => $label)
                            <div class="flex items-start gap-2">
                                <input type="color" aria-label="Choose {{ strtolower($label) }}" :value="accent({{ $mode }}Accent, accent(lightAccent, '#0f766e'))" @input="{{ $mode }}Accent = $event.target.value" class="mt-6 h-10 w-11 shrink-0 rounded border border-zinc-300 p-1 dark:border-zinc-700">
                                <x-input :name="$mode.'_accent'" :label="$label" :x-model="$mode.'Accent'" :error="$errors->first($mode.'_accent')" maxlength="7" placeholder="{{ $mode === 'light' ? 'Use built-in color' : 'Use light accent' }}" />
                            </div>
                        @endforeach
                    </div>
                </x-form-card>

                <x-form-card title="Tenant dashboard">
                    <input type="hidden" name="command_dashboard" value="0">
                    <label class="flex items-start gap-3 text-sm">
                        <input type="checkbox" name="command_dashboard" value="1" @checked(old('command_dashboard', $identity['command_dashboard'])) class="mt-0.5 size-4 accent-accent-600">
                        <span>
                            <span class="block font-medium">Enable Command Center</span>
                            <span class="mt-1 block text-zinc-600 dark:text-zinc-400">Use the Command Center dashboard for all tenants.</span>
                        </span>
                    </label>
                </x-form-card>

                <x-form-card title="Sign-in page">
                    <div class="space-y-4">
                        <x-textarea id="sign-in-message" name="sign_in_message" label="Message" rows="2" maxlength="180" x-model="message" :error="$errors->first('sign_in_message')" hint="Optional text displayed above the sign-in form.">{{ $message }}</x-textarea>
                        <x-input id="support-email" name="support_email" label="Support email" type="email" maxlength="255" :value="$email" x-model="email" :error="$errors->first('support_email')" hint="Adds a Contact support link next to Forgot password." />
                    </div>

                    <x-slot:footer>
                        <button type="submit" class="btn-primary">Save app branding</button>
                    </x-slot:footer>
                </x-form-card>
            </form>

            <x-form-card title="Sign-in preview">
                <div class="overflow-hidden rounded-xl border border-zinc-200 dark:border-zinc-700">
                    <div class="flex items-center gap-2 border-b border-zinc-200 bg-zinc-50 px-4 py-3 text-sm font-medium dark:border-zinc-700 dark:bg-zinc-800">
                        <img src="{{ $faviconUrl }}" :src="favicon" alt="" width="16" height="16" class="size-4 shrink-0 rounded-xs">
                        <span class="truncate">Sign in — <span x-text="displayName">{{ $name ?: $defaultName }}</span></span>
                    </div>
                    <div class="relative isolate min-h-28 overflow-hidden bg-zinc-950 p-4 text-white">
                        <img src="{{ $backgroundUrl }}" :src="background" alt="" class="absolute inset-0 -z-20 size-full object-cover">
                        <div aria-hidden="true" class="absolute inset-0 -z-10 bg-gradient-to-b from-zinc-950/85 to-zinc-950/25"></div>
                        <div class="flex items-center gap-3">
                            <div x-show="!logo"><x-brand-mark :logo-override="null" /></div>
                            <img x-show="logo" x-cloak :src="logo" alt="Logo preview" class="size-8 shrink-0 rounded-md bg-white object-contain">
                            <div class="min-w-0 break-words text-sm font-semibold" x-text="displayName">{{ $name ?: $defaultName }}</div>
                        </div>
                    </div>
                    <div class="space-y-2 p-4">
                        <p class="font-semibold">Sign in</p>
                        <p x-show="message.trim()" x-text="message" x-cloak class="break-words text-sm text-zinc-600 dark:text-zinc-400"></p>
                        <p x-show="email.trim()" x-text="'Contact support · ' + email" x-cloak class="break-words text-sm text-accent-700 dark:text-accent-300"></p>
                        <div class="grid grid-cols-2 gap-2 pt-2">
                            <div class="rounded-md border border-zinc-200 bg-white p-2 text-xs text-zinc-900">
                                Light
                                <span class="mt-1 block h-3 rounded-sm" :style="{ backgroundColor: accent(lightAccent, '#0f766e') }"></span>
                            </div>
                            <div class="rounded-md border border-zinc-700 bg-zinc-950 p-2 text-xs text-white">
                                Dark
                                <span class="mt-1 block h-3 rounded-sm" :style="{ backgroundColor: accent(darkAccent, accent(lightAccent, '#0f766e')) }"></span>
                            </div>
                        </div>
                    </div>
                </div>
            </x-form-card>
        </div>
    </x-page-shell>
@endcomponent
