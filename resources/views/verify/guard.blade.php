<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Know Your Guard — {{ $guard->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-gradient-to-b from-zinc-100 to-zinc-50 font-sans antialiased">
<div class="mx-auto max-w-md px-4 py-8 sm:py-10">
    <div class="overflow-hidden rounded-2xl border border-zinc-200/80 bg-white shadow-lg shadow-zinc-200/60">
        <div class="bg-zinc-900 px-5 py-4 text-center">
            <p class="text-[11px] font-semibold uppercase tracking-[0.2em] text-zinc-400">Know Your Guard</p>
            <p class="mt-1 text-sm font-medium text-white">{{ $companyName }}</p>
        </div>

        <div class="px-5 pb-5 pt-6">
            <div class="flex flex-col items-center text-center">
                @if ($photoUrl)
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $guard->full_name }}"
                        class="h-40 w-32 rounded-xl border-2 border-zinc-200 object-cover shadow-md"
                    >
                @else
                    <div class="flex h-40 w-32 items-center justify-center rounded-xl border-2 border-zinc-200 bg-zinc-100 text-3xl font-bold text-zinc-500 shadow-sm">
                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                    </div>
                @endif

                <h1 class="mt-4 text-xl font-bold tracking-tight text-zinc-900">{{ $guard->full_name }}</h1>

                @if ($guard->employee_number)
                    <p class="mt-1 text-sm font-medium text-zinc-500">Guard ID {{ $guard->employee_number }}</p>
                @endif

                @if ($guard->rank)
                    <p class="mt-1 text-sm text-zinc-600">{{ $guard->rank }}</p>
                @endif

                @if ($isVerified)
                    <span class="mt-3 inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 ring-1 ring-emerald-600/20">
                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified officer
                    </span>
                @else
                    <span class="mt-3 inline-flex rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-800 ring-1 ring-amber-600/20">
                        Verification pending
                    </span>
                @endif
            </div>

            @if ($currentAssignment)
                <div class="mt-6 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-800">On assignment</p>
                    <p class="mt-1 text-base font-semibold text-emerald-950">{{ $currentAssignment['site_name'] }}</p>
                    <p class="mt-2 flex items-start gap-2 text-sm text-emerald-900/90">
                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <span>{{ $currentAssignment['date_range'] }}</span>
                    </p>
                </div>
            @endif

            <div class="mt-5 space-y-2.5">
                <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3.5 py-3">
                    <span class="text-sm text-zinc-500">Security company</span>
                    <span class="text-sm font-semibold text-zinc-900">{{ $companyName }}</span>
                </div>
                @if ($branchName)
                    <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3.5 py-3">
                        <span class="text-sm text-zinc-500">Branch</span>
                        <span class="text-sm font-semibold text-zinc-900">{{ $branchName }}</span>
                    </div>
                @endif
                <div class="flex items-center justify-between rounded-lg bg-zinc-50 px-3.5 py-3">
                    <span class="text-sm text-zinc-500">Employment status</span>
                    <span class="text-sm font-semibold capitalize text-zinc-900">{{ $guard->status }}</span>
                </div>
            </div>

            @if ($skills->isNotEmpty())
                <div class="mt-5">
                    <h2 class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">Skills</h2>
                    <div class="mt-2.5 flex flex-wrap gap-2">
                        @foreach ($skills as $skill)
                            <span class="rounded-lg bg-zinc-100 px-2.5 py-1 text-xs font-medium text-zinc-700">
                                {{ $skill->skill }}{{ $skill->level ? ' · '.$skill->level : '' }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-6 rounded-xl border border-zinc-100 bg-zinc-50 px-4 py-3 text-center text-xs leading-relaxed text-zinc-500">
                @if ($verifiedAt)
                    <p>Identity last verified {{ $verifiedAt->format('M j, Y g:i A') }}</p>
                @endif
                <p @class(['mt-0.5' => $verifiedAt])>Page scanned {{ $scannedAt->format('M j, Y g:i A') }}</p>
            </div>
        </div>
    </div>

    <p class="mt-5 text-center text-xs leading-relaxed text-zinc-500">
        This page confirms the guard is registered with {{ $companyName }}.
        Contact the company immediately if anything looks wrong.
    </p>
</div>
</body>
</html>
