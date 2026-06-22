<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('guardops:health', function () {
    $this->info('GuardOps SaaS starter is ready.');
});
