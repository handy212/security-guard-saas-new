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
<body class="min-h-screen bg-zinc-100 font-sans antialiased text-zinc-900">
@php
    $tel = static fn (?string $phone) => $phone ? preg_replace('/[^\d+]/', '', $phone) : null;
    $primaryTel = $tel($primaryPhone);
    $reportTel = $tel($reportPhone);
@endphp

<header class="bg-zinc-950 px-4 py-5 text-white sm:px-6">
    <div class="mx-auto flex max-w-lg items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-[0.18em] text-zinc-400">{{ $companyNameUpper }}</p>
            <h1 class="mt-1 text-2xl font-extrabold tracking-tight">Know Your Guard</h1>
            <p class="mt-1 text-sm text-zinc-300">{{ $tagline }}</p>
        </div>
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-12 w-auto max-w-[120px] shrink-0 object-contain">
        @endif
    </div>
</header>

<main class="mx-auto max-w-lg px-4 pb-8 pt-4 sm:px-6">
    @if ($isSuspended ?? false)
        <div class="rounded-xl border border-red-300 bg-red-50 px-4 py-3.5">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-red-600 text-white">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-red-900">{{ $suspendedMessage }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-red-800">Do not grant access. Contact the security company if you need clarification.</p>
                </div>
            </div>
        </div>
    @elseif ($isAuthorisedToday)
        <div class="rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-3.5">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-600 text-white">
                    <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </span>
                <div>
                    <p class="text-sm font-bold uppercase tracking-wide text-emerald-900">{{ $page['verified_banner_title'] }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-emerald-800">{{ $page['verified_banner_hint'] }}</p>
                </div>
            </div>
        </div>
    @elseif ($isVerified)
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3.5">
            <p class="text-sm font-bold uppercase tracking-wide text-amber-900">Verified officer</p>
            <p class="mt-1 text-sm text-amber-800">Identity confirmed. Confirm current assignment before granting access.</p>
        </div>
    @else
        <div class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3.5">
            <p class="text-sm font-bold uppercase tracking-wide text-amber-900">Verification pending</p>
            <p class="mt-1 text-sm text-amber-800">Do not grant access until verification is complete.</p>
        </div>
    @endif

    <section class="mt-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
        <div class="flex gap-4">
            @if ($photoUrl)
                <img
                    src="{{ $photoUrl }}"
                    alt="{{ $guard->full_name }}"
                    class="h-28 w-24 shrink-0 rounded-xl border border-zinc-200 object-cover shadow-sm sm:h-32 sm:w-28"
                >
            @else
                <div class="flex h-28 w-24 shrink-0 items-center justify-center rounded-xl border border-zinc-200 bg-zinc-100 text-2xl font-bold text-zinc-500 sm:h-32 sm:w-28">
                    {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                </div>
            @endif

            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-bold leading-tight tracking-tight sm:text-xl">{{ $guard->full_name }}</h2>
                <p class="mt-0.5 text-sm text-zinc-500">
                    {{ $guard->dutyTypeLabel() }}
                    @if ($guard->rank)
                        · {{ $guard->rank }}
                    @endif
                </p>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @if ($guard->employee_number)
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-700">Guard ID: {{ $guard->employee_number }}</span>
                    @endif
                    @if ($branchName)
                        <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-semibold text-zinc-700">{{ $branchName }}</span>
                    @endif
                    <span @class([
                        'rounded-full px-2.5 py-1 text-[11px] font-semibold capitalize',
                        'bg-emerald-100 text-emerald-800' => $guard->status === 'active',
                        'bg-zinc-100 text-zinc-700' => $guard->status !== 'active',
                    ])>Employment: {{ $guard->status }}</span>
                </div>

                <p class="mt-3 text-xs text-zinc-500">
                    Company: <span class="font-semibold text-zinc-800">{{ $companyName }}</span>
                </p>
                <p class="mt-0.5 text-[11px] text-zinc-400">{{ $page['database_source_label'] }}</p>
            </div>
        </div>
    </section>

    @if ($currentAssignment)
        <section class="mt-4 rounded-2xl border border-emerald-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-emerald-700">Current assignment</p>
            <p class="mt-1 text-lg font-bold text-zinc-900">{{ $currentAssignment['site_name'] }}</p>
            <p class="mt-2 text-sm text-zinc-700">
                <span class="font-medium text-zinc-500">Authorised period:</span>
                <span class="font-semibold text-zinc-900">{{ $currentAssignment['date_range'] }}</span>
            </p>
            <p class="mt-2 text-xs text-zinc-500">
                Assignment status: On assignment • {{ $page['verified_by_label'] }}
            </p>
        </section>
    @endif

    <section class="mt-4 rounded-2xl border border-zinc-200 bg-zinc-50 p-4">
        <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">Client access guidance</p>
        <p class="mt-2 text-sm leading-relaxed text-zinc-700">{{ $page['access_guidance'] }}</p>
    </section>

    @if (! empty($page['expected_appearance']) || $skills->isNotEmpty())
        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            @if (! empty($page['expected_appearance']))
                <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">{{ $page['appearance_heading'] }}</p>
                    <ul class="mt-3 space-y-2">
                        @foreach ($page['expected_appearance'] as $item)
                            <li class="flex items-start gap-2 text-sm text-zinc-700">
                                <svg class="mt-0.5 h-4 w-4 shrink-0 text-emerald-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>
            @endif

            @if ($skills->isNotEmpty())
                <section class="rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
                    <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">{{ $page['competencies_heading'] }}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($skills as $skill)
                            <span class="rounded-full bg-zinc-100 px-2.5 py-1 text-[11px] font-medium text-zinc-700">
                                {{ $skill->skill }}{{ $skill->level ? ' · '.$skill->level : '' }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    @if ($primaryPhone || $reportPhone || $reportEmail)
        <section class="mt-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
            <p class="text-[11px] font-bold uppercase tracking-wide text-zinc-500">{{ $page['support_heading'] }}</p>
            <p class="mt-2 text-sm text-zinc-600">{{ $page['support_intro'] }}</p>

            @if ($phones)
                <ul class="mt-3 space-y-1 text-sm font-semibold text-zinc-800">
                    @foreach ($phones as $phone)
                        <li>{{ $phone }}</li>
                    @endforeach
                </ul>
            @endif

            <div class="mt-4 grid grid-cols-2 gap-2">
                @if ($primaryTel)
                    <a href="tel:{{ $primaryTel }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-600 px-3 py-3 text-center text-xs font-bold uppercase tracking-wide text-white transition hover:bg-emerald-700">
                        {{ $page['call_button_label'] }}
                    </a>
                @endif
                @if ($reportTel)
                    <a href="tel:{{ $reportTel }}" class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-3 py-3 text-center text-xs font-bold uppercase tracking-wide text-white transition hover:bg-zinc-800">
                        {{ $page['report_button_label'] }}
                    </a>
                @elseif ($reportEmail)
                    <a href="mailto:{{ $reportEmail }}" class="inline-flex items-center justify-center rounded-xl bg-zinc-900 px-3 py-3 text-center text-xs font-bold uppercase tracking-wide text-white transition hover:bg-zinc-800">
                        {{ $page['report_button_label'] }}
                    </a>
                @endif
            </div>
        </section>
    @endif

    <section class="mt-4 rounded-2xl border border-zinc-200 bg-white p-4 shadow-sm">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div class="text-xs text-zinc-500">
                <p class="font-semibold uppercase tracking-wide text-zinc-600">Verification log</p>
                @if ($verifiedAt)
                    <p class="mt-2">Identity last verified {{ $verifiedAt->format('M j, Y g:i A') }}</p>
                @endif
                <p @class(['mt-1' => $verifiedAt])>QR code scanned {{ $scannedAt->format('M j, Y g:i A') }}</p>
            </div>
            <p class="max-w-[200px] text-xs font-medium leading-relaxed text-emerald-700 sm:text-right">
                {{ $page['live_page_notice'] }}
            </p>
        </div>
    </section>
</main>

<footer class="bg-zinc-950 px-4 py-5 text-white sm:px-6">
    <div class="mx-auto max-w-lg">
        <p class="text-[11px] font-bold uppercase tracking-[0.18em] text-zinc-400">Security notice</p>
        <p class="mt-2 text-sm leading-relaxed text-zinc-300">{{ $page['security_notice'] }}</p>
    </div>
</footer>
</body>
</html>
