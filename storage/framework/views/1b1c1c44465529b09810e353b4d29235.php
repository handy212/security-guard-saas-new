<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo e(config('app.name', 'GuardOps SaaS')); ?></title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?> <?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::styles(); ?>

</head>
<body class="bg-slate-50 text-slate-900">
<div class="min-h-screen md:flex">
    <aside class="w-full border-r bg-white p-4 md:w-72">
        <div class="mb-6">
            <div class="text-xl font-black">GuardOps SaaS</div>
            <div class="text-xs text-slate-500">Enterprise Security Platform</div>
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php if(auth()->guard()->check()): ?>
                <div class="mt-2 text-xs text-slate-600"><?php echo e(auth()->user()->name); ?></div>
                <form method="POST" action="<?php echo e(route('logout')); ?>" class="mt-1">
                    <?php echo csrf_field(); ?>
                    <button type="submit" class="text-xs text-red-600 hover:underline">Sign out</button>
                </form>
            <?php endif; ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </div>
        <nav class="space-y-1 text-sm">
            <?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if BLOCK]><![endif]--><?php endif; ?><?php $__currentLoopData = [
                '/dashboard'=>'Dashboard','/saas/tenants'=>'SaaS','/clients'=>'Clients','/clients/complaints'=>'Complaints','/sites'=>'Sites','/sites/compliance'=>'Site Compliance','/guards'=>'Guards','/guards/hr-records'=>'Guard HR','/schedules'=>'Schedules','/schedules/calendar'=>'Calendar','/schedules/marketplace'=>'Shift Market','/schedules/deployment-sheet'=>'Deployment','/attendance/timekeeping'=>'Attendance','/patrols'=>'Patrols','/patrols/playback'=>'Playback','/patrols/vehicles'=>'Vehicle Patrol','/incidents'=>'Incidents','/reports/daily'=>'Daily Reports','/dispatch'=>'Dispatch','/client-portal'=>'Client Portal','/billing/invoices'=>'Billing','/billing/payroll'=>'Payroll','/analytics'=>'Analytics','/equipment'=>'Equipment','/visitors'=>'Visitors','/compliance'=>'Compliance','/compliance/policies'=>'Policies','/settings/roles'=>'Roles & Permissions'
            ]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $href=>$label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <a class="block rounded-lg px-3 py-2 hover:bg-slate-100 <?php echo e(request()->is(trim($href,'/').'*') ? 'bg-slate-900 text-white' : ''); ?>" href="<?php echo e($href); ?>"><?php echo e($label); ?></a>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><?php if(\Livewire\Mechanisms\ExtendBlade\ExtendBlade::isRenderingLivewireComponent()): ?><!--[if ENDBLOCK]><![endif]--><?php endif; ?>
        </nav>
    </aside>
    <main class="flex-1"><?php echo e($slot); ?></main>
</div>
<?php echo \Livewire\Mechanisms\FrontendAssets\FrontendAssets::scripts(); ?>

</body>
</html>
<?php /**PATH /workspace/resources/views/layouts/app.blade.php ENDPATH**/ ?>