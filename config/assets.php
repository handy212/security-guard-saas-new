<?php

return [
    'statuses' => [
        'available' => 'Available',
        'issued' => 'Issued',
        'maintenance' => 'Maintenance',
        'retired' => 'Retired',
        'lost' => 'Lost',
    ],

    'conditions' => [
        'good' => 'Good',
        'fair' => 'Fair',
        'poor' => 'Poor',
    ],

    'category_types' => [
        'serialized' => 'Serialized (unique items)',
        'consumable' => 'Consumable (quantity tracked)',
    ],

    'po_statuses' => [
        'draft' => 'Draft',
        'submitted' => 'Submitted',
        'ordered' => 'Ordered',
        'partial' => 'Partially received',
        'received' => 'Received',
        'cancelled' => 'Cancelled',
    ],
];
