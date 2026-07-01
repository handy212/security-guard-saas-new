<?php

return [
    /** Days until issued QR tokens expire (renew via Regenerate QR on the guard profile). */
    'token_ttl_days' => (int) env('GUARD_VERIFICATION_TOKEN_TTL_DAYS', 365),
];
