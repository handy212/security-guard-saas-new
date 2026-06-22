<?php

namespace App\Livewire\Patrols;

use Livewire\Component;

class Playback extends Component
{
    public function render()
    {
        return view('livewire.patrols.playback', ['sessions'=>\App\Models\PatrolSession::with('guard')->latest()->limit(30)->get(),'points'=>\App\Models\PatrolPlaybackPoint::latest()->limit(200)->get()])->layout('layouts.app');
    }
}
