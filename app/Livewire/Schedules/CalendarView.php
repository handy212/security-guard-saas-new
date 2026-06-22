<?php

namespace App\Livewire\Schedules;

use Livewire\Component;

class CalendarView extends Component
{
    public function render()
    {
        return view('livewire.schedules.calendar-view', ['shifts'=>\App\Models\Shift::with('site')->whereBetween('starts_at',[now()->startOfMonth(),now()->endOfMonth()])->orderBy('starts_at')->get()])->layout('layouts.app');
    }
}
