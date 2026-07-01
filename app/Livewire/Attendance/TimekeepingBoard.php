<?php

namespace App\Livewire\Attendance;

use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\AttendanceLog;
use App\Models\BreakLog;
use App\Support\TenantContext;
use Livewire\Component;

class TimekeepingBoard extends Component
{
    use AuthorizesModuleAccess;

    public array $breakForm = ['attendance_log_id' => '', 'type' => 'meal', 'started_at' => '', 'ended_at' => ''];

    public function mount(): void
    {
        $this->authorizePermission('attendance.manage');
    }

    public function saveBreak(): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);
        BreakLog::create($this->validate([
            'breakForm.attendance_log_id' => 'required',
            'breakForm.type' => 'required',
            'breakForm.started_at' => 'required',
            'breakForm.ended_at' => 'nullable',
        ])['breakForm'] + ['tenant_id' => TenantContext::id()]);
    }

    public function render()
    {
        return view('livewire.attendance.timekeeping-board', [
            'logs' => AttendanceLog::with(['assignedGuard', 'site'])->latest()->limit(80)->get(),
            'breaks' => BreakLog::latest()->limit(20)->get(),
        ])->layout('layouts.app');
    }
}
