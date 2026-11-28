<nav aria-label="Assets sections" class="flex flex-wrap gap-1 border-b border-zinc-200 dark:border-zinc-800">
    @foreach (config('navigation.assets', []) as $link)
        @can($link['permission'])
            @php $active = request()->is(ltrim($link['href'], '/')); @endphp
            <a href="{{ $link['href'] }}" wire:navigate @if ($active) aria-current="page" @endif
                @class([
                    'border-b-2 px-3 py-2 text-sm font-medium',
                    'border-accent-600 text-accent-700 dark:text-accent-300' => $active,
                    'border-transparent text-zinc-600 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100' => ! $active,
                ])>
                {{ match ($link['href']) { '/assets/list' => 'Asset list', '/assets/inventory' => 'Inventory', default => $link['label'] } }}
            </a>
        @endcan
    @endforeach
</nav>
