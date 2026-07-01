<?php

return [
    'driver' => env('FILE_SCANNER', 'null'),

    'clamav' => [
        'binary' => env('CLAMAV_BINARY', 'clamscan'),
    ],
];
