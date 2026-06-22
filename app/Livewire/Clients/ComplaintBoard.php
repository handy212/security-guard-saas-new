<?php

namespace App\Livewire\Clients;

use Livewire\Component;

class ComplaintBoard extends Component
{
    public function render()
    {
        return view('livewire.clients.complaint-board', ['complaints'=>\App\Models\ClientComplaint::with(['clientAccount','site'])->latest()->limit(80)->get()])->layout('layouts.app');
    }
}
