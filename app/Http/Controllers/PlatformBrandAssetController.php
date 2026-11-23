<?php

namespace App\Http\Controllers;

use App\Services\PlatformBrandingService;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PlatformBrandAssetController extends Controller
{
    public function __invoke(PlatformBrandingService $branding, string $kind, string $version): StreamedResponse
    {
        return $branding->assetResponse($kind, $version);
    }
}
