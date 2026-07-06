<?php

namespace App\Livewire\Scheduling;

use App\Enums\AttendanceStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Models\AttendanceLog;
use App\Models\BreakLog;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Carbon\Carbon;
use Livewire\Component;

class AttendanceIndex extends Component
{
    use AuthorizesModuleAccess;

    public array $breakForm = ['attendance_log_id' => '', 'type' => 'meal', 'started_at' => '', 'ended_at' => ''];

    public string $date = '';

    public string $statusFilter = 'all';

    protected $queryString = ['date', 'statusFilter'];

    public function mount(): void
    {
        $this->authorizePermission('attendance.manage');
        $this->date = $this->date ?: today()->toDateString();
        $this->breakForm['started_at'] = now()->format('Y-m-d\TH:i');
    }

    public function previousDay(): void
    {
        $this->date = Carbon::parse($this->date)->subDay()->toDateString();
    }

    public function nextDay(): void
    {
        $this->date = Carbon::parse($this->date)->addDay()->toDateString();
    }

    public function goToday(): void
    {
        $this->date = today()->toDateString();
    }

    public function saveBreak(): void
    {
        $data = $this->validate([
            'breakForm.attendance_log_id' => ['required', TenantValidation::exists('attendance_logs')],
            'breakForm.type' => 'required|in:meal,rest',
            'breakForm.started_at' => 'required|date',
            'breakForm.ended_at' => 'nullable|date|after:breakForm.started_at',
        ])['breakForm'];

        BreakLog::create($data + ['tenant_id' => TenantContext::id()]);
        $this->breakForm = ['attendance_log_id' => '', 'type' => 'meal', 'started_at' => now()->format('Y-m-d\TH:i'), 'ended_at' => ''];
        session()->flash('status', 'Break logged.');
    }

    public function deleteBreak(int $breakId): void
    {
        BreakLog::where('tenant_id', TenantContext::id())->whereKey($breakId)->delete();
        session()->flash('status', 'Break removed.');
    }

    public function render()
    {
        $tenantId = TenantContext::id();

        $logsQuery = AttendanceLog::with(['assignedGuard', 'site'])
            ->where('tenant_id', $tenantId)
            ->when(filled($this->date), fn ($q) => $q->whereDate('clock_in_at', $this->date))
            ->when($this->statusFilter !== 'all', fn ($q) => $q->where('status', $this->statusFilter))
            ->latest();

        $logs = $logsQuery->limit(80)->get();

        $breaks = BreakLog::with(['attendanceLog.assignedGuard'])
            ->where('tenant_id', $tenantId)
            ->when(filled($this->date), function ($q) {
                $q->whereHas('attendanceLog', fn ($log) => $log->whereDate('clock_in_at', $this->date));
            })
            ->latest()
            ->limit(20)
            ->get();

        return view('livewire.scheduling.attendance-index', [
            'logs' => $logs,
            'breaks' => $breaks,
            'statusOptions' => AttendanceStatus::cases(),
        ])->layout('layouts.app');
    }
}
