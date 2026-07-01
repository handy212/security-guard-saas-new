@props(['model', 'active' => '', 'options' => []])

<div {{ $attributes->merge(['class' => 'inline-flex max-w-full shrink-0 overflow-x-auto rounded-lg border border-zinc-200 bg-zinc-50 p-1']) }}>
    @foreach ($options as $value => $label)
        <button
            type="button"
            wire:click="$set('{{ $model }}', '{{ $value }}')"
            class="rounded-md px-3 py-1.5 text-[11px] font-semibold uppercase tracking-wide transition {{ (string) $active === (string) $value ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-500 hover:text-zinc-700' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
