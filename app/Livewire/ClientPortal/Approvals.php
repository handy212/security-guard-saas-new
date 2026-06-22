<?php
namespace App\Livewire\ClientPortal;
use Livewire\Component;
use App\Models\ClientReportApproval;
class Approvals extends Component
{
    public string $search = '';
    public function render()
    {
        $items = ClientReportApproval::query()->latest()->limit(50)->get();
        return view('livewire.clientportal.approvals', compact('items'))->layout('layouts.app');
    }
}
