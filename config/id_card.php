<?php

return [
    /** browsershot (matches preview) | dompdf (legacy fallback) */
    'pdf_driver' => env('ID_CARD_PDF_DRIVER', 'browsershot'),

    'chrome_path' => env('CHROME_PATH', '/usr/bin/chromium'),

    /** Spatie Browsershot needs Node to launch Chromium */
    'node_path' => env('NODE_BINARY', '/usr/bin/node'),
    'npm_path' => env('NPM_PATH', '/usr/bin/npm'),

    /**
     * CR80 / ISO ID-1 physical size in inches (landscape width × height).
     * Portrait swaps these axes.
     */
    'width_in' => 3.375,
    'height_in' => 2.125,

    /** On-screen / print design canvas (portrait) in CSS pixels */
    'design_width_px' => 280,
    'design_height_px' => 445,

    /** PNG export resolution (print-ready) */
    'png_dpi' => 300,
];
