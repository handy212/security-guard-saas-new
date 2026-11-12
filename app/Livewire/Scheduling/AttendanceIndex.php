<?php

namespace App\Livewire\Scheduling;

use App\Enums\AttendanceStatus;
use App\Livewire\Concerns\AuthorizesModuleAccess;
use App\Livewire\Concerns\EmbedsInScheduler;
use App\Models\AttendanceLog;
use App\Models\BreakLog;
use App\Support\TenantContext;
use App\Support\TenantValidation;
use Carbon\Carbon;
use Livewire\Component;

class AttendanceIndex extends Component
{
    use AuthorizesModuleAccess;
    use EmbedsInScheduler;

    public array $breakForm = ['attendance_log_id' => '', 'type' => 'meal', 'started_at' => '', 'ended_at' => ''];

    public string $date = '';

    public string $statusFilter = 'all';

    public bool $showBreakForm = false;

    protected $queryString = ['date', 'statusFilter'];

    public function mount(): void
    {
        $this->authorizePermission('attendance.manage');
        $this->date = $this->date ?: today()->toDateString();
        $this->breakForm['started_at'] = now()->format('Y-m-d\TH:i');
    }

    public function openBreakForm(?int $attendanceLogId = null): void
    {
        $this->resetValidation();
        $anchor = Carbon::parse($this->date)->setTimeFromTimeString(now()->format('H:i'));
        $this->breakForm = [
            'attendance_log_id' => $attendanceLogId ? (string) $attendanceLogId : '',
            'type' => 'meal',
            'started_at' => $anchor->format('Y-m-d\TH:i'),
            'ended_at' => '',
        ];
        $this->showBreakForm = true;
    }

    public function closeBreakForm(): void
    {
        $this->showBreakForm = false;
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

        $log = AttendanceLog::where('tenant_id', TenantContext::id())->findOrFail($data['attendance_log_id']);
        if (! $log->clock_in_at || Carbon::parse($data['started_at'])->lt($log->clock_in_at)) {
            $this->addError('breakForm.started_at', 'Break must start at or after the recorded clock-in.');

            return;
        }
        $end = filled($data['ended_at'] ?? null) ? Carbon::parse($data['ended_at']) : null;
        if ($log->clock_out_at && (! $end || $end->gt($log->clock_out_at) || Carbon::parse($data['started_at'])->gte($log->clock_out_at))) {
            $this->addError('breakForm.ended_at', 'For a completed shift, enter an end time within the clock-in and clock-out window.');

            return;
        }
        $data['ended_at'] = $end;
        BreakLog::create($data + ['tenant_id' => TenantContext::id()]);
        $this->breakForm = ['attendance_log_id' => '', 'type' => 'meal', 'started_at' => now()->format('Y-m-d\TH:i'), 'ended_at' => ''];
        $this->showBreakForm = false;
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
            ->when(filled($this->date), fn ($q) => $q->where(fn ($day) => $day->whereDate('clock_in_at', $this->date)->orWhere(fn ($missing) => $missing->whereNull('clock_in_at')->whereDate('recorded_at', $this->date))))
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
