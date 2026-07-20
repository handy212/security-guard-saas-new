<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Know Your Guard — {{ $guard->full_name }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css'])
</head>
<body @class([
    'relative min-h-screen font-sans text-zinc-900 antialiased',
    'bg-red-50' => ($statusTone ?? null) === 'suspended',
    'bg-amber-50' => ($statusTone ?? null) === 'unassigned',
    'bg-zinc-100' => ! in_array($statusTone ?? null, ['suspended', 'unassigned'], true),
])>
@php
    $tel = static fn (?string $phone) => $phone ? preg_replace('/[^\d+]/', '', $phone) : null;
    $primaryTel = $tel($primaryPhone);
    $reportTel = $tel($reportPhone);
    $supervisorTel = $tel($supervisor['phone'] ?? null);
    $hasAppearance = ! empty($page['expected_appearance']) || ! empty($issuedAssets);
    $hasSupport = $primaryPhone || $reportPhone || $reportEmail || $supervisor;
    $tone = $statusTone ?? (($isSuspended ?? false) ? 'suspended' : ($isAuthorisedToday ? 'authorised' : ($isVerified ? 'unassigned' : 'pending')));
@endphp

<style>:root { --tenant-brand: {{ $brandColor ?? '#0f766e' }}; }</style>

@if ($tone === 'suspended')
    <div class="pointer-events-none fixed inset-0 z-0 bg-red-600/20" aria-hidden="true"></div>
@elseif ($tone === 'unassigned')
    <div class="pointer-events-none fixed inset-0 z-0 bg-amber-400/25" aria-hidden="true"></div>
@endif

<header @class([
    'relative z-10 overflow-hidden px-4 py-5 text-white sm:px-6',
    'bg-red-950' => $tone === 'suspended',
    'bg-amber-950' => $tone === 'unassigned',
    'bg-zinc-950' => ! in_array($tone, ['suspended', 'unassigned'], true),
])>
    <div class="pointer-events-none absolute inset-0 opacity-45" style="background:
        radial-gradient(ellipse 80% 70% at 12% 20%, color-mix(in srgb, var(--tenant-brand) 55%, transparent), transparent 55%);"></div>
    <div class="relative mx-auto flex max-w-lg items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-zinc-400">{{ $companyNameUpper }}</p>
            <h1 class="mt-1 text-2xl font-semibold tracking-tight">Know Your Guard</h1>
            <p class="mt-1 text-sm text-zinc-300">{{ $tagline }}</p>
        </div>
        @if ($logoUrl)
            <img src="{{ $logoUrl }}" alt="{{ $companyName }}" class="h-12 w-auto max-w-[120px] shrink-0 object-contain">
        @endif
    </div>
</header>

<main class="relative z-10 mx-auto max-w-lg space-y-4 px-4 pb-8 pt-4 sm:px-6">
    @if ($tone === 'suspended')
        <div class="rounded-[var(--radius-card)] border-2 border-red-400 bg-red-100 px-4 py-3.5 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-red-600 text-white" aria-hidden="true">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-red-950">{{ $suspendedMessage }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-red-900">Do not grant access. Contact the security company if you need clarification.</p>
                </div>
            </div>
        </div>
    @elseif ($tone === 'unassigned')
        <div class="rounded-[var(--radius-card)] border-2 border-amber-400 bg-amber-100 px-4 py-3.5 shadow-sm">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-amber-500 text-white" aria-hidden="true">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-amber-950">{{ $page['unassigned_banner_title'] }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-amber-900">{{ $page['unassigned_banner_hint'] }}</p>
                </div>
            </div>
        </div>
    @elseif ($tone === 'authorised')
        <div class="rounded-[var(--radius-card)] border border-emerald-200/90 bg-emerald-50 px-4 py-3.5">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-emerald-600 text-white" aria-hidden="true">
                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                </span>
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-emerald-900">{{ $page['verified_banner_title'] }}</p>
                    <p class="mt-1 text-sm leading-relaxed text-emerald-800">{{ $page['verified_banner_hint'] }}</p>
                </div>
            </div>
        </div>
    @else
        <div class="rounded-[var(--radius-card)] border border-amber-200/90 bg-amber-50 px-4 py-3.5">
            <p class="text-sm font-semibold uppercase tracking-wide text-amber-900">Verification pending</p>
            <p class="mt-1 text-sm text-amber-800">Do not grant access until verification is complete.</p>
        </div>
    @endif

    <section @class([
        'card-surface p-4',
        'ring-2 ring-red-400' => $tone === 'suspended',
        'ring-2 ring-amber-400' => $tone === 'unassigned',
    ])>
        <div class="flex gap-4">
            <div @class([
                'relative h-28 w-24 shrink-0 overflow-hidden rounded-md sm:h-32 sm:w-28',
                'ring-2 ring-red-500' => $tone === 'suspended',
                'ring-2 ring-amber-500' => $tone === 'unassigned',
                'ring-1 ring-zinc-200' => ! in_array($tone, ['suspended', 'unassigned'], true),
            ])>
                @if ($photoUrl)
                    <img
                        src="{{ $photoUrl }}"
                        alt="{{ $guard->full_name }}"
                        class="h-full w-full object-cover"
                    >
                @else
                    <div class="flex h-full w-full items-center justify-center bg-accent-50 text-2xl font-semibold text-accent-700">
                        {{ strtoupper(substr($guard->first_name, 0, 1).substr($guard->last_name, 0, 1)) }}
                    </div>
                @endif
                @if ($tone === 'suspended')
                    <div class="absolute inset-0 bg-red-600/35" aria-hidden="true"></div>
                    <span class="absolute inset-x-0 bottom-0 bg-red-700/90 px-1 py-0.5 text-center text-[9px] font-bold uppercase tracking-wide text-white">Suspended</span>
                @elseif ($tone === 'unassigned')
                    <div class="absolute inset-0 bg-amber-500/30" aria-hidden="true"></div>
                    <span class="absolute inset-x-0 bottom-0 bg-amber-600/90 px-1 py-0.5 text-center text-[9px] font-bold uppercase tracking-wide text-white">Unassigned</span>
                @endif
            </div>

            <div class="min-w-0 flex-1">
                <h2 class="text-lg font-semibold leading-tight tracking-tight sm:text-xl">{{ $guard->full_name }}</h2>
                <p class="mt-1 text-sm text-zinc-600">
                    <span class="font-medium text-zinc-800">{{ $guard->dutyTypeLabel() }}</span>
                    @if ($guard->rank)
                        <span class="text-zinc-400">·</span> {{ $guard->rank }}
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-zinc-500">{{ $guard->duty_type?->description() ?? '' }}</p>

                <div class="mt-3 flex flex-wrap gap-1.5">
                    @if ($guard->employee_number)
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-semibold tabular-nums text-zinc-700">Guard ID: {{ $guard->employee_number }}</span>
                    @endif
                    @if ($branchName)
                        <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-semibold text-zinc-700">{{ $branchName }}</span>
                    @endif
                    <span @class([
                        'rounded-md px-2 py-1 text-[11px] font-semibold capitalize',
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
        <section class="overflow-hidden rounded-[var(--radius-card)] border border-emerald-200/90 bg-white">
            <div class="border-b border-emerald-100 bg-emerald-50/80 px-4 py-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-emerald-700">Current assignment</p>
            </div>
            <div class="p-4">
                <p class="text-lg font-semibold text-zinc-900">{{ $currentAssignment['site_name'] }}</p>
                <p class="mt-2 text-sm text-zinc-700">
                    <span class="font-medium text-zinc-500">Authorised period:</span>
                    <span class="font-semibold tabular-nums text-zinc-900">{{ $currentAssignment['date_range'] }}</span>
                </p>
                <p class="mt-2 text-xs text-zinc-500">
                    Assignment status: On assignment · {{ $page['verified_by_label'] }}
                </p>
            </div>
        </section>
    @elseif ($tone === 'unassigned')
        <section class="overflow-hidden rounded-[var(--radius-card)] border-2 border-amber-300 bg-white">
            <div class="border-b border-amber-200 bg-amber-100/80 px-4 py-2.5">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-amber-800">Current assignment</p>
            </div>
            <div class="p-4">
                <p class="text-lg font-semibold text-amber-950">No site assigned</p>
                <p class="mt-2 text-sm leading-relaxed text-amber-900">
                    This officer has no active site assignment right now. Do not grant site access.
                </p>
            </div>
        </section>
    @endif

    <section class="rounded-[var(--radius-card)] border border-zinc-200/90 bg-zinc-50/80 p-4">
        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Client access guidance</p>
        <p class="mt-2 text-sm leading-relaxed text-zinc-700">{{ $page['access_guidance'] }}</p>
    </section>

    @if ($hasAppearance || $skills->isNotEmpty())
        <div @class(['grid gap-4', 'sm:grid-cols-2' => $hasAppearance && $skills->isNotEmpty()])>
            @if ($hasAppearance)
                <section class="card-surface p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ $page['appearance_heading'] }}</p>
                    @if (! empty($page['expected_appearance']))
                        <ul class="mt-3 space-y-2">
                            @foreach ($page['expected_appearance'] as $item)
                                <li class="flex items-start gap-2 text-sm text-zinc-700">
                                    <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                    <span>{{ $item }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    @if (! empty($issuedAssets))
                        <div @class(['mt-4 border-t border-zinc-100 pt-3' => ! empty($page['expected_appearance'])])>
                            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ $page['issued_kit_heading'] ?? 'Issued for this shift' }}</p>
                            <ul class="mt-2 space-y-2">
                                @foreach ($issuedAssets as $asset)
                                    <li class="flex items-start gap-2 text-sm text-zinc-700">
                                        <svg class="mt-0.5 h-4 w-4 shrink-0 text-accent-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                        <span>
                                            {{ $asset['label'] }}
                                            @if (! empty($asset['tag']))
                                                <span class="text-zinc-400">· {{ $asset['tag'] }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                </section>
            @endif

            @if ($skills->isNotEmpty())
                <section class="card-surface p-4">
                    <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ $page['competencies_heading'] }}</p>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($skills as $skill)
                            <span class="rounded-md bg-zinc-100 px-2 py-1 text-[11px] font-medium text-zinc-700">
                                {{ $skill->skill }}{{ $skill->level ? ' · '.$skill->level : '' }}
                            </span>
                        @endforeach
                    </div>
                </section>
            @endif
        </div>
    @endif

    @if ($hasSupport)
        <section class="card-surface p-4">
            <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ $page['support_heading'] }}</p>
            <p class="mt-2 text-sm text-zinc-600">{{ $page['support_intro'] }}</p>

            <div class="mt-4 space-y-3">
                @if ($supervisor)
                    <div class="rounded-lg border border-zinc-200 bg-zinc-50/80 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">{{ $page['supervisor_heading'] ?? 'Site supervisor' }}</p>
                        <p class="mt-1 text-sm font-semibold text-zinc-900">{{ $supervisor['name'] }}</p>
                        <p class="mt-0.5 text-xs text-zinc-500">{{ $supervisor['role_label'] }}</p>
                        @if ($supervisor['phone'])
                            <p class="mt-2 text-sm font-semibold tabular-nums text-zinc-800">{{ $supervisor['phone'] }}</p>
                        @elseif ($supervisor['email'])
                            <p class="mt-2 text-sm font-semibold text-zinc-800">{{ $supervisor['email'] }}</p>
                        @endif
                        @if ($supervisorTel)
                            <a href="tel:{{ $supervisorTel }}" class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-accent-600 px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-accent-700">
                                {{ $page['call_supervisor_label'] ?? 'Call supervisor' }}
                            </a>
                        @elseif ($supervisor['email'])
                            <a href="mailto:{{ $supervisor['email'] }}" class="mt-3 inline-flex w-full items-center justify-center rounded-md bg-accent-600 px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-accent-700">
                                Email supervisor
                            </a>
                        @endif
                    </div>
                @endif

                @if ($primaryPhone || $reportPhone || $reportEmail)
                    <div class="rounded-lg border border-zinc-200 p-3">
                        <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Control room</p>
                        <p class="mt-1 text-xs text-zinc-500">Company verification support, 24/7.</p>

                        @if ($phones)
                            <ul class="mt-2 space-y-1 text-sm font-semibold tabular-nums text-zinc-800">
                                @foreach ($phones as $phone)
                                    <li>{{ $phone }}</li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-3 grid gap-2 {{ ($primaryTel && ($reportTel || $reportEmail)) ? 'grid-cols-2' : 'grid-cols-1' }}">
                            @if ($primaryTel)
                                <a href="tel:{{ $primaryTel }}" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-emerald-700">
                                    {{ $page['call_button_label'] }}
                                </a>
                            @endif
                            @if ($reportTel)
                                <a href="tel:{{ $reportTel }}" class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-zinc-800">
                                    {{ $page['report_button_label'] }}
                                </a>
                            @elseif ($reportEmail)
                                <a href="mailto:{{ $reportEmail }}" class="inline-flex items-center justify-center rounded-md bg-zinc-900 px-3 py-2.5 text-center text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-zinc-800">
                                    {{ $page['report_button_label'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    <section class="card-surface overflow-hidden">
        <div class="border-b border-zinc-100 px-4 py-2.5">
            <div class="flex items-center justify-between gap-3">
                <p class="text-[11px] font-semibold uppercase tracking-wide text-zinc-500">Verification log</p>
                <span class="inline-flex items-center gap-1.5 rounded-md bg-accent-50 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-accent-700">
                    <span class="h-1.5 w-1.5 rounded-full bg-accent-500" aria-hidden="true"></span>
                    Live
                </span>
            </div>
        </div>
        <dl class="divide-y divide-zinc-100">
            @if ($verifiedAt)
                <div class="flex items-baseline justify-between gap-4 px-4 py-3">
                    <dt class="text-xs text-zinc-500">Identity verified</dt>
                    <dd class="text-right text-xs font-semibold tabular-nums text-zinc-800">{{ $verifiedAt->format('M j, Y · g:i A') }}</dd>
                </div>
            @endif
            <div class="flex items-baseline justify-between gap-4 px-4 py-3">
                <dt class="text-xs text-zinc-500">QR scanned</dt>
                <dd class="text-right text-xs font-semibold tabular-nums text-zinc-800">{{ $scannedAt->format('M j, Y · g:i A') }}</dd>
            </div>
        </dl>
        <p class="border-t border-zinc-100 bg-zinc-50/80 px-4 py-2.5 text-[11px] leading-relaxed text-zinc-500">
            {{ $page['live_page_notice'] }}
        </p>
    </section>
</main>

<footer @class([
    'relative z-10 px-4 py-5 text-white sm:px-6',
    'bg-red-950' => $tone === 'suspended',
    'bg-amber-950' => $tone === 'unassigned',
    'bg-zinc-950' => ! in_array($tone, ['suspended', 'unassigned'], true),
])>
    <div class="mx-auto max-w-lg">
        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-zinc-400">Security notice</p>
        <p class="mt-2 text-sm leading-relaxed text-zinc-300">{{ $page['security_notice'] }}</p>
    </div>
</footer>
</body>
</html>
