<?php

namespace App\Livewire\Schedules;

use Livewire\Component;

class ShiftMarketplace extends Component
{
    public function render()
    {
        return view('livewire.schedules.shift-marketplace', ['bids'=>\App\Models\OpenShiftBid::with(['shift','guard'])->latest()->limit(50)->get(),'swaps'=>\App\Models\ShiftSwapRequest::latest()->limit(50)->get()])->layout('layouts.app');
    }
}
