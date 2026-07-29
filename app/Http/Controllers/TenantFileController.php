<?php

namespace App\Http\Controllers;

use App\Models\SiteDocument;
use App\Support\TenantContext;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TenantFileController extends Controller
{
    public function __construct(private \App\Services\TenantFileStorageService $storage)
    {
    }

    public function guardPhoto(\App\Models\Guard $guard): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $guard->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($guard->photo_path, 404);
        abort_unless($this->storage->exists($guard->photo_path), 404);

        return $this->storage->response($guard->photo_path);
    }

    public function applicationPhoto(\App\Models\GuardApplication $application): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $application->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($application->photo_path, 404);
        abort_unless($this->storage->exists($application->photo_path), 404);

        return $this->storage->response($application->photo_path);
    }

    public function guardDocument(\App\Models\GuardDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage'), 403);
        abort_unless((int) $document->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($this->storage->exists($document->file_path), 404);

        return $this->storage->response($document->file_path);
    }

    public function idCardLogo(): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage') || auth()->user()->can('settings.manage'), 403);

        $path = \App\Models\TenantSetting::query()
            ->where('tenant_id', TenantContext::id())
            ->where('key', 'id_card')
            ->value('value')['logo_path'] ?? null;

        abort_unless($path, 404);
        abort_unless($this->storage->exists($path), 404);

        return $this->storage->response($path);
    }

    public function idCardBackLogo(): StreamedResponse
    {
        abort_unless(auth()->user()->can('guards.manage') || auth()->user()->can('settings.manage'), 403);

        $path = \App\Models\TenantSetting::query()
            ->where('tenant_id', TenantContext::id())
            ->where('key', 'id_card')
            ->value('value')['back_logo_path'] ?? null;

        abort_unless($path, 404);
        abort_unless($this->storage->exists($path), 404);

        return $this->storage->response($path);
    }

    public function idCardSignature(\App\Services\GuardIdCardLogoService $logos): \Symfony\Component\HttpFoundation\Response
    {
        abort_unless(auth()->user()->can('guards.manage') || auth()->user()->can('settings.manage'), 403);

        $path = \App\Models\TenantSetting::query()
            ->where('tenant_id', TenantContext::id())
            ->where('key', 'id_card')
            ->value('value')['signature_path'] ?? null;

        abort_unless($path, 404);
        abort_unless($this->storage->exists($path), 404);

        $trimmed = $logos->signaturePngBinary($path);
        if ($trimmed !== null) {
            return response($trimmed, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'private, max-age=300',
            ]);
        }

        return $this->storage->response($path);
    }

    public function clientDocument(\App\Models\ClientDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()->can('clients.manage'), 403);
        abort_unless((int) $document->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($this->storage->exists($document->file_path), 404);

        return $this->storage->response($document->file_path);
    }

    public function siteDocument(SiteDocument $document): StreamedResponse
    {
        abort_unless(auth()->user()->can('sites.manage'), 403);
        abort_unless((int) $document->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($this->storage->exists($document->file_path), 404);

        return $this->storage->response($document->file_path);
    }

    public function expenseReceipt(\App\Models\Expense $expense): StreamedResponse
    {
        abort_unless(auth()->user()->can('billing.manage'), 403);
        abort_unless((int) $expense->tenant_id === (int) TenantContext::id(), 404);
        abort_unless($expense->receipt_path, 404);
        abort_unless($this->storage->exists($expense->receipt_path), 404);

        return $this->storage->response($expense->receipt_path);
    }
}
