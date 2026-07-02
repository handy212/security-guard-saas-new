<div
    x-data="{
        open: false,
        menuTop: 0,
        menuLeft: 0,
        menuWidth: 160,
        updatePosition() {
            const rect = this.$refs.trigger.getBoundingClientRect();
            this.menuTop = rect.bottom + 4;
            this.menuLeft = Math.max(8, Math.min(rect.right - this.menuWidth, window.innerWidth - this.menuWidth - 8));
        },
        toggle() {
            this.open = !this.open;
            if (this.open) {
                this.$nextTick(() => this.updatePosition());
            }
        },
        close() {
            this.open = false;
        },
    }"
    @scroll.window="if (open) updatePosition()"
    @resize.window="if (open) updatePosition()"
    {{ $attributes->merge(['class' => 'relative inline-block text-left']) }}
>
    <button
        type="button"
        x-ref="trigger"
        @click="toggle()"
        class="table-row-menu-trigger"
        aria-label="Row actions"
        aria-haspopup="true"
        :aria-expanded="open"
    >
        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4zm0 4a2 2 0 110-4 2 2 0 010 4z"/>
        </svg>
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            x-transition
            @click.outside="close()"
            @keydown.escape.window="close()"
            :style="`position:fixed;top:${menuTop}px;left:${menuLeft}px;min-width:${menuWidth}px;z-index:9999`"
            class="table-row-menu-panel"
        >
            {{ $slot }}
        </div>
    </template>
</div>
