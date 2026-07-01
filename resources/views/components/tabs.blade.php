@props(['tabs' => [], 'active' => '', 'action' => 'setTab'])

<div class="flex gap-1 overflow-x-auto border-b border-zinc-200">
    @foreach ($tabs as $key => $label)
        <button
            type="button"
            wire:click="{{ $action }}('{{ $key }}')"
            class="shrink-0 border-b-2 px-3 py-2 text-sm font-medium transition {{ $active === $key ? 'border-zinc-900 text-zinc-900' : 'border-transparent text-zinc-500 hover:text-zinc-700' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
