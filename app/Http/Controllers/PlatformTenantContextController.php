<?php

namespace App\Http\Controllers;

use App\Support\TenantContext;
use Illuminate\Http\RedirectResponse;

class PlatformTenantContextController extends Controller
{
    public function exit(): RedirectResponse
    {
        abort_unless(TenantContext::isPlatformAdmin(), 403);

        TenantContext::exitTenant();
        // Safe here: this action redirects away from Livewire, unlike in-component exits.
        session()->regenerate();

        return redirect()->route('saas.tenants');
    }
}
