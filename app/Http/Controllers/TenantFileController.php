<?php

namespace App\Http\Controllers;

use App\Models\Guard;
use App\Models\GuardDocument;
use App\Services\TenantFileStorageService;
use App\Support\TenantContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantFileController extends Controller
{
    public function __construct(private TenantFileStorageService $storage)
    {
    }

    public function guardPhoto(Guard $guard): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($guard->photo_path, 404);
        abort_unless($this->storage->exists($guard->photo_path), 404);

        return $this->storage->response($guard->photo_path);
    }

    public function guardDocument(GuardDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $document->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($this->storage->exists($document->file_path), 404);

        return $this->storage->response($document->file_path);
    }
}
