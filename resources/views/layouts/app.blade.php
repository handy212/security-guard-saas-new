<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'GuardOps SaaS') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js']) @livewireStyles
</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen md:flex">
    <aside class="w-full border-r bg-white p-4 md:w-72">
        <div class="mb-6"><div class="text-xl font-black">GuardOps SaaS</div><div class="text-xs text-slate-500">Enterprise Security Platform</div></div>
        <nav class="space-y-1 text-sm">
            @foreach([
                '/dashboard'=>'Dashboard','/saas/tenants'=>'SaaS','/clients'=>'Clients','/clients/complaints'=>'Complaints','/sites'=>'Sites','/sites/compliance'=>'Site Compliance','/guards'=>'Guards','/guards/hr-records'=>'Guard HR','/schedules'=>'Schedules','/schedules/calendar'=>'Calendar','/schedules/marketplace'=>'Shift Market','/schedules/deployment-sheet'=>'Deployment','/attendance/timekeeping'=>'Attendance','/patrols'=>'Patrols','/patrols/playback'=>'Playback','/patrols/vehicles'=>'Vehicle Patrol','/incidents'=>'Incidents','/reports/daily'=>'Daily Reports','/dispatch'=>'Dispatch','/client-portal'=>'Client Portal','/billing/invoices'=>'Billing','/billing/payroll'=>'Payroll','/analytics'=>'Analytics','/equipment'=>'Equipment','/visitors'=>'Visitors','/compliance'=>'Compliance','/compliance/policies'=>'Policies','/settings/roles'=>'Roles & Permissions'
            ] as $href=>$label)
                <a class="block rounded-lg px-3 py-2 hover:bg-slate-100 {{ request()->is(trim($href,'/').'*') ? 'bg-slate-900 text-white' : '' }}" href="{{ $href }}">{{ $label }}</a>
            @endforeach
        </nav>
    </aside>
    <main class="flex-1">{{ $slot }}</main>
</div>
@livewireScripts
</body>
</html>
