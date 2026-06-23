@props(['steps', 'progress'])

<div class="rounded-xl border border-sky-200 bg-gradient-to-br from-sky-50 to-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-lg font-bold text-slate-900">Get your company set up</h2>
            <p class="text-sm text-slate-600">Complete these steps to start running operations.</p>
        </div>
        <div class="flex items-center gap-3">
            <div class="h-2 w-32 overflow-hidden rounded-full bg-slate-200">
                <div class="h-full rounded-full bg-sky-600 transition-all" style="width: {{ $progress }}%"></div>
            </div>
            <span class="text-sm font-semibold text-sky-800">{{ $progress }}%</span>
        </div>
    </div>
    <ul class="mt-4 grid gap-2 sm:grid-cols-2">
        @foreach($steps as $step)
            <li>
                <a href="{{ $step['href'] }}"
                   class="flex items-center gap-3 rounded-lg border px-3 py-2.5 text-sm transition {{ $step['done'] ? 'border-emerald-200 bg-emerald-50 text-emerald-900' : 'border-slate-200 bg-white hover:border-sky-300 hover:bg-sky-50' }}">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $step['done'] ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600' }}">
                        {{ $step['done'] ? '✓' : '○' }}
                    </span>
                    {{ $step['label'] }}
                </a>
            </li>
        @endforeach
    </ul>
</div>
