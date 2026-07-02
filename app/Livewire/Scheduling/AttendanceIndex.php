<?php

namespace App\Livewire\Scheduling;

use App\Models\AttendanceLog;
use App\Models\BreakLog;
use App\Support\TenantContext;
use Livewire\Component;

class AttendanceIndex extends Component
{
    public array $breakForm = ['attendance_log_id' => '', 'type' => 'meal', 'started_at' => '', 'ended_at' => ''];

    public function mount(): void
    {
        abort_unless(auth()->user()->can('attendance.manage'), 403);
    }

    public function saveBreak(): void
    {
        BreakLog::create($this->validate([
            'breakForm.attendance_log_id' => 'required',
            'breakForm.type' => 'required',
            'breakForm.started_at' => 'required',
            'breakForm.ended_at' => 'nullable',
        ])['breakForm'] + ['tenant_id' => TenantContext::id()]);
        session()->flash('status', 'Break logged.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        return view('livewire.scheduling.attendance-index', [
            'logs' => AttendanceLog::with(['assignedGuard', 'site'])->where('tenant_id', $tenantId)->latest()->limit(80)->get(),
            'breaks' => BreakLog::where('tenant_id', $tenantId)->latest()->limit(20)->get(),
        ])->layout('layouts.app');
    }
}
