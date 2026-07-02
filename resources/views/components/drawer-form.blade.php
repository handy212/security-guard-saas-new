@props(['submitLabel' => 'Save', 'cancelLabel' => 'Cancel', 'closeMethod' => 'closeDrawer', 'target' => 'save'])

<form {{ $attributes->merge(['class' => 'flex h-full min-h-0 flex-col']) }}>
    <div class="drawer-form-body">
        <div class="drawer-form-fields">
            {{ $slot }}
        </div>
    </div>

    <div class="drawer-form-footer">
        @if (isset($footer))
            {{ $footer }}
        @else
            <x-button type="submit" wire:loading.attr="disabled" :wire:target="$target">
                <span wire:loading.remove wire:target="{{ $target }}">{{ $submitLabel }}</span>
                <span wire:loading wire:target="{{ $target }}">Saving…</span>
            </x-button>
            <x-button type="button" variant="secondary" wire:click="{{ $closeMethod }}">{{ $cancelLabel }}</x-button>
        @endif
    </div>
</form>
