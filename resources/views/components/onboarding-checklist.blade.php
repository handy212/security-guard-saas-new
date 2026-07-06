@props(['steps', 'progress'])

<div class="card-surface p-4">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Setup checklist</h2>
            <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $progress }}% complete</p>
        </div>
        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
            <div class="h-full rounded-full bg-zinc-900 transition-all dark:bg-zinc-100" style="width: {{ $progress }}%"></div>
        </div>
    </div>
    <ul class="mt-3 space-y-1.5">
        @foreach ($steps as $step)
            <li>
                <a
                    href="{{ $step['href'] }}"
                    @class([
                        'flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition',
                        'text-emerald-800 bg-emerald-50 dark:bg-emerald-900/30 dark:text-emerald-300' => $step['done'],
                        'text-zinc-700 hover:bg-zinc-50 dark:text-zinc-300 dark:hover:bg-zinc-800' => ! $step['done'],
                    ])
                >
                    <span @class([
                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold',
                        'bg-emerald-600 text-white' => $step['done'],
                        'border border-zinc-300 text-zinc-400 dark:border-zinc-600' => ! $step['done'],
                    ])>
                        {{ $step['done'] ? '✓' : '' }}
                    </span>
                    <span @class(['line-through opacity-60' => $step['done']])>{{ $step['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
