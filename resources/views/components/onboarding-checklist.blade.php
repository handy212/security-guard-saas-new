@props(['steps', 'progress'])

<div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div>
            <h2 class="text-sm font-semibold text-zinc-900">Setup checklist</h2>
            <p class="text-xs text-zinc-500">{{ $progress }}% complete</p>
        </div>
        <div class="h-1.5 w-24 overflow-hidden rounded-full bg-zinc-100">
            <div class="h-full rounded-full bg-zinc-900 transition-all" style="width: {{ $progress }}%"></div>
        </div>
    </div>
    <ul class="mt-3 space-y-1.5">
        @foreach ($steps as $step)
            <li>
                <a
                    href="{{ $step['href'] }}"
                    @class([
                        'flex items-center gap-2.5 rounded-lg px-2.5 py-2 text-sm transition',
                        'text-emerald-800 bg-emerald-50' => $step['done'],
                        'text-zinc-700 hover:bg-zinc-50' => ! $step['done'],
                    ])
                >
                    <span @class([
                        'flex h-5 w-5 shrink-0 items-center justify-center rounded-full text-[10px] font-bold',
                        'bg-emerald-600 text-white' => $step['done'],
                        'border border-zinc-300 text-zinc-400' => ! $step['done'],
                    ])>
                        {{ $step['done'] ? '✓' : '' }}
                    </span>
                    <span @class(['line-through opacity-60' => $step['done']])>{{ $step['label'] }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</div>
