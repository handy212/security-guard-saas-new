<?php

namespace App\Livewire\Visitors;

use App\Models\Guard;
use App\Models\Site;
use App\Models\VisitorLog;
use App\Support\TenantContext;
use Livewire\Component;

class VisitorLogIndex extends Component
{
    public string $search = '';

    public array $form = [
        'site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '', 'purpose' => '', 'vehicle_plate' => '',
    ];

    public function checkIn(): void
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);
        $data = $this->validate([
            'form.site_id' => 'required',
            'form.visitor_name' => 'required',
            'form.visitor_phone' => 'nullable',
            'form.company' => 'nullable',
            'form.purpose' => 'nullable',
            'form.vehicle_plate' => 'nullable',
        ])['form'];

        VisitorLog::create($data + [
            'tenant_id' => TenantContext::id(),
            'checked_in_at' => now(),
            'status' => 'checked_in',
        ]);

        $this->form = ['site_id' => '', 'visitor_name' => '', 'visitor_phone' => '', 'company' => '', 'purpose' => '', 'vehicle_plate' => ''];
    }

    public function checkOut(VisitorLog $visitor): void
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);
        $visitor->update(['checked_out_at' => now(), 'status' => 'checked_out']);
    }

    public function render()
    {
        abort_unless(auth()->user()->can('visitors.manage'), 403);

        return view('livewire.visitors.visitor-log-index', [
            'items' => VisitorLog::with('site')->when($this->search, fn ($q) => $q->where('visitor_name', 'like', '%'.$this->search.'%'))->latest()->limit(50)->get(),
            'sites' => Site::orderBy('name')->get(),
        ])->layout('layouts.app');
    }
}
