@props(['field', 'active' => '', 'options' => []])

<div {{ $attributes->merge(['class' => 'inline-flex max-w-full shrink-0 overflow-x-auto rounded-lg bg-slate-100/90 p-1 dark:bg-zinc-800']) }}>
    @foreach ($options as $value => $label)
        <button
            type="button"
            wire:click="$set('{{ $field }}', '{{ $value }}')"
            class="rounded-md px-3 py-1.5 text-sm font-medium transition {{ (string) $active === (string) $value ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-900 dark:text-zinc-100' : 'text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
        >
            {{ $label }}
        </button>
    @endforeach
</div>
