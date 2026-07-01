<?php

return [
  'enabled' => env('SSO_ENABLED', false),
  'provider' => env('SSO_PROVIDER', 'oidc'),
  'client_id' => env('SSO_CLIENT_ID'),
  'client_secret' => env('SSO_CLIENT_SECRET'),
  'redirect_uri' => env('SSO_REDIRECT_URI', env('APP_URL').'/auth/sso/callback'),
  'issuer' => env('SSO_ISSUER'),
];
