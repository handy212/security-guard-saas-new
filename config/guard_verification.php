<?php

return [
    /** Days until issued QR tokens expire (per-tenant override via tenant_settings.verification). */
    'token_ttl_days' => (int) env('GUARD_VERIFICATION_TOKEN_TTL_DAYS', 365),
];
