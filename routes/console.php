<?php

use Illuminate\Support\Facades\Artisan;

Artisan::command('guardops:health', function () {
    $this->info('GuardCore Pro SaaS starter is ready.');
});
