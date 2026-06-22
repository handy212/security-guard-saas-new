<?php

return [
    'base_domain' => env('TENANCY_BASE_DOMAIN', 'guardops.test'),
    'central_domains' => array_filter(explode(',', env('TENANCY_CENTRAL_DOMAINS', 'localhost,127.0.0.1'))),
];
