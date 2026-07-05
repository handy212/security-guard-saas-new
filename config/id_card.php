<?php

return [
    /** browsershot (matches preview) | dompdf (legacy fallback) */
    'pdf_driver' => env('ID_CARD_PDF_DRIVER', 'browsershot'),

    'chrome_path' => env('CHROME_PATH', '/usr/bin/chromium'),

    /** CR80 portrait — width × height in mm */
    'paper_width_mm' => 53.98,
    'paper_height_mm' => 85.6,

    /** On-screen / print design canvas (portrait) in CSS pixels */
    'design_width_px' => 280,
    'design_height_px' => 445,
];
