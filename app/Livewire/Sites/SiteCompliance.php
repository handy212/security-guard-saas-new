<?php

namespace App\Livewire\Sites;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\Site;
use App\Models\SiteDocument;
use App\Models\SiteEmergencyContact;
use App\Models\SiteSlaRequirement;
use App\Support\TenantContext;
use Livewire\Component;

class SiteCompliance extends Component
{
    use AuthorizesModuleAccess;

    public array $contactForm = ['site_id' => '', 'name' => '', 'phone' => '', 'role' => ''];

    public array $documentForm = ['site_id' => '', 'title' => '', 'file_path' => '', 'document_type' => ''];

    public function mount(): void
    {
        $this->authorizePermission('sites.manage');
    }

    public function saveContact(): void
    {
        abort_unless(auth()->user()->can('sites.manage'), 403);
        SiteEmergencyContact::create($this->validate([
            'contactForm.site_id' => 'required',
            'contactForm.name' => 'required',
            'contactForm.phone' => 'required',
            'contactForm.role' => 'nullable',
        ])['contactForm'] + ['tenant_id' => TenantContext::id()]);
    }

    public function saveDocument(): void
    {
        abort_unless(auth()->user()->can('sites.manage'), 403);
        SiteDocument::create($this->validate([
            'documentForm.site_id' => 'required',
            'documentForm.title' => 'required',
            'documentForm.file_path' => 'required',
            'documentForm.document_type' => 'nullable',
        ])['documentForm'] + ['tenant_id' => TenantContext::id()]);
    }

    public function render()
    {
        return view('livewire.sites.site-compliance', [
            'contacts' => SiteEmergencyContact::with('site')->latest()->limit(50)->get(),
            'documents' => SiteDocument::with('site')->latest()->limit(50)->get(),
            'sla' => SiteSlaRequirement::with('site')->latest()->limit(50)->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
