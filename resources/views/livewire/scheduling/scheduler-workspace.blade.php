<div class="scheduler-workspace"
    x-data="{ paths: @js($paths) }"
    x-on:click.capture="
        const link = $event.target.closest('a[href]');
        if (link && !$event.ctrlKey && !$event.metaKey && !$event.shiftKey && !$event.altKey && $event.button === 0 && !link.target && !link.hasAttribute('download')) {
            const url = new URL(link.href, window.location.origin);
            const tab = paths[url.pathname];
            if (url.origin === window.location.origin && tab) {
                $event.preventDefault();
                $event.stopImmediatePropagation();
                $wire.setTab(tab, url.searchParams.get('date'));
            }
        }
    "
>
    <x-page-shell title="Scheduler" description="Plan shifts, staff your sites, and manage the field day.">
        <x-profile-layout :tabs="$tabs" :active="$activeTab">
            @livewire($panel, $panelParams, key('scheduler-'.$activeTab))
        </x-profile-layout>
    </x-page-shell>
</div>
