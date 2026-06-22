<?php
namespace App\Livewire\Compliance;
use Livewire\Component;
use App\Models\GuardCertification;
class ComplianceDashboard extends Component
{
    public string $search = '';
    public function render()
    {
        $items = GuardCertification::query()->latest()->limit(50)->get();
        return view('livewire.compliance.compliance-dashboard', compact('items'))->layout('layouts.app');
    }
}
