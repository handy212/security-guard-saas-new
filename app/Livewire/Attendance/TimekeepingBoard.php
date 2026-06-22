<?php

namespace App\Livewire\Attendance;

use Livewire\Component;

class TimekeepingBoard extends Component
{
    public function render()
    {
        return view('livewire.attendance.timekeeping-board', ['logs'=>\App\Models\AttendanceLog::with(['guard','shift'])->latest()->limit(80)->get(),'breaks'=>\App\Models\BreakLog::latest()->limit(20)->get()])->layout('layouts.app');
    }
}
