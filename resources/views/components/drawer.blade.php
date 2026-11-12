@props(['title', 'description' => null, 'width' => 'md', 'closeMethod' => 'closeDrawer'])

@php
    $widthClass = match($width) {
        'sm' => 'max-w-sm',
        'lg' => 'max-w-2xl',
        'xl' => 'max-w-3xl',
        default => 'max-w-md',
    };
@endphp

<div class="fixed inset-0 z-[60] flex justify-end" role="dialog" aria-modal="true" aria-label="{{ $title }}"
    x-data="{
        previousFocus: null,
        previousAction: null,
        previousScope: null,
        init() {
            const owner = this.$el.closest('[wire\\:id]');
            this.previousFocus = document.activeElement;
            if (!owner?.contains(this.previousFocus) || this.previousFocus === document.body) {
                this.previousFocus = [...(owner?.querySelectorAll('button') || [])]
                    .find(button => /^(open|edit)/.test(button.getAttribute('wire:click') || '')) || this.previousFocus;
            }
            this.previousAction = this.previousFocus?.getAttribute('wire:click');
            this.previousScope = this.previousFocus?.closest('[wire\\:id]')?.getAttribute('wire:id');
            this.$nextTick(() => this.$el.querySelector('input:not([disabled]), select:not([disabled]), textarea:not([disabled]), button')?.focus());
        },
        destroy() {
            const opener = this.previousFocus;
            const action = this.previousAction;
            const scope = this.previousScope;
            this.$nextTick(() => {
                if (opener?.isConnected) {
                    opener.focus();
                } else if (action) {
                    const owner = document.querySelector('[wire\\:id=' + CSS.escape(scope) + ']') || document;
                    owner.querySelector('[wire\\:click=' + CSS.escape(action) + ']')?.focus();
                }
            });
        },
        trapTab(event) {
            const controls = [...this.$el.querySelectorAll('a[href], button, input, select, textarea, [tabindex]')]
                .filter(el => !el.disabled && el.tabIndex >= 0 && el.getClientRects().length);
            const first = controls[0];
            const last = controls[controls.length - 1];
            if (event.shiftKey && (document.activeElement === first || !this.$el.contains(document.activeElement))) {
                event.preventDefault();
                last?.focus();
            } else if (!event.shiftKey && (document.activeElement === last || !this.$el.contains(document.activeElement))) {
                event.preventDefault();
                first?.focus();
            }
        }
    }"
    x-on:keydown.tab.window="trapTab($event)"
    x-on:keydown.escape.window.prevent.stop="$wire.{{ $closeMethod }}()"
>
    <div
        class="absolute inset-0 bg-zinc-950/40 backdrop-blur-[2px]"
        wire:click="{{ $closeMethod }}"
    ></div>

    <div class="relative flex h-full w-full {{ $widthClass }} flex-col border-l border-zinc-200 bg-white shadow-2xl dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex shrink-0 items-start justify-between gap-3 border-b border-zinc-100 px-5 py-5 sm:px-6 dark:border-zinc-800">
            <div class="min-w-0">
                <h2 class="text-lg font-semibold tracking-tight text-zinc-900 dark:text-zinc-50">{{ $title }}</h2>
                @if ($description)
                    <p class="mt-1.5 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $description }}</p>
                @endif
            </div>
            <button type="button" wire:click="{{ $closeMethod }}" class="rounded-lg p-2 text-zinc-400 transition hover:bg-zinc-100 hover:text-zinc-700 dark:hover:bg-zinc-800 dark:hover:text-zinc-200" aria-label="Close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="flex min-h-0 flex-1 flex-col overflow-hidden">
            {{ $slot }}
        </div>
    </div>
</div>
