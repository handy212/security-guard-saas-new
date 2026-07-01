<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Models\GuardDocument;
use App\Support\TenantContext;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantFileController extends Controller
{
    public function guardPhoto(Guard $guard): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($guard->photo_path, 404);

        return $this->streamPublicFile($guard->photo_path);
    }

    public function guardDocument(GuardDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $document->tenant_id === (int) TenantContext::id(), 404);

        return $this->streamPublicFile($document->file_path);
    }

    private function streamPublicFile(string $path): StreamedResponse
    {
        abort_unless(Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path);
    }
}
