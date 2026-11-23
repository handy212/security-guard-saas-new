@props(['message'])

@if ($message)
    <div
        x-data="{
            visible: true,
            timer: null,
            start() { this.stop(); this.timer = setTimeout(() => this.visible = false, 5000) },
            stop() { clearTimeout(this.timer) },
            destroy() { this.stop() }
        }"
        x-init="start()"
        x-show="visible"
        x-transition.opacity
        @mouseenter="stop()"
        @mouseleave="if (!$el.contains(document.activeElement)) start()"
        @focusin="stop()"
        @focusout="if (!$el.contains($event.relatedTarget) && !$el.matches(':hover')) start()"
        class="fixed inset-x-4 bottom-4 z-50 ml-auto flex max-w-sm items-center gap-3 rounded-xl border border-emerald-200 bg-white px-4 py-2.5 text-sm text-zinc-900 shadow-lg dark:border-emerald-800 dark:bg-zinc-900 dark:text-zinc-100"
    >
        <span role="status" class="min-w-0 flex-1 font-medium">{{ $message }}</span>
        <button type="button" aria-label="Dismiss notification" @click="visible = false" class="flex size-8 shrink-0 items-center justify-center rounded-md text-lg text-zinc-600 hover:bg-zinc-100 dark:text-zinc-300 dark:hover:bg-zinc-800">
            <span aria-hidden="true">×</span>
        </button>
    </div>
@endif
