<?php

namespace App\Services;

use App\Models\{AttendanceLog, ShiftAssignment};
use Carbon\Carbon;
use RuntimeException;

class AttendanceService
{
    public function clockIn(int $assignmentId, float $lat, float $lng): AttendanceLog
    {
        $assignment = ShiftAssignment::with('shift.site')->findOrFail($assignmentId);
        $status = Carbon::now()->gt(Carbon::parse($assignment->shift->starts_at)->addMinutes(10)) ? 'late' : 'on_time';

        $log = AttendanceLog::create([
            'tenant_id' => $assignment->tenant_id,
            'shift_assignment_id' => $assignment->id,
            'guard_id' => $assignment->guard_id,
            'site_id' => $assignment->shift->site_id,
            'clock_in_at' => now(),
            'clock_in_latitude' => $lat,
            'clock_in_longitude' => $lng,
            'status' => $status,
            'geofence_validated' => $this->withinGeofence($assignment->shift->site, $lat, $lng),
        ]);

        $assignment->update(['status' => 'in_progress']);
        return $log;
    }

    public function clockOut(int $attendanceId, float $lat, float $lng): AttendanceLog
    {
        $log = AttendanceLog::with('shiftAssignment.shift')->findOrFail($attendanceId);
        if ($log->clock_out_at) {
            throw new RuntimeException('Attendance record is already clocked out.');
        }
        $log->update([
            'clock_out_at' => now(),
            'clock_out_latitude' => $lat,
            'clock_out_longitude' => $lng,
            'status' => 'completed',
            'worked_minutes' => Carbon::parse($log->clock_in_at)->diffInMinutes(now()),
        ]);
        $log->shiftAssignment?->update(['status' => 'completed']);
        return $log->fresh();
    }

    public function withinGeofence($site, float $lat, float $lng): bool
    {
        if (! $site || ! $site->latitude || ! $site->longitude) return false;
        $earth = 6371000;
        $dLat = deg2rad($lat - $site->latitude);
        $dLng = deg2rad($lng - $site->longitude);
        $a = sin($dLat/2) ** 2 + cos(deg2rad($site->latitude)) * cos(deg2rad($lat)) * sin($dLng/2) ** 2;
        $distance = 2 * $earth * atan2(sqrt($a), sqrt(1-$a));
        return $distance <= ($site->geofence_radius_meters ?? 150);
    }
}
