<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Guard Verification — {{ $guard->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-zinc-50">
<div class="mx-auto max-w-md px-4 py-8">
    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white shadow-sm">
        <div class="border-b border-zinc-100 bg-zinc-900 px-4 py-3 text-center text-sm font-medium text-white">
            Know Your Guard
        </div>

        <div class="p-4">
            <div class="flex flex-col items-center text-center">
                @if ($guard->photo_path)
                    <img src="{{ route('guard.verify.photo', $token) }}" alt="{{ $guard->full_name }}" class="h-24 w-24 rounded-full border-2 border-zinc-200 object-cover">
                @else
                    <div class="flex h-24 w-24 items-center justify-center rounded-full bg-zinc-100 text-2xl font-semibold text-zinc-600">
                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                    </div>
                @endif

                <h1 class="mt-3 text-lg font-semibold text-zinc-900">{{ $guard->full_name }}</h1>
                @if ($guard->employee_number)
                    <p class="text-sm text-zinc-500">ID {{ $guard->employee_number }}</p>
                @endif

                @if ($isVerified)
                    <span class="mt-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 ring-1 ring-emerald-600/20">
                        <svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Verified by {{ $companyName }}
                    </span>
                @else
                    <span class="mt-2 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 ring-1 ring-amber-600/20">
                        Verification pending
                    </span>
                @endif
            </div>

            <dl class="mt-5 space-y-3 text-sm">
                <div class="flex justify-between border-b border-zinc-100 pb-2">
                    <dt class="text-zinc-500">Security company</dt>
                    <dd class="font-medium text-zinc-900">{{ $companyName }}</dd>
                </div>
                @if ($branchName)
                    <div class="flex justify-between border-b border-zinc-100 pb-2">
                        <dt class="text-zinc-500">Branch</dt>
                        <dd class="font-medium text-zinc-900">{{ $branchName }}</dd>
                    </div>
                @endif
                <div class="flex justify-between border-b border-zinc-100 pb-2">
                    <dt class="text-zinc-500">Employment status</dt>
                    <dd class="font-medium text-zinc-900">{{ ucfirst($guard->status) }}</dd>
                </div>
                @if ($guard->rank)
                    <div class="flex justify-between border-b border-zinc-100 pb-2">
                        <dt class="text-zinc-500">Position</dt>
                        <dd class="font-medium text-zinc-900">{{ $guard->rank }}</dd>
                    </div>
                @endif
                @if ($currentSite)
                    <div class="flex justify-between border-b border-zinc-100 pb-2">
                        <dt class="text-zinc-500">Current assignment</dt>
                        <dd class="font-medium text-zinc-900">{{ $currentSite }}</dd>
                    </div>
                @endif
            </dl>

            @if ($certifications->isNotEmpty())
                <div class="mt-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Certifications</h2>
                    <ul class="mt-2 space-y-1.5">
                        @foreach ($certifications as $cert)
                            <li class="flex justify-between text-sm">
                                <span class="text-zinc-700">{{ $cert->name }}</span>
                                <span class="text-zinc-500">{{ $cert->expires_at?->format('M j, Y') ?? 'No expiry' }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @if ($skills->isNotEmpty())
                <div class="mt-4">
                    <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500">Skills</h2>
                    <div class="mt-2 flex flex-wrap gap-1.5">
                        @foreach ($skills as $skill)
                            <span class="rounded bg-zinc-100 px-2 py-0.5 text-xs text-zinc-700">{{ $skill->skill }}{{ $skill->level ? ' · '.$skill->level : '' }}</span>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="mt-5 rounded-md bg-zinc-50 px-3 py-2 text-center text-xs text-zinc-500">
                @if ($verifiedAt)
                    Last verified {{ $verifiedAt->format('M j, Y g:i A') }}
                @endif
                · Scanned {{ $scannedAt->format('M j, Y g:i A') }}
            </div>
        </div>
    </div>

    <p class="mt-4 text-center text-xs text-zinc-400">
        This page confirms guard credentials. Contact {{ $companyName }} to report concerns.
    </p>
</div>
</body>
</html>
