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

    /*
     | Categories shown in the deploy kit step and preferred for field kits.
     | Vehicles / motors stay synchronized with fleet when created from Fleet.
     */
    'deploy_kit_categories' => [
        'Vehicles',
        'Motors',
        'Radios',
        'Bodycams',
    ],

    'fleet_type_categories' => [
        'car' => 'Vehicles',
        'van' => 'Vehicles',
        'motor' => 'Motors',
        'other' => 'Vehicles',
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
